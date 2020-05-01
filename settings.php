<?php
  include_once("database.php");
?>
<p><h2>General</h2></p>
<form action="post.php" method=POST>
<table class="table">
  <tr>
    <th>Installation Type</th>
    <td>
      <select>
        <option>Simple</option>
        <option>Advanced</option>
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
<table>
</table>

<form action="post.php" method=POST>
<table class="table">
  <tr>
    <th>Switch Name</th>
    <td><input size=10></td>
    <td><font size=-1>The name of the virtual switch, eg. vmbr1</font></td>
  </tr>
  <tr>
    <th>Switch Type</th>
    <td>
      <select title="Switch Type" name="switchType" class="selectpicker">
        <option>Q-in-Q Trunk</option>
	<option>VLAN Trunk</option>
        <option>Unmanaged VLAN Trunk</option>
      </select>
    </td>
    <td><font size=-1>A VLAN Switch uses a single VLAN across the network whereas a Q-in-Q switch trunks VLANs 1-4096 over another VLAN ID. All installations need 1 VLAN Trunk switch, but you cannot have more than 1 VLAN Trunk switch in most networks.</font></td>
  </tr>
  <tr>
    <th>Switch Uplink</th>
    <td>
      <select title="Type" name="uplinkType" class="selectpicker">
        <option>Interface</option>
        <option>PatchPort</option>
      </select>
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
