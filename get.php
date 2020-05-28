<?php

  include_once("database.php");

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

?>
