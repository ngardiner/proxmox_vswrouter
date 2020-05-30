<?php
  include_once("lib/database.php");
  include_once("lib/lib.php");
?>
<script language="JavaScript">
    $(document).ready(function() { 
      $('#add-vpn-server').click(function() { 
        editVPN("") 
      })
    });

    function delVPN(server) {
      $.ajax({
        url: "ajax/vpn.php",
        type: 'POST',
        data: "req=delVPNServer&server="+server,
        dataType: 'html',
        success: function(status) {
            $('#ModalPopup .modal-title').html("Delete VPN Server");
            $('#ModalPopup .modal-body').html(status);
            $('#ModalPopup').modal('show')
        },
      })
      $.ajax({
        url: "ajax/vpn.php",
        type: 'POST',
        data: "req=delVPNServerFooter&server="+server,
        dataType: 'html',
        success: function(status) {
            $('#ModalPopup .modal-footer').html(status);
        },
      })
    }

    function editVPN(server) {
      $.ajax({
        url: "ajax/vpn.php",
        type: 'POST',
        data: "req=editVPN&server="+server,
        dataType: 'html',
        success: function(status) {
          if (server) {
            $('#ModalPopup .modal-title').html("Edit VPN Server");
          } else {
            $('#ModalPopup .modal-title').html("Add VPN Server");
          }
          $('#ModalPopup .modal-body').html(status);
          $('#ModalPopup').modal('show')
        },
      });
      $.ajax({
        url: "ajax/vpn.php",
        type: 'POST',
        data: "req=editVPNFooter&server="+server,
        dataType: 'html',
        success: function(status) {
            $('#ModalPopup .modal-footer').html(status);
        },
      })
    }
</script>
 <div id="ModalPopup" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    &times;</button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    Close</button>
            </div>
        </div>
    </div>
</div>
<table class="table">
  <thead class="thead-light">
    <tr>
      <th colspan=7>VPN Servers <a href="#" id='add-vpn-server' class="btn btn-outline-primary btn-sm float-right">Add</a></th>
    </tr>
  </thead>
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
    $count = 1;
    foreach ($servers as $server) {
      print "<tr>";
      print "<td>".$server['name']."</td>";
      print "<td>".$server['proto']."</td>";
      print "<td>".$server['port']."</td>";
      print "<td>".$server['network']."/".$server['mask']."</td>";
      print "<td>".$server['desc']."</td>";
      print "<td><a href='get.php?action=getDHP&server=".$server['name']."'>dhparam</a> | ";
      print "<a href='get.php?action=getVPNServerConfig&name=".$server['name']."'>Server Config</a> | ";
      print "<a href='#'>Client Config</a></td>";

      # Define onclick functions for edit and delete buttons
      print "<script language=\"JavaScript\">$(document).ready(function() { $('#del-vpn-server-".$count."').click(function() { delVPN('".$server['name']."') })});</script>";
      print "<script language=\"JavaScript\">$(document).ready(function() { $('#edit-vpn-server-".$count."').click(function() { editVPN('".$server['name']."') })});</script>";

      # And then print the buttons
      print "<td><a href='#' rel='details' id='edit-vpn-server-".$count."' class='btn btn-success' title='Edit'>Edit</a> <a href='#' rel='details' id='del-vpn-server-".$count."' class='btn btn-danger' title='Delete'>Delete</a></td>";
      print "</tr>";
      $count++;
    }
  ?>
</table>

<table class="table">
  <thead class="thead-light">
    <tr>
      <th>VPN Clients <a href="#" id='add-vpn-client' class="btn btn-outline-primary btn-sm float-right">Add</a></th>
    </tr>
  </thead>
</table>

Client Name <input> Protocol
<select>
</select>
Host <input>

<table class="table">
  <thead class="thead-light">
    <tr>
      <th colspan=5>Certificate Authorities <a href="#" id='add-vpn-ca' class="btn btn-outline-primary btn-sm float-right">Add</a></th>
    </tr>
  </thead>
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
  <thead class="thead-light">
    <tr>
      <th colspan=4>VPN Certificates</th>
    </tr>
  </thead>
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
