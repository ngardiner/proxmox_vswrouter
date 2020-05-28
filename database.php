<?php

# Check database exists
if (! file_exists("database.db3")) {
  $dbh = new PDO("sqlite:database.db3");

  $sth = $dbh->prepare("CREATE TABLE settings (setting varchar(24), value varchar(64), primary key(setting))");
  $sth->execute();
  $sth = $dbh->prepare("CREATE TABLE switches (name varchar(12), type int(1), uplink_type int(1), uplink_interface varchar(12), uplink_vlan int, primary key(name))");
  $sth->execute();
  $sth = $dbh->prepare("INSERT INTO settings (setting, value) VALUES (:setting, :value)");
  $sth->execute(array(':setting' => "db_version", ':value' => "2"));
  $sth->execute(array(':setting' => "install_type", ':value' => "simple"));
  $sth = $dbh->prepare("CREATE TABLE route_tables (rt_name varchar(12), rt_id int, rt_desc tinytext, primary key(rt_name))");
  $sth->execute();
  $sth = $dbh->prepare("INSERT INTO route_tables (rt_name, rt_id, rt_desc) VALUES (:rt_name, :rt_id, :rt_desc)");
  $sth->execute(array(':rt_name' => "Default", ':rt_id' => 253, ':rt_desc' => "Operating System Route Table"));
  $sth = $dbh->prepare("CREATE TABLE routes (rt_id int, dst_ip varchar(15), dst_mask varchar(15), rt_desc tinytext, primary key(rt_id, dst_ip, dst_mask))");
  $sth->execute();
  $sth = $dbh->prepare("CREATE TABLE switch_vlans (switch_name varchar(12), vlan_id int, ip_address varchar(15), mask_length int, rt_table int, desc varchar(255), ha_ipa int, ha_ipb int, primary key(switch_name, vlan_id))");
  $sth->execute();
} else {
  # Check database version
  if (get_setting("db_version") < 2) {
    $dbh = new PDO("sqlite:database.db3");
    $sth = $dbh->prepare("ALTER TABLE switch_vlans ADD ha_ipa int");
    $sth->execute();
    $sth = $dbh->prepare("ALTER TABLE switch_vlans ADD ha_ipb int");
    $sth->execute();
    $sth = $dbh->prepare("UPDATE settings SET value = '2' where setting = 'db_version'");
    $sth->execute();
    $sth = $dbh->prepare("CREATE TABLE routes (rt_id, dst_ip, dst_mask, rt_desc tinytext, primary key(rt_id, dst_ip, dst_mask))");
    $sth->execute();
    $sth = $dbh->prepare("CREATE TABLE vpn_cert_ca (name varchar(24), key varchar(2048), cert varchar(2048), country varchar(2), state varchar(10), org varchar(24), certdb varchar(16384) DEFAULT \"\", serial varchar(4) DEFAULT \"00\", primary key(name))");
    $sth->execute();
    $sth = $dbh->prepare("CREATE TABLE vpn_cert (name varchar(24), ca varchar(24), cert varchar(4096), key varchar(4096), primary key (name, ca))");
    $sth->execute();
    $sth = $dbh->prepare("CREATE TABLE vpn_server (name varchar(24), dhparam varchar(8192), proto varchar(3), port int, network varchar(15), mask int, ca_name varchar(24), desc varchar(128), primary key(name))");
    $sth->execute();
  }
  if (get_setting("db_version") < 3) {
    # Next schema update goes here
	  # $dbh = new PDO("sqlite:database.db3");
	  # $sth = $dbh->prepare("UPDATE settings SET value = '2' where setting = 'db_version'");
	  # $sth->execute();
  }
}

function add_switch($name, $type, $uplink_type, $iface, $switch, $vlan) {
  $uplink_iface = "";
  $uplink_vlan = 0;
  if ($uplink_type == "Interface") { 
    $uplink_type = 1; 
    $uplink_iface = $iface;
  }
  if ($uplink_type == "PatchPort") { 
    $uplink_type = 2; 
    $uplink_iface = $switch;
    $uplink_vlan = $vlan;
  }
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO switches (name, type, uplink_type, uplink_interface, uplink_vlan) VALUES (:name, :type, :uplink_type, :uplink_interface, :uplink_vlan)");
  $sth->execute(array(':name' => $name, ':type' => $type, ':uplink_type' => $uplink_type, ':uplink_interface' => $uplink_iface, ':uplink_vlan' => $uplink_vlan));
  return 0;
}

function add_route_table($rtId, $rtName, $rtDesc) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO route_tables (rt_name, rt_id, rt_desc) VALUES (:rt_name, :rt_id, :rt_desc)");
  if ($sth) {
    $sth->execute(array('rt_name' => $rtName, 'rt_id' => $rtId, 'rt_desc' => $rtDesc));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }

}

function add_vlan($switchName, $vlanID, $ipAddress, $maskLength, $rtTable, $vlanDesc) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO switch_vlans (switch_name, vlan_id, ip_address, mask_length, rt_table, desc) VALUES (:switch_name, :vlan_id, :ip_address, :mask_length, :rt_table, :desc)");
  if ($sth) {
    $sth->execute(array(':switch_name' => $switchName, ':vlan_id' => $vlanID, ':ip_address' => $ipAddress, ':mask_length' => $maskLength, ':rt_table' => $rtTable, ':desc' => $vlanDesc));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}

function add_vpn_ca($name, $key, $cert, $country, $state, $org) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO vpn_cert_ca (name, key, cert, country, state, org) VALUES (:name, :key, :cert, :country, :state, :org)");
  if ($sth) {
    $sth->execute(array(':name' => $name, ':key' => $key, ':cert' => $cert, ':country' => $country, ':state' => $state, ':org' => $org));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}

function add_vpn_cert($name, $ca, $cert, $key) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO vpn_cert (name, ca, cert, key) VALUES (:name, :ca, :cert, :key)");
  if ($sth) {
    $sth->execute(array(':name' => $name, ':ca' => $ca, ':cert' => $cert, ':key' => $key));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}

function add_vpn_server($name, $dhparam, $proto, $port, $network, $mask, $ca, $desc) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("INSERT INTO vpn_server (name, dhparam, proto, port, network, mask, ca_name, desc) values (:name, :dhparam, :proto, :port, :network, :mask, :ca_name, :desc)");
  if ($sth) {
    $sth->execute(array(':name' => $name, ':dhparam' => $dhparam, ':proto' => $proto, ':port' => $port, ':network' => $network, ':mask' => $mask, ':ca_name' => $ca, ':desc' => $desc));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}

function ca_get_cert($name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT cert FROM vpn_cert_ca WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name));
    return $sth->fetch()[0];
  }
}

function ca_get_index($name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT certdb FROM vpn_cert_ca WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name));
    return $sth->fetch()[0];
  }
}

function ca_get_key($name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT key FROM vpn_cert_ca WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name));
    return $sth->fetch()[0];
  }
}

function ca_get_serial($name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT serial FROM vpn_cert_ca WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name));
    return $sth->fetch()[0];
  }
}

function ca_set_index($name, $value) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("UPDATE vpn_cert_ca SET certdb = :certdb WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name, ':certdb' => $value));
    return 0;
  } else {
    return 1;
  }
}

function ca_set_serial($name, $value) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("UPDATE vpn_cert_ca SET serial = :serial WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $name, ':serial' => $value));
    return 0;
  } else {
    return 1;
  }
}

function del_vlan($switch, $vlan) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("DELETE FROM switch_vlans WHERE switch_name = :switch_name AND vlan_id = :vlan_id");
  if ($sth) {
    $sth->execute(array(':switch_name' => $switch, ':vlan_id' => $vlan));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}         

function del_vpn_cert($cert_name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("DELETE FROM vpn_cert WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $cert_name));
    return 0;
  } else {
    print_r($dbh->errorInfo());
    return 1;
  }
}

function get_checked($setting, $value) {
  # Mark a selectbox option if the setting matches
   if (get_setting($setting) == $value) {
       print "checked";
   }
}

function get_rt_table_name($rt_id) {
  if ($rt_id == "99999") {
    return "<i>Reserved</i>";
  }
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT rt_name FROM route_tables WHERE rt_id = :rt_id");
  if ($sth) {
    $sth->execute(array(':rt_id' => $rt_id));
    return $sth->fetch()[0];
  } else {
    return "";
  }

}

function get_rt_routes($rt_id) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM routes WHERE rt_id = :rt_id ORDER BY dst_ip, dst_mask ASC");
  if ($sth) {
    $sth->execute(array(':rt_id' => $rt_id));
    return $sth->fetchAll();
  } else {
    return 0;
  }
}

function get_rt_tables() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM route_tables ORDER BY rt_id ASC");
  if ($sth) {
    $sth->execute();
    return $sth->fetchAll();
  } else {
    return 0;
  }
}

function get_select($setting, $value) {
  # Mark a selectbox option if the setting matches
  if (get_setting($setting) == $value) {
    print "selected";
  }
}

function get_setting($setting) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT value FROM settings WHERE setting = :setting");
  $sth->execute(array(':setting' => $setting));
  $res = $sth->fetch();
  if ($res) {
    return $res['value'];
  } else {
    return;
  }
}

function get_settings() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM settings");
  if ($sth) {
    $sth->execute();
  } else {
    return;
  }
  $res = $sth->fetchAll();
  if ($res) {
    return $res;
  } else {
    return;
  }
}

function get_switches() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM switches ORDER BY name ASC");
  if ($sth) {
    $sth->execute();
    return $sth->fetchAll();
  } else {
    return;
  }
}

function get_switch_vlans($switchname) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM switch_vlans WHERE switch_name = :switchname ORDER BY vlan_id ASC");
  $sth->execute(array(':switchname' => $switchname));
  return $sth->fetchAll();
}

function get_vpn_ca() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM vpn_cert_ca");
  $sth->execute();
  return $sth->fetchAll();
}

function get_vpn_ca_cert($ca_name) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT cert FROM vpn_cert_ca WHERE name = :name");
  if ($sth) {
    $sth->execute(array(':name' => $ca_name));
    return $sth->fetch()[0];
  }
}

function get_vpn_certs() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM vpn_cert");
  if ($sth) {
    $sth->execute();
    return $sth->fetchAll();
  } else {
    return "<b><font color='red'>Error: Database Query Failed</font></b>";
  }
}

function get_vpn_server() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM vpn_server");
  $sth->execute();
  return $sth->fetchAll();
}

function set_setting($setting, $value) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("REPLACE INTO settings (setting, value) VALUES (:setting, :value)");
  $sth->execute(array(':setting' => $setting, ':value' => $value));
  return 0;
}
?>
