# Proxmox vSWRouter
A Virtual Router for Proxmox suitable for creating lab networks easily via a web interface.

## Introduction
The purpose of this project is to put together a particular network pattern useful for quickly spinning up and down virtual networks

### Design
This project consists of a pair of OpenVSwitch switches, one (or more) on each Proxmox node in the cluster, and one (or more) within a VM. The Proxmox VMs trunk VLANs from their ovs switch to the VM, which acts as a router. It will terminate the 

### Pros and Cons
This same mechanism could easily be performed just by spinning up a VLAN each time, however:

   * This generally requires configuring VLAN trunking across a network, and mixes virtual VLANs with 
   * Note that single VLAN mode is intended to be supported by this project as well, but it will only work in flat, non-VLAN aware networks.
   
Cons:

   * Q-in-Q VLAN trunking adds overhead (4 bytes in the header)
   
## Setup

The following steps will get you up and running:

1. Deploy an Ubuntu 20.04 VM. 
2. Install the following packages:
3. Clone this git repository under /var/www/html
