<?php
  function listInterfaces($spec) {
    $contents = file("/proc/net/dev");

    $lineno = 0;
    $ifs = Array();
    foreach($contents as $line) {
      if ($lineno < 2) { 
        $lineno++;
	continue; 
      }
      $if = explode(":", $line)[0];
      $if = str_replace(" ", "", $if);
      if (($spec == "switch" && preg_match("/^vmbr\d+$/", $if)) ||
          ($spec == "physical" && preg_match("/^enp/", $if))    ||
	  ($spec == "physical" && preg_match("/^eth/", $if))) {
        $ifs[] = $if;
      }
    }
    sort($ifs);
    return $ifs;
  }
?>
