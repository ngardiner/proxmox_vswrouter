# Proxmox vSWRouter

## What is it?

A web-based Virtual Router project primarily for Proxmox environments, suitable for creating lab networks easily via a web interface.

This project is **not** associated with the official Proxmox project, however it is very much intended to be complimentary in that it provides a soft router platform which is intended to be easily run as a lightweight Proxmox-friendly virtual router layer for Virtualized environments.

If this project goal interests you and you have further questions, feel free to read through the <a href="docs/faq.md">FAQ</a> which may cover your question in more detail.

Project Aim:
   * Provide a _lightweight_ router instance for Proxmox installs - no need for firmware or interfacing with a proprietary router solution. It can be built on <a href="docs/install-container.md">container</a>, <a href="docs/install-kvm.md">VM</a> or hardware devices.
   * Model functionality on the VMWare Workstation VMNet functionality - allow easy spinning up of networks, primarily for lab testing, but with richer functionality and a web interface.

## Introduction
The purpose of this project is to put together a particular network pattern useful for quickly spinning up and down virtual networks using more than one Proxmox host. This is to hopefully provide a similar ease of use to VMWare VMNet interfaces in VMWare workstation, but without the limitations.

To do this, we offer a simple web interface with the following features:

1. Add VLAN interfaces with a given IP address using OpenVSwitch internal interfaces
2. Setting a routing table per interface (if desired) and allowing or denying Internet access per interface

### Design
Implementing this project consists of a pair of OpenVSwitch switches, one (or more) on each Proxmox node in the cluster, and one (or more) within a VM. The Proxmox VMs trunk VLANs from their ovs switch to the VM, which acts as a router.

## Setup

The following steps will get you up and running:

1. Deploy an Ubuntu 20.04 Container or VM. The following configuration should be used:

   * VM CPU Cores: 1
   * RAM: Minimum 512MB
   * Network Interfaces: 2 interfaces
      * Interface 1: Management. Should be in VLAN 1 

You should deploy 2 Containers or VMs if you are running in HA mode. Keep in mind, HA mode needs to be running across two different Proxmox cluster nodes

2. Install the following packages:
```apt-get install apache2 libapache2-mod-php7.4 php7.4-mbstring php7.4-sqlite```

3. Clone this git repository under /var/www/html
```
cd /var/www/html
rm index.html
git clone https://github.com/ngardiner/proxmox_vswrouter .\
chown -R www-data:www-data /var/www/html
```

4. Enable PHP and restart apache
```
a2enmod php7.4
service apache2 restart
```

4. Access the web interface at ```http://[ip address]/```
5. Go to the settings tab. This is where you can configure the vswitches that will be used.

   * Your installation, whether Single or Double (Q-in-Q) VLAN tagged will have one VLAN Trunk. Create it first.
   * You are then able to create Q-in-Q switches and connect them to VLANs on the VLAN Trunk switch.

6. Enable the cron script which will configure all of the settings

```
cd /var/www/html
cp docs/cron.conf /etc/cron.d/pvsw_agent
```

## Current Status

   * New VLAN interfaces - implemented (non-HA only)
   * Delete bridge interfaces - implemented (non-HA only)
   * New bridge - not implemented
   * Delete bridge - not implemented
   * Define routing table - not implemented
   * Define routing table routes - not implemented
   * Enforce routing table - not implemented
   * Update incorrect IP address - implemented (non-HA only)
