<?php
  include_once("database.php");
  include_once("lib.php");
?>
<table class="table">
  <?php
  $rt = get_rt_tables();
  foreach ($rt as $table) {
    print "<tr><th>".$table[0]."</th><td>ID ".$table[1]."</td><td>".$table[2]."</th><td><input type=Submit value='Delete' /></td></tr>";
    if ($table[0] == "Default") {
      print "<tr><td>&nbsp;</td><td><i>The default route table cannot be managed from the web interface, as it is the OS routing table (check netstat -r)</i></td></tr>";
    } else {
      $routes = get_rt_routes($table[1]);
      foreach ($routes as $route) {
        print "<tr><td>&nbsp;</td><td>".$route[1]."/".$route[2]."</td>";
	print "<td>".$route[3]."</td>";
	print "<td><input type=Submit value='Delete' /></td></tr>";
      }
    }
  }
?>
</table>
<form action="post.php" method=POST>
  <table>
    <tr><th>Route Table Name</th><td><input name="rtName" /></td></tr>
    <tr><th>Route Table ID (2-252)</th><td><input size=3 name="rtID" /></td></tr>
    <tr><th>Route Table Description</th><td><input size=40 name="rtDesc" /></td></tr>
    <tr><td><input type=submit value="Add Route Table" name="addRT" /></td></tr>
  </table>
</form>
