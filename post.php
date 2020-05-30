<?php
  include_once("lib/ca_func.php");
  include_once("lib/database.php");

  if (isset($_POST['addRT'])) {
    if (! add_route_table($_POST['rtID'], $_POST['rtName'], $_POST['rtDesc'])) {
      header("Location: /?page=routes");
    }
  }
  if (isset($_POST['addSwitch'])) {
    add_switch($_POST['switchName'], $_POST['switchType'], $_POST['uplinkType'], $_POST['uplinkIface'], $_POST['uplinkSwitch'], $_POST['uplinkVlan']);
    header("Location: /?page=settings");
  }
  if (isset($_POST['addVPNCA'])) {
    # Create openssl private key
    $key = ca_make_key(2048);
    $subj = ca_set_subject($_POST['caCountry'], $_POST['caState'], $_POST['caOrg']);
    if (file_put_contents("/tmp/key.pem", $key) === strlen($key)) {
      ca_make_cacert("/tmp/key.pem", "/tmp/ca_cert.pem", $subj);
      $cert = file_get_contents("/tmp/ca_cert.pem");

      # Remove our temp files
      unlink("/tmp/ca_cert.pem");
      unlink("/tmp/key.pem");

      if (! add_vpn_ca($_POST['caName'], $key, $cert, $_POST['caCountry'], $_POST['caState'], $_POST['caOrg'])) {
        $redirect = "/?page=vpn";
        header("Location: $redirect");
      }
    } else {
      print("Write key to file failed.");
    }
  }
  if (isset($_POST['addVPNCert'])) {
    # Create openssl private key
    $key = ca_make_key(2048);

    if (file_put_contents("/tmp/svrkey.pem", $key) === strlen($key)) {
      $cacert = ca_get_cert($_POST['certCA']);
      file_put_contents("/tmp/cacert.pem", $cacert);
      $index = ca_get_index($_POST['certCA']);
      file_put_contents("/tmp/index.txt", $index);
      $serial = ca_get_serial($_POST['certCA']);
      file_put_contents("/tmp/serial", $serial);
      $cakey = ca_get_key($_POST['certCA']);
      file_put_contents("/tmp/cakey.pem", $cakey);

      # Create Server Certificate
      $svrcrt = ca_make_svrcert("/tmp/svrkey.pem", $_POST['certName']);

      # Remove CA files
      unlink("/tmp/cacert.pem");
      unlink("/tmp/cakey.pem");

      # Update CA database and serial
      $index = file_get_contents("/tmp/index.txt");
      ca_set_index($_POST['certCA'], $index);
      unlink("/tmp/index.txt");
      $serial = file_get_contents("/tmp/serial");
      ca_set_serial($_POST['certCA'], $serial);
      unlink("/tmp/serial");

      # Add VPN Certificate to list
      if (! add_vpn_cert($_POST['certName'], $_POST['certCA'], $svrcrt, $key)) {
        $redirect = "/?page=vpn";
        header("Location: $redirect");
      }

    }
  }
  if (isset($_POST['addVPNServer'])) {
    # Create diffie hellman dhparam file
    $dhparam = ca_make_dh(2048);

    if (! add_vpn_server($_POST['serverName'], $dhparam, $_POST['serverProto'], $_POST['serverPort'], $_POST['serverNetwork'], $_POST['serverMask'], $_POST['serverCA'], $_POST['serverDesc'])) {
      $redirect = "/?page=vpn";
      header("Location: $redirect");
    } else {
      ?>
      <h1>An error occurred</h1>
      <?php
    }
  }
  if (isset($_POST['delVPNCert'])) {
    if (! del_vpn_cert($_POST['certName'])) {
      $redirect = "/?page=vpn";
      header("Location: $redirect");
    } else {
      ?>
      <h1>An error occurred</h1>
      <?php
    }
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
