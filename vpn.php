<?php
  include_once("database.php");
  include_once("lib.php");
?>
<table class="table">
  <tr>
    <th colspan=6>VPN Servers</th>
  </tr>
  <tr>
    <th>Name</th>
    <th>Protocol</th>
    <th>Port</th>
    <th>Network</th>
    <th>Description</th>
    <th>Download</th>
    <th>Action</th>
  </tr>
  <?php
    $servers = get_vpn_server();
    foreach ($servers as $server) {
      print "<tr>";
      print "<td>".$server['name']."</td>";
      print "<td>".$server['proto']."</td>";
      print "<td>".$server['port']."</td>";
      print "<td>".$server['network']."/".$server['mask']."</td>";
      print "<td>".$server['desc']."</td>";
      print "<td><a href='get.php?action=getDHP&server=".$server['name']."'>dhparam</a> | ";
      print "<a href='#'>Server Config</a> | ";
      print "<a href='#'>Client Config</a></td>";
      print "<td>Delete</td>";
      print "</tr>";
    }
  ?>
</table>

<form action="post.php" method=POST>
  Server Name: <input name="serverName" size=10 /> Protocol:
  <select name="serverProto">
    <option value="tcp">TCP</option>
    <option value="udp">UDP</option>
  </select>
  Port <input name="serverPort" size=4 />
  VPN Network <input name="serverNetwork" size=11 />
  Mask Length <input name="serverMask" size=1 />
  CA: <select name="serverCA">
    <?php
      $cas = get_vpn_ca();
      foreach ($cas as $ca) {
        print "<option value='".$ca['name']."'>".$ca['name']."</option>";
      }
    ?>
  </select>
  Description <input name="serverDesc" size=60 />
  <input type=submit name="addVPNServer" value="Add Server" />
</form>

<table class="table">
  <tr>
    <th>VPN Clients</th>
  </tr>
</table>

Client Name <input> Protocol
<select>
</select>
Host <input>

<table class="table">
  <tr>
    <th colspan=4>Certificate Authorities</th>
  </tr>
  <tr>
    <th>Name</th>
    <th>Country</th>
    <th>State</th>
    <th>Organisation</th>
    <th>Download</th>
  </tr>
  <?php
    $cas = get_vpn_ca();
    foreach ($cas as $ca) {
      print "<tr>";
      print "<td>".$ca['name']."</td>";
      print "<td>".$ca['country']."</td>";
      print "<td>".$ca['state']."</td>";
      print "<td>".$ca['org']."</td>";
      print "<td><a href='get.php?action=getCACert&ca=".$ca['name']."'>CA Cert</a></td>";
      print "</tr>";
    }
  ?>
</table>

<form action="post.php" method=POST>
  CA Nickname: <input name="caName" size=8 /> Country 
  <select name="caCountry">
    <option value="AU">Australia</option>
  </select> 
  State <input name="caState" size=2 />
  Organization <input name="caOrg" size=8 />
  <input type=submit name="addVPNCA" value="Add New CA" />
</form>

<table class="table">
  <tr>
    <th colspan=4>VPN Certificates</th>
  </tr>
  <tr>
    <th>Name</th>
    <th>CA Name</th>
    <th>Download</th>
    <th>Action</th>
  </tr>
  <?php
    $certs = get_vpn_certs();
    foreach ($certs as $cert) {
      print "<tr>";
      print "<td>".$cert['name']."</td>";
      print "<td>".$cert['ca']."</td>";
      print "<td>Cert | Key</td>";
      ?>
      <form action="post.php" method=POST>
        <input type=hidden name="certName" value="<?php print $cert['name']; ?>" />
        <td><input type=submit name="delVPNCert" value="Delete" /></td>
      </form>
      <?php
      print "</tr>";
    }
  ?>
</table>

<form action="post.php" method=POST>
  Cert Name: <input name="certName" size=8 />
  CA:
  <select name="certCA">
    <?php
      $cas = get_vpn_ca();
      foreach ($cas as $ca) {
        print "<option value='".$ca['name']."'>".$ca['name']."</option>";
      }
    ?>
  </select>
  <input type=submit name="addVPNCert" value="Add New Cert" />
</form>
