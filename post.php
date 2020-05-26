<?php
  include_once("database.php");

  if (isset($_POST['addRT'])) {
    if (! add_route_table($_POST['rtID'], $_POST['rtName'], $_POST['rtDesc'])) {
      header("Location: /?page=routes");
    }
  }
  if (isset($_POST['addSwitch'])) {
    add_switch($_POST['switchName'], $_POST['switchType'], $_POST['uplinkType'], $_POST['uplinkIface'], $_POST['uplinkSwitch'], $_POST['uplinkVlan']);
    header("Location: /?page=settings");
  }

  if (isset($_POST['delVlan'])) {
    if (! del_vlan($_POST['switchId'], $_POST['vlanId'])) {
      $redirect = "/?page=switch&anchor=" . $_POST['switchId'];
      header("Location: $redirect");
    } else {
      ?>
      <h1>An error occurred</h1>
      <?php
    }
  }

  if (isset($_POST['addVlan'])) {
    if (! add_vlan($_POST['switchName'], $_POST['vlanID'], $_POST['ipAddress'], $_POST['maskLength'], $_POST['rtTable'], $_POST['vlanDesc'])) {
      $redirect = "/?page=switch&anchor=" . $_POST['switchName'];
      header("Location: $redirect");
      print "Redirecting to $redirect...";
    } else {
      ?>
      <h1>An error occurred</h1>
      <?php
    }
  }

  if (isset($_POST['saveHASettings'])) {
    if ($_POST['ha_enable'] == "on") {
      set_setting("ha_enable", "1");
    } else {
      set_setting("ha_enable", "0");
    }
    set_setting("ha_mode", $_POST['ha_mode']);
    header("Location: /?page=settings");
  }

  if (isset($_POST['saveSettings'])) {
    set_setting("install_type", $_POST['install_type']);
    header("Location: /?page=settings");
  }

  if (isset($_POST['json'])) {
    if ($_POST['json'] == "getVLANs") {
      $vlans = get_switch_vlans($_POST['switch']);
      print json_encode($vlans);
      return;
    } elseif ($_POST['json'] == "getSettings") {
      $settings = get_settings();
      print json_encode($settings);
      return;
    } elseif ($_POST['json'] == "getSwitches") {
      $switches = get_switches();
      print json_encode($switches);
      return;
    } elseif ($_POST['json'] == "getRouteTables") {
      $rt = get_rt_tables();
      print json_encode($rt);
      return;
    }
  }
?>
