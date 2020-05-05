#!/usr/bin/python3

#############################################################
# 
# pvsw_agent.py
# Nathan Gardiner <ngardiner@gmail.com>
#
# This agent is responsible for enforcing the configuration defined
# by the web console. It aims to be idempotent, in such a way that it
# will gather the current network state every minute, compare it to
# the intended state, and change the values which are not aligned.
#

import json
import logging
import logging.handlers
import netifaces
import os
from shlex import split
import subprocess
from urllib import request, parse

def debug_print(lvl, msg):
    logger.info("pvsw_agent: %s" % msg)

def getDB(query):
    query = query.encode("utf-8")
    req = request.Request("http://127.0.0.1/post.php", data=query)
    resp = request.urlopen(req).read().decode("utf-8")
    try:
      return json.loads(resp)
    except json.decoder.JSONDecodeError:
      return

def getOVSBridges():
    s = subprocess.Popen(["/usr/bin/ovs-vsctl list-br"], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def getOVSPorts(bridge):
    s = subprocess.Popen(["/usr/bin/ovs-vsctl list-ports " + bridge], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def getSetting(setting,settings):
    # Iterate through list of settings, find the value
    value = ""
    for seti in settings:
        if seti['setting'] == setting:
            value = seti['value']
    return value

def setQinQ():
    setting = runCmdString("/usr/bin/ovs-vsctl get Open_vSwitch . other_config:vlan-limit")
    debug_print(9, "QinQ Command Output: %s" % setting)
    if (setting != '"2"'):
        # Turn on Q-in-Q support
        runCmd("/usr/bin/ovs-vsctl set Open_vSwitch . other_config:vlan-limit=2")
        runCmd("/usr/bin/ovs-appctl revalidator/purge")

def runCmd(cmd):
    subprocess.call(split(cmd), stdout=open(os.devnull, "w"), stderr=subprocess.STDOUT)

def runCmdString(cmd):
    s = subprocess.Popen(split(cmd), stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8")

# Main execution loop

# Enable syslog logging
logger = logging.getLogger('MyLogger')
logger.setLevel(logging.DEBUG)
handler = logging.handlers.SysLogHandler(address = '/dev/log')
logger.addHandler(handler)
debug_print(9, "pvsw_agent: Execution begin");

# Ensure IP Forwarding is enabled for all interfaces
runCmd("/sbin/sysctl net.ipv4.ip_forward=1")

# Ensure Q-in-Q is enabled
setQinQ()

def doMainLoop():

  # These data structures hold both the OVS defined and DB defined data
  # based on the values assigned
  bridges = {}
  bridgeports = {}

  # Fetch settings from server
  settings = getDB("json=getSettings")
  if (not settings):
      # Issue fetching settings. Set it to an empty list.
      settings = []

  # Gather a list of vswitches on the device
  ovsbridges = getOVSBridges()

  for ovsbridge in ovsbridges:
    bridges[ovsbridge] = {}
    bridgeports[ovsbridge] = {}
    bridges[ovsbridge]['local_status'] = 1;

    # Request all configured ports for a bridge
    ovsports = getOVSPorts(ovsbridge)

    for ovsport in ovsports:
        bridgeports[ovsbridge][ovsport] = {}
        bridgeports[ovsbridge][ovsport]['local_status'] = 1;

  # Connect to the webserver and request a list of vswitches
  dbswitches = getDB("json=getSwitches")

  # Iterate through the list of vswitches
  for switch in dbswitches:

    # Check if the vswitch is configured locally
    if bridges.get(switch['name'], None) == None:
      bridges[switch['name']] = {}
      bridges[switch['name']]['db_status'] = 3
    else:
      bridges[switch['name']]['db_status'] = 2

    # Exclude any bridge uplink ports from being removed
    if (switch['uplink_type'] == "1"):
      # This is a physical link
      bridgeports[switch['name']][switch['uplink_interface']]['db_status'] = 3
      bridgeports[switch['name']][switch['uplink_interface']]['skip_ip'] = 1

    if (switch['uplink_type'] == "2"):
      # This is a patch port link
      uplinkport = "%svl%s" % (switch['uplink_interface'], switch['uplink_vlan'])
      downlinkport = "%strunk" % switch['name']
 
      # Set these two ports as db_status=3 (in sync) and skip_ip
      bridgeports[switch['name']][downlinkport]['db_status'] = 3
      bridgeports[switch['name']][downlinkport]['skip_ip'] = 1
      bridgeports[switch['uplink_interface']][uplinkport]['db_status'] = 3
      bridgeports[switch['uplink_interface']][uplinkport]['skip_ip'] = 1

  # Create any missing bridges
  for bridge in bridges:
    if bridges[bridge].get('db_status',0) == 2:
      logger.info('Bridge %s does not exist. Adding.' % bridge)

      # We add the bridge first
      runCmd("/usr/bin/ovs-vsctl add-br %s" % bridge)

      # Then we add the port (either interface or patch port) connecting the bridge to its uplink.
      bridges[bridge]['db_status'] = 3

  # Connect to the webserver and request a list of vlan interfaces
  for switch in dbswitches:
    # Fetch VLAN interfaces from server
    dbports = getDB("json=getVLANs&switch=%s" % switch['name'])

    # Iterate through the vlan interfaces
    for dbport in dbports:
      dbbport = "%svl%s" % (dbport['switch_name'], dbport['vlan_id'])
      logger.info(dbbport)
      if (bridgeports[dbport['switch_name']].get(dbbport, None) == None):
          bridgeports[dbport['switch_name']][dbbport] = {}
      bridgeports[dbport['switch_name']][dbbport]['ip_address'] = dbport.get('ip_address',"")
      bridgeports[dbport['switch_name']][dbbport]['ip_node_1'] = dbport.get('ha_ipa', "")
      bridgeports[dbport['switch_name']][dbbport]['ip_node_2'] = dbport.get('ha_ipb', "")
      bridgeports[dbport['switch_name']][dbbport]['mask_length'] = dbport['mask_length']
      bridgeports[dbport['switch_name']][dbbport]['vlan_id'] = dbport['vlan_id']
      if (bridgeports[dbport['switch_name']][dbbport].get('local_status', None) == None):
        # Port doesn't exist locally. Seed a value
        bridgeports[dbport['switch_name']][dbbport]['local_status'] = 0
      # If the port already exists locally, set db_status to 3
      if bridgeports[dbport['switch_name']][dbbport]['local_status'] == 1:
        # Set skip_ip if it's a reserved VLAN, otherwise we'd try changing its
        # IP address later.
        if (dbport['rt_table'] == "99999"):
          bridgeports[dbport['switch_name']][dbbport]['skip_ip'] = 1
        bridgeports[dbport['switch_name']][dbbport]['db_status'] = 3
      else:
        # Otherwise, set it to 2 (unless it's a reserved port, which is 3)
        if (dbport['rt_table'] == "99999"):
          bridgeports[dbport['switch_name']][dbbport]['db_status'] = 3
          bridgeports[dbport['switch_name']][dbbport]['skip_ip'] = 1
        else:
          bridgeports[dbport['switch_name']][dbbport]['db_status'] = 2
          debug_print(1, "Found new VLAN interface to configure: %svl%s" % (dbport['switch_name'], dbport['vlan_id']))

  # Create any missing internal ports
  for bname in bridgeports:
    for bport in bridgeports[bname]:
        if bridgeports[bname][bport].get('db_status',0) == 2:
          logger.info('Port %s does not exist as an OVS port. Adding.' % bport)
          runCmd("/usr/bin/ovs-vsctl add-port %s %s tag=%s -- set interface %s type=internal" % (bname,bport,bridgeports[bname][bport].get('vlan_id', 9999),bport))
          bridgeports[bname][bport]['db_status'] = 3

  # Make sure IP addresses match up
  for bname in bridgeports:
    for bport in bridgeports[bname]:
        # Skip IP configuration for interfaces marked with skip_ip set
        if bridgeports[bname][bport].get('skip_ip', 0):
            continue
        # Work out which IP to use. Single router = ip_address, multi = ip_node_a
        ip_touse = bridgeports[bname][bport].get('ip_address', "")
        if getSetting('ha_enable', settings) == "1":
            if (bridgeports[bname][bport].get('ip_node_1', "") != None):
              ip_touse = '.'.join(ip_touse.split('.')[:-1])+"."+bridgeports[bname][bport].get('ip_node_1', "")
            else:
              debug_print(1, "Interface %s has no HA IP assigned despite HA being enabled. Using Cluster IP %s instead, this should be fixed." % (bport,ip_touse))

        # Check if interface has an IP address associated with it
        if bridgeports[bname][bport].get('ip_address', None):
            # Check if the interface itself has an IP address assigned.
            if netifaces.ifaddresses(bport).get(netifaces.AF_INET, None):
              if ip_touse != netifaces.ifaddresses(bport)[netifaces.AF_INET][0]['addr']:
                  debug_print(1, "Changing IP address %s to %s for interface %s" % (netifaces.ifaddresses(bport)[netifaces.AF_INET][0]['addr'], ip_touse, bport));
            else:
                # Interface does not have an IP Address assigned to it.
                # We assign our configured IP address to it.
                debug_print(1, "Adding IP address %s to unconfigured interface %s." % (bridgeports[bname][bport]['ip_address'] + "/" + bridgeports[bname][bport]['mask_length'], bport));
                runCmd("/sbin/ip addr replace %s dev %s" % (bridgeports[bname][bport]['ip_address'] + "/" + bridgeports[bname][bport]['mask_length'], bport))

  # For HA clusters, we need to check the status of VIPs
  if getSetting('ha_enable', settings) == "1":
    for bname in bridgeports:
      for bport in bridgeports[bname]:
        # Skip VIP configuration for interfaces marked with skip_ip set
        if bridgeports[bname][bport].get('skip_ip', 0):
          continue
        # Check if VLAN has a VIP address associated with it
        # pcs resource config vmbr2vl1vip

  # Activate all interfaces that we agree upon (db_status = 3)
  for bname in bridgeports:
    for bport in bridgeports[bname]:
      if bridgeports[bname][bport].get('db_status',0) == 3:
        runCmd("/sbin/ip link set %s up" % bport)

  # Now, reverse the search and find interfaces configured which aren't in the
  # DB, and remove them from the bridge they are configured on.
  for bname in bridgeports:
    for bport in bridgeports[bname]:
      if bridgeports[bname][bport].get('db_status',0) == 0:
        debug_print(1, 'Port %s does not exist in the Database. Removing.' % bport)
        runCmd("/usr/bin/ovs-vsctl del-port %s %s" % (bname,bport))
        bridgeports[bname][bport]['db_status'] = 1

# Tun main loop
doMainLoop()
