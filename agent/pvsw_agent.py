#!/usr/bin/python3

import json
import netifaces
import os
from shlex import split
import subprocess
from urllib import request, parse

def debug_print(msg):
    print(msg)

def getNewVLANS():
    return

def getOVSBridges():
    s = subprocess.Popen(["ovs-vsctl list-br"], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def getOVSPorts(bridge):
    s = subprocess.Popen(["ovs-vsctl list-ports " + bridge], shell=True, stdout=subprocess.PIPE).stdout
    return s.read().decode("utf-8").splitlines()

def getUndefinedVLANS():
    return

# Main execution loop

# These data structures hold both the OVS defined and DB defined data
# based on the values assigned
bridges = {}
bridgeports = {}

# Gather a list of vswitches on the device
ovsbridges = getOVSBridges()

for ovsbridge in ovsbridges:
    debug_print("Bridge: %s" % ovsbridge);
    bridges[ovsbridge] = {}
    bridgeports[ovsbridge] = {}
    bridges[ovsbridge]['local_status'] = 1;

    ovsports = getOVSPorts(ovsbridge)

    for ovsport in ovsports:
        debug_print("   Port: %s" % ovsport);
        bridgeports[ovsbridge][ovsport] = {}
        bridgeports[ovsbridge][ovsport]['local_status'] = 1;

# Connect to the webserver and request a list of vswitches
data = "json=getSwitches".encode("utf-8")
req = request.Request("http://127.0.0.1/post.php", data=data)
resp = request.urlopen(req).read().decode("utf-8")
dbswitches = json.loads(resp)
print(resp)
print(dbswitches)

# Connect to the webserver and request a list of vlan interfaces
for switch in dbswitches:
  print(switch['name'])
  data = "json=getVLANs&switch=%s" % switch['name']
  data = data.encode("utf-8")
  req = request.Request("http://127.0.0.1/post.php", data=data)
  resp = request.urlopen(req).read().decode("utf-8")
  dbports = json.loads(resp)
  print(resp)

  for dbport in dbports:
    dbbport = "%svl%s" % (dbport['switch_name'], dbport['vlan_id'])
    if (bridgeports[dbport['switch_name']].get(dbbport, None) == None):
        bridgeports[dbport['switch_name']][dbbport] = {}
    bridgeports[dbport['switch_name']][dbbport]['ip_address'] = dbport['ip_address']
    bridgeports[dbport['switch_name']][dbbport]['mask_length'] = dbport['mask_length']
    bridgeports[dbport['switch_name']][dbbport]['vlan_id'] = dbport['vlan_id']
    if (bridgeports[dbport['switch_name']][dbbport].get('local_status', None) == None):
        bridgeports[dbport['switch_name']][dbbport]['local_status'] = 0
    if bridgeports[dbport['switch_name']][dbbport]['local_status'] == 1:
      bridgeports[dbport['switch_name']][dbbport]['db_status'] = 3
    else:
      bridgeports[dbport['switch_name']][dbbport]['db_status'] = 2
    print("%svl%s" % (dbport['switch_name'], dbport['vlan_id']))

print(bridgeports)

# Create any missing internal ports
for bname in bridgeports:
    for bport in bridgeports[bname]:
        print(bport)
        if bridgeports[bname][bport].get('db_status',0) == 2:
          cmd = "ovs-vsctl add-port %s %s tag=%s -- set interface %s type=internal" % (bname,bport,bridgeports[bname][bport].get('vlan_id', 9999),bport)
          subprocess.call(split(cmd), stdout=open(os.devnull, "w"), stderr=subprocess.STDOUT)
          bridgeports[bname][bport]['db_status'] = 3

# Make sure IP addresses match up
for bname in bridgeports:
    for bport in bridgeports[bname]:
        if bridgeports[bname][bport].get('ip_address', None):
            if netifaces.ifaddresses(bport).get(netifaces.AF_INET, None):
              if bridgeports[bname][bport]['ip_address'] != netifaces.ifaddresses(bport)[netifaces.AF_INET][0]['addr']:
                print(bridgeports[bname][bport]['ip_address']);
            else:
                print(bridgeports[bname][bport]['ip_address'] + "aaa");
                cmd = "ip addr replace %s dev %s" % (bridgeports[bname][bport]['ip_address'] + "/" + bridgeports[bname][bport]['mask_length'], bport)
                subprocess.call(split(cmd), stdout=open(os.devnull, "w"), stderr=subprocess.STDOUT)

# Activate all interfaces that we agree upon (db_status = 3)
for bname in bridgeports:
  for bport in bridgeports[bname]:
    if bridgeports[bname][bport].get('db_status',0) == 3:
      cmd = "ip link set %s up" % bport
      subprocess.call(split(cmd), stdout=open(os.devnull, "w"), stderr=subprocess.STDOUT)
