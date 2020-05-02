# Advanced Configuration - HA

## Introduction

This engine does (reluctantly) support 2-node clustering of vSW-Router instances with a number of caveats. This is only because I already had a router set up this way. I really don't recommend it due to the added complexity, but it does work.

## Setup

### Installing Corosync and Pacemaker

apt-get install corosync pacemaker

### Configure Corosync

You can choose to work with the default settings for Corosync, or you can customize. One common customization is to set at least ring0 as a dedicated VLAN between the router instances to provide separation between Corosync ring and data network.

Make sure that if you do this, you mark the VLAN as reserved in the UI under the Switches tab. If you don't, you may end up deploying onto that VLAN and breaking clustering.

Because this is a 2-node cluster, you need this config under the quorum section:

```
quorum {
        provider: corosync_votequorum
        two_node: 1
        wait_for_all: 0
}
```

What does this do?

   * It enables two-node mode, which requires only one vote for quorum. If this is not set, the cluster would go down every time a single node was down.
   * It does not require waiting until both nodes are online before the services first start. If you bring just one node up, it will continue to operate.

### Verifying Pacemaker Cluster Status

Run the following command:

```pcs status```

And check that the cluster is quorate. If so, it can be used for Virtual IPs. Note that once you turn on Cluster HA mode, all interfaces added must be HA, there's no switching per-VLAN. 

### Enabling HA mode

   * On the Settings screen, change your Installation Type to Advanced.
   * Tick the Enable High Availability box, and select Corosync + Pacemaker as your HA mode.
   * Click Save Settings
