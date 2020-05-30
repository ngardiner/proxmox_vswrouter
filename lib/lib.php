<?php
  function switchtype($type) {
    if ($type == "1") {
      return "Q-in-Q";
    } elseif ($type == "2") {
      return "VLAN Trunk";
    }
  }

