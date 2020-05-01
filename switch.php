<?php
  include_once("database.php");
  include_once("lib.php");
?>
<table class="table">
<?php
  $switches = get_switches();
  $rt = get_rt_tables();
  foreach ($switches as $switch) {
    print "<tr><th>".$switch[0]."</th><th>&nbsp</th><th>&nbsp;</th>";
    print "<td>".switchtype($switch[1])."</td></tr>";
    $vlans = get_switch_vlans($switch[0]);
    if ($vlans) {
      print "<tr><th>&nbsp;</th><th>VLAN ID</th><th>IP Address</th>";
      print "<th>Route Table</th><th>Description</th></tr>";
      foreach ($vlans as $vlan) {
        print "<tr><td>&nbsp;</td><td>".$vlan[1]."</td>";
        print "<td>".$vlan[2]."/".$vlan[3]."</td>";
        print "<td>".$vlan[4]."</td><td>".$vlan[5]."</td></tr>";
      }
    }
    print "<tr><td colspan='5'><form action=post.php method=POST>";
    print "Add VLAN (1-4096): <input size='2' name='vlanID' /> IP Address: <input size='13' name='ipAddress' /> ";
    print "Mask Length <input size = '1' name='maskLength'/> Route Table: <select name='rtTable'>";
    foreach ($rt as $rte) {
      print "<option value='".$rte[1]."'>".$rte[0]."</option>";
    }
    print "</select> ";
    print "Description: <input name=vlanDesc size=100 />";
    print "<input type=hidden name=switchName value='".$switch[0]."' />";
    print "<input type=submit name=addVlan value='Go'></form></td></tr>";
  }
?>
</table>
