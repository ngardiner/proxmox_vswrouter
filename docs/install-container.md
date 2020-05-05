# Container Installation

## Introduction

Container installation is the recommended installation method for this project. There are a few pros and cons which are listed below.

1. In Proxmox, select Create CT (container) on the Proxmox host that you would like to deploy the router. This container can be deployed as an unprivileged container.

2. Enter the hostname you'd like, and set a root password or SSH public key. Click Next.

3. Select Ubuntu 20.04 minimal as your OS template. You could potentially use an earlier version of Ubuntu LTS, but it hasn't been tested and may not work.

4. Specify 8GB as the disk size, and select the disk you'd like to deploy to.

5. Specify 1 CPU core and a memory limit of 512MB. This doesn't mean that the container will use 512MB of the host's memory, but simply that this is the upper bound of memory utilization for this container.

6. On the network tab, point eth0 to your Network Switch. In most cases, this will be <b>vmbr1</b>. Do not specify a MAC address, VLAN tag or any IP addresses. Clear the Firewall checkbox.

7. Accept the default settings for DNS. Click Next and confirm the container deployment.

Once complete, start the container. You will need to login using the Proxmox console as you have not configured an IP address for the container. Log in using the root user and the password you specified earlier.

## Configuration

1. Create a temporary VLAN interface to get connected to the network and download the required packages. An example of a command to do this is below:

```
ip link add link eth0 name eth0.28 type vlan id 28
ip addr change dev eth0.28 192.168.28.223/23
ip link set eth0.28 up
ip route add default gw 192.168.28.1
```

2. Update the packages on the container, and install the necessary tools for this project.

```
apt-get update
apt-get dist-upgrade
apt-get install apache2 git libapache2-mod-php7.4 openvswitch-switch openvswitch-vtep php7.4 php7.4-mbstring php7.4-sqlite python3
```

3. Clone the project git repository
```
cd /var/www/html
rm index.html
git clone https://github.com/ngardiner/proxmox_vswrouter ./
chown -R www-data:www-data /var/www/html
a2enmod php7.4
service apache2 restart
```

4. Access the WebUI

   * Log in using your web browser to the WebUI at http://<i>xxx.xxx.xxx.xxx</i> and configure your first vswitch. It should be a <b>VLAN Trunk</b> with the interface set to eth0.

5. (Temporary) Create the vswitch from the command line

This step is temporary for now, as this will be done automatically in the future. Create the bridge and add eth0 to it.
```
ovs-vsctl add-br vmbr1
ovs-vsctl add-port vmbr1 eth0
```

If you want to create Q-in-Q switches, you'll need to create those AFTER defining this in the webgui. Remember, this is very temporary.

```
ovs-vsctl add-br vmbr2
ovs-vsctl add-port vmbr2 vmbr2trunk -- set interface vmbr2trunk type=patch -- set interface vmbr2trunk options:peer=vmbr1vl901
ovs-vsctl add-port vmbr1 vmbr1vl901 -- set interface vmbr1vl901 type=patch -- set interface vmbr1vl901 options:peer=vmbr2trunk
```

6. Add an interface which covers your management IP using the web interface.

   * Remove the old interface using the ip link command:

```ip link del dev eth0.28```

7. Activate the cron script

```cp /var/www/html/docs/cron.conf /etc/cron.d/pvsw_agent```

   * Shortly after this, your machine should be online again
