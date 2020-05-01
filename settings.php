<?php
  include_once("database.php");
  include_once("lib.php");
  include_once("network.php");

  function uplinktype($type) {
    if ($type == "1") {
      return "Interface";
    } elseif ($type == "2") {
      return "PatchPort";
    }
  }
?>
<p><h2>General</h2></p>
<form action="post.php" method=POST>
<table class="table">
  <tr>
    <th>Installation Type</th>
    <td>
      <select name="install_type">
        <option value="simple">Simple</option>
        <option value="advanced">Advanced</option>
      </select>
    </td>
    <td><font size=-1>Installation Type. For simple installations, we won't show complex configuration.</font></td>
  </tr>
  <tr>
    <td>
      <input type=submit name="saveSettings" value="Save Settings">
    </td>
  </tr>
</table>
</form>

<p><h2>Switches</h2></p>
<table class="table">
 <tr>
   <th>Switch Name</th>
   <th>Switch Type</th>
   <th>Uplink Type</th>
   <th>Uplink Interface</th>
   <th>Uplink VLAN</th>
  </tr>
<?php
  $switches = get_switches();
  foreach ($switches as $switch) {
    print "<tr><th>".$switch[0]."</th>";
    print "<td>".switchtype($switch[1])."</td>";
    print "<td>".uplinktype($switch[2])."</td>";
    print "<td>".$switch[3]."</td>";
    print "<td>".$switch[4]."</td></tr>";
  }
?>
</table>

<form action="post.php" method=POST>
<table class="table">
  <tr>
    <th>Switch Name</th>
    <td><input name="switchName" size=10></td>
    <td><font size=-1>The name of the virtual switch, eg. vmbr1</font></td>
  </tr>
  <tr>
    <th>Switch Type</th>
    <td>
      <select title="Switch Type" name="switchType" class="selectpicker">
        <option value="1">Q-in-Q Trunk</option>
	<option value="2">VLAN Trunk</option>
        <option value="3">Unmanaged VLAN Trunk</option>
      </select>
    </td>
    <td><font size=-1>A VLAN Switch uses a single VLAN across the network whereas a Q-in-Q switch trunks VLANs 1-4096 over another VLAN ID. All installations need 1 VLAN Trunk switch, but you cannot have more than 1 VLAN Trunk switch in most networks.</font></td>
  </tr>
  <tr>
    <th>Switch Uplink</th>
    <td>
      <select title="Type" name="uplinkType" id="uplinkType" class="selectpicker">
        <option value="Interface">Interface</option>
        <option value="PatchPort">PatchPort</option>
      </select>
      <span id="Interface">
      <select name="uplinkIface" class="selectpicker">
        <?php
          $ifs = listInterfaces("physical");
          foreach ($ifs as $if) {
            print "<option>".$if."</option>";
          }
        ?>
      </select>
      </span>
      <span id="PatchPort">
      <select name="uplinkSwitch" class="selectpicker">
        <?php
          $ifs = listInterfaces("switch");
          foreach ($ifs as $if) {
            print "<option>".$if."</option>";
          }
        ?>
        <input size="2" value="1" name="uplinkVlan" />
      </select>
      </span>
      <script language="JavaScript">
      $(function() {
        $('#Interface').show(); 
	$('#PatchPort').hide();
        $('#uplinkType').change(function(){
          if($('#uplinkType').val() == 'Interface') {
            $('#Interface').show(); 
	    $('#PatchPort').hide();
          } else {
            $('#Interface').hide(); 
	    $('#PatchPort').show();
          }});
        });
      </script>
    </td>
    <td><font size=-1>The port that the switch uses as an uplink. VLAN switches normally use Interface, Q-in-Q switches normally use PatchPort.</font></td>
  </tr>
  <tr>
    <td colspan=2>
      <input type=submit name="addSwitch" value="Add Switch">
    </td>
  </tr>
</table>
</form>

<?php
if (get_setting("install_type") == "advanced") {
?>
<p><h2>High Availability</h2></p>
<table>
  <tr>
    <th>Enable High Availability</th>
    <td><input type="checkbox"></td>
  </tr>
  <tr>
    <th>High Availability Method</th>
    <td>
      <select>
        <option>Corosync + Pacemaker</option>
      </select>
    </td>
  </tr>
</table>
<?php
}
?>
