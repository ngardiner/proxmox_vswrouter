<?php
  include_once("database.php");

  if ($_POST['saveSettings']) {
    set_setting("install_type", $_POST['install_type']);
    header("Location: /?page=settings");
  }

  if ($_POST['addSwitch']) {
    add_switch($_POST['switchName'], $_POST['switchType'], $_POST['uplinkType'], $_POST['uplinkIface'], $_POST['uplinkSwitch'], $_POST['uplinkVlan']);
    header("Location: /?page=settings");
  }
?>
