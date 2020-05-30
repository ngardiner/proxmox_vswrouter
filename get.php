<?php

  include_once("lib/database.php");

  if ($_GET['action'] == "getCACert") {
    $cert = get_vpn_ca_cert($_GET['ca']);
    header("Content-Type: application/x-pem-file");
    header('Content-Disposition: attachment; filename="'.$_GET['ca'].'.pem"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($cert));
    print($cert);
  }
  if ($_GET['action'] == "getDHP") {
    $cert = get_vpn_server_dh($_GET['server']);
    $name_file = str_replace(" ", "_", $_GET['server']);
    header("Content-Type: application/x-pem-file");
    header('Content-Disposition: attachment; filename=dh_'.$name_file.'.pem');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($cert));
    print($cert);
  }
  if ($_GET['action'] == "getVPNServerConfig") {
#CREATE TABLE vpn_server (name varchar(24), dhparam varchar(8192), proto varchar(3), port int, network varchar(15), mask int, ca_name varchar(24), desc varchar(128), primary key(name));
    header("Content-Type: text/plain");
    $config = get_vpn_server_name($_GET['name']);
    $name_file = str_replace(" ", "_", $_GET['name']);
    print "port ".$config['port']."\n";
    print "proto ".strtolower($config['proto'])."\n";
    print "dev tap0\n"; # This needs to be updated to point to a device
    print "ca ".$config['ca_name'].".pem\n";
    print "cert server.crt\n";
    print "key server.key\n";
    print "dh dh-".$name_file.".pem\n";
    print "topology subnet\n";
    print "server ".$config['network']." 255.255.255.0\n";
    print ";push \"route 192.168.10.0 255.255.255.0\"\n";
    print ";push \"route 192.168.20.0 255.255.255.0\"\n";
    print "keepalive 10 120\n";
    print "cipher AES-256-CBC\n";
    print "max-clients 100\n";
    print "user nobody\n";
    print "group nobody\n";
    print "persist-key\n";
    print "persist-tun\n";
    print "status /tmp/openvpn-".$name_file."-status.log\n";
    print "explicit-exit-notify 1\n";
  }

?>
