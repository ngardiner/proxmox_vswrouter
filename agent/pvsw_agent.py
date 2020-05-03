#!/usr/bin/python3

import json
import logging
import logging.handlers
import netifaces
import os
from shlex import split
import subprocess
from urllib import request, parse

def debug_print(msg):
    print(msg)

def getOVSBridges():
    s = subprocess.Popen(["ovs-vsctl list-br"], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def getOVSPorts(bridge):
    s = subprocess.Popen(["ovs-vsctl list-ports " + bridge], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def runCmd(cmd):
    subprocess.call(split(cmd), stdout=open(os.devnull, "w"), stderr=subprocess.STDOUT)

# Main execution loop

# Enable syslog logging
logger = logging.getLogger('MyLogger')
logger.setLevel(logging.DEBUG)
handler = logging.handlers.SysLogHandler(address = '/dev/log')
logger.addHandler(handler)
logger.info("pvsw_agent: Execution begin");

# Ensure IP Forwarding is enabled for all interfaces
runCmd("sysctl net.ipv4.ip_forward=1")

# These data structures hold both the OVS defined and DB defined data
# based on the values assigned
bridges = {}
bridgeports = {}

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
data = "json=getSwitches".encode("utf-8")
req = request.Request("http://127.0.0.1/post.php", data=data)
resp = request.urlopen(req).read().decode("utf-8")
dbswitches = json.loads(resp)

# Iterate through the list of vswitches
for switch in dbswitches:

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

# Connect to the webserver and request a list of vlan interfaces
for switch in dbswitches:
  data = "json=getVLANs&switch=%s" % switch['name']
  data = data.encode("utf-8")
  req = request.Request("http://127.0.0.1/post.php", data=data)
  resp = request.urlopen(req).read().decode("utf-8")
  dbports = json.loads(resp)

  # Iterate through the vlan interfaces
  for dbport in dbports:
    dbbport = "%svl%s" % (dbport['switch_name'], dbport['vlan_id'])
    if (bridgeports[dbport['switch_name']].get(dbbport, None) == None):
        bridgeports[dbport['switch_name']][dbbport] = {}
    bridgeports[dbport['switch_name']][dbbport]['ip_address'] = dbport['ip_address']
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
    print("%svl%s" % (dbport['switch_name'], dbport['vlan_id']))

print(bridgeports)

# Create any missing internal ports
for bname in bridgeports:
    for bport in bridgeports[bname]:
        if bridgeports[bname][bport].get('db_status',0) == 2:
          logger.info('Port %s does not exist as an OVS port. Adding.' % bport)
          runCmd("ovs-vsctl add-port %s %s tag=%s -- set interface %s type=internal" % (bname,bport,bridgeports[bname][bport].get('vlan_id', 9999),bport))
          bridgeports[bname][bport]['db_status'] = 3

# Make sure IP addresses match up
for bname in bridgeports:
    for bport in bridgeports[bname]:
        # Skip IP configuration for interfaces marked with skip_ip set
        if bridgeports[bname][bport].get('skip_ip', 0):
            continue
        # Check if interface has an IP address associated with it
        if bridgeports[bname][bport].get('ip_address', None):
            # Check if the interface itself has an IP address assigned.
            if netifaces.ifaddresses(bport).get(netifaces.AF_INET, None):
              if bridgeports[bname][bport]['ip_address'] != netifaces.ifaddresses(bport)[netifaces.AF_INET][0]['addr']:
                print("Would replace "+bridgeports[bname][bport]['ip_address']);
            else:
                # Interface does not have an IP Address assigned to it.
                # We assign our configured IP address to it.
                print(bridgeports[bname][bport]['ip_address'] + "aaa");
                logger.info("pvsw_agent: Adding IP address %s to unconfigured interface %s." % (bridgeports[bname][bport]['ip_address'] + "/" + bridgeports[bname][bport]['mask_length'], bport));
                runCmd("ip addr replace %s dev %s" % (bridgeports[bname][bport]['ip_address'] + "/" + bridgeports[bname][bport]['mask_length'], bport))

# Activate all interfaces that we agree upon (db_status = 3)
for bname in bridgeports:
  for bport in bridgeports[bname]:
    if bridgeports[bname][bport].get('db_status',0) == 3:
      runCmd("ip link set %s up" % bport)

# Now, reverse the search and find interfaces configured which aren't in the
# DB.
for bname in bridgeports:
  for bport in bridgeports[bname]:
    if bridgeports[bname][bport].get('db_status',0) == 0:
      logger.info('Port %s does not exist in the Database. Removing.' % bport)
      runCmd("ovs-vsctl del-port %s %s" % (bname,bport))
      bridgeports[bname][bport]['db_status'] = 1
