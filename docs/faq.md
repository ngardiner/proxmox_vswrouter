# Frequently Asked Questions

## Meta - The Project

### How is this related to Proxmox?

   * Very distantly! It's not a part of the project nor is it a component of the Proxmox part of the solution. In effect, it only has Proxmox in the name because it provides functionality that Proxmox doesn't have in the form of a flexible routing platform.

   * Yes, Proxmox uses OVS for flexible networking and yes, Proxmox lets you cofigure network interfaces such as internal ports from OVS bridges and patch ports using the Debian network configuration format.

   * Proxmox network configuration outside of this is lacking. What this project adds is:
      * The ability to define "external" network interfaces for VLANs trunked across the network so that a single gateway can provide the same routing functionality across all Proxmox hosts without the host configuration being modified (after initial setup, that is).
      * Web-based network configuration that doesn't require you to reboot after every network configuration change.
      * Support for iproute2 

### What are the areas where this project could be considered inflexible?

   * In order to keep this as simple as possible whilst providing the range of functionality we want, there are a few things that are deliberately kept simple. That said, no reason you can't submit a PR to customize them, but I don't see it happening as part of the core implementation anytime soon:
      * Interface names are as they are. Given the agent goes in and auto-adds and removes interfaces on a daily basis, there is little room for them to 
      * You *must* define any custom bridge configurations (ie interfaces you add) as reserved, otherwise they *will* be deleted by the agent. The policy-based nature of the implementation is that the end state matches what you have defined in the GUI.

### What other projects provide routing engines for Virtualized environments such as Proxmox?

Below is a list of all of the projects I looked at before I decided to spend the time doing this. Each one has a quick synopsis and an honest evaluation of why I don't just use it.

   * pfSense/OPNsense
      * pfSense is a BSD-based Network Security platform. It is a great project, with a lot of useful features such as DNS server, DHCP server and VPN built-in.
      * pfSense does support Q-in-Q VLANs as Layer 3 interfaces, but does not provide Layer 2 functionality such as nested switches as we do, meaning you need to manage Inner and Outer tags per interface. 
      * Ultimately, pfSense as a Network Security device is too much overhead for simply providing L3 routing for VLANs.
   * OpenWRT/Lede
      * OpenWRT is the closest thing to what I have been looking for, and 
      * OpenWRT is minimal, moreso than even this project. It's derived from a squashfs image smaller than our minimal LXC template.
      * OpenWRT does not provide **any** soft switching as a base installation. It is intended as a hardware router platform. Adding soft switching so that features such as gre and vxlan tunnels require additional software installed, and frankly quite a bit of hacking around to make it fit within the UCI config structure.
   * VyOS
      * VyOS is a versatile routing platform with many features. 
      * There is no web interface for VyOS, making it good for specialised router uses but not good for this particular function.
      * While there are some crowbar-ed in container versions of VyOS, there is no officially maintained containerised version and <a href="https://forum.vyos.io/t/official-docker-image/808">no plans for one</a>.

## High Availability

### It looks like I need a complex High Availability setup which involves Corosync and Pacemaker. Is this true?

   * Yes and no. The Corosync and Pacemaker HA mode is one mode of providing High Availability. It **does** add complexity to the build, however it also provides a more "traditional" style of network HA which might be preferred:
      * The Corosync/Pacemaker method of HA involves assigning each of the 2 nodes (we limit this to 2 nodes for simplicity's sake) an IP address on every segment, on top of the 
      * This allows us to run the environment active-active if we would like (although this is not yet implemented).

## VLAN and Q-in-Q

## Routing
