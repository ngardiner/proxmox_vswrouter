# Proxmox vSWRouter
A Virtual Router for Proxmox suitable for creating lab networks easily via a web interface.

## Introduction
The purpose of this project is to put together a particular network pattern useful for quickly spinning up and down virtual networks using more than one Proxmox host. This is to hopefully provide a similar ease of use to VMWare VMNet interfaces in VMWare workstation, but without the limitations.

To do this, we offer a simple web interface with the following features:

1. Add VLAN interfaces with a given IP address using OpenVSwitch internal interfaces
2. Setting a routing table per interface (if desired) and allowing or denying Internet access per interface

### Design
Implementing this project consists of a pair of OpenVSwitch switches, one (or more) on each Proxmox node in the cluster, and one (or more) within a VM. The Proxmox VMs trunk VLANs from their ovs switch to the VM, which acts as a router. It will terminate the 

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
   ```apt-get install apache2 libapache2-mod-php7.4 php7.4-sqlite```

3. Clone this git repository under /var/www/html
git clone
4. Access the web interface
5. Go to the settings tab. This is where you can configure the vswitches that will be used.
