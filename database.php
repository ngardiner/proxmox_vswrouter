<?php

# Check database exists
if (! file_exists("database.db3")) {
  $dbh = new PDO("sqlite:database.db3");

  $sth = $dbh->prepare("CREATE TABLE settings (setting varchar(24), value varchar(64), primary key(setting))");
  $sth->execute();
  $sth = $dbh->prepare("CREATE TABLE switches (name varchar(12), type int(1), uplink_type int(1), uplink_interface varchar(12), uplink_vlan int, primary key(name))");
  $sth->execute();
  $sth = $dbh->prepare("INSERT INTO settings (setting, value) VALUES (:setting, :value)");
  $sth->execute(array(':setting' => "db_version", ':value' => "1"));
  $sth->execute(array(':setting' => "install_type", ':value' => "simple"));
  $sth = $dbh->prepare("CREATE TABLE route_tables (rt_name varchar(12), rt_id int, rt_desc tinytext, primary key(rt_name))");
  $sth->execute();
  $sth = $dbh->prepare("INSERT INTO route_tables (rt_name, rt_id, rt_desc) VALUES (:rt_name, :rt_id, :rt_desc)");
  $sth->execute(array(':rt_name' => "Default", ':rt_id' => 253, ':rt_desc' => "Operating System Route Table"));
  $sth = $dbh->prepare("CREATE TABLE switch_vlans (switch_name varchar(12), vlan_id int, ip_address varchar(15), mask_length int, rt_table int, desc varchar(255), primary key(switch_name, vlan_id))");
  $sth->execute();
} else {
  # Check database version
  if (get_setting("db_version") < 1) {
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

function get_rt_tables() {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT * FROM route_tables");
  if ($sth) {
    $sth->execute();
    return $sth->fetchAll();
  } else {
    return 0;
  }
}

function get_setting($setting) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("SELECT value FROM settings WHERE setting = :setting");
  $sth->execute(array(':setting' => $setting));
  $res = $sth->fetch();
  return $res['value'];
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

function set_setting($setting, $value) {
  $dbh = new PDO("sqlite:database.db3");
  $sth = $dbh->prepare("REPLACE INTO settings (setting, value) VALUES (:setting, :value)");
  $sth->execute(array(':setting' => $setting, ':value' => $value));
  return 0;
}
?>
