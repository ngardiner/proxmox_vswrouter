<?php
  include_once("database.php");
  include_once("lib.php");
?>
<table class="table">
<?php
  $switches = get_switches();
  $rt = get_rt_tables();
  foreach ($switches as $switch) {
    print "<tr><th>".$switch[0]."</th>";
    print "<td>".switchtype($switch[1])."</td></tr>";
    print "<tr><td>Add VLAN: <input size='2'/> IP Address: <input size='13' />";
    print "Mask Length <input size = '1'/> Route Table: <select>";
    foreach ($rt as $rte) {
      print "<option value='".$rte[1]."'>".$rte[0]."</option>";
    }
    print "</select>";
    print "<input type=submit value='Go'></td></tr>";
  }
?>
</table>
