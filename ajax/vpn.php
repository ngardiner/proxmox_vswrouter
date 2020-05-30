<?php
  include_once("../lib/ca_func.php");
  include_once("../lib/database.php");

  if ($_POST['req'] == "addVPNServer") {
    # Create diffie hellman dhparam file
    $dhparam = ca_make_dh(2048);

    if (! add_vpn_server($_POST['serverName'], $dhparam, $_POST['serverProto'],
          $_POST['serverPort'], $_POST['serverNetwork'], $_POST['serverMask'],
          $_POST['serverCA'], $_POST['serverDesc'])) {
      header(400);
    }

  }

  if ($_POST['req'] == "delVPNServer") {
    $server = $_POST['server'];
    print("<b>Warning:</b> This action is irreversible.");
    print("<p>Are you sure you want to delete the VPN Server $server?");
    exit(0);
  }
  if ($_POST['req'] == "delVPNServerConfirm") {
    del_vpn_server($_POST['server']);
    header(200);
  }
  if ($_POST['req'] == "delVPNServerFooter") {
    ?>
    <button type="button" id='del-vpn-server' class="btn btn-danger" data-dismiss="modal">Delete VPN Server</button>
    <script language="JavaScript">
      $(document).ready(function() {
        $('#del-vpn-server').click(function() {
          $.ajax({
            url: "ajax/vpn.php",
            type: 'POST',
            data: "req=delVPNServerConfirm&server=<?php print $_POST['server'];?>",
            dataType: 'html',
            success: function(status) {
                $('#ModalPopup').modal('hide');
            },
          })
        })
      });
    </script>
    <button type="button" class="btn btn-success" data-dismiss="modal">Cancel</button>
    <?php
    exit(0);
  }
  if ($_POST['req'] == "editVPN") {
    $vpn = get_vpn_server_name($_POST['server']);
    if (! $vpn) {
      $vpn = Array("name" => "");
    }
    ?>
    <script language="JavaScript">
    $(document).ready(function() {
      $('#serverCA').change(function() {
        getCertOptions();
      })
    });
    $(document).ready(function() { getCertOptions(); });

    function getCertOptions() {
      var ca = $('#serverCA').children("option:selected").val();
      $.ajax({
        url: "ajax/vpn.php",
        type: 'POST',
        data: "req=getCertsForCA&ca="+ca,
        dataType: 'json',
        success: function(status) {
          $("#serverCert").html('');
          $.each(status, function(){
            $("#serverCert").append('<option value="'+ this +'">'+ this +'</option>')
          })
        }
      })
    }
    </script>
    <table class='table'>
      <thead class="thead-light">
        <tr>
          <th colspan=2>VPN Server Settings</th>
        </tr>
      </thead>
      <tr>
        <th>VPN Server Name</th>
        <?php
        if ($vpn['name'] == "") {
          ?>
          <td><input id="serverName" size=10 />
          <?php
        } else {
          ?>
          <td><?php print $vpn['name'];?></td>
          <?php
        }
        ?>
      </tr>
      <tr>
        <th>Protocol</th>
        <td>
          <select id="serverProto">
          <option value="TCP" <?php if ($vpn['proto'] == "TCP") { print "selected"; }?>>TCP</option>
          <option value="UDP" <?php if ($vpn['proto'] == "UDP") { print "selected"; }?>>UDP</option>
          </select>
        </td>
      </tr>
      <tr>
        <th>VPN Network</th>
        <td><input id="serverNetwork" size=11 value="<?php print $vpn['network']; ?>" /> / <input id="serverMask" size=1 value="<?php print $vpn['mask']; ?>" />
        <br /><i><font size=-2>This is the network in which VPN Clients will obtain IP addresses</i></font></td>
      </tr>
      <tr>
        <th>CA / Server Cert</th>
        <td>
          <select id="serverCA">
            <?php
              $cas = get_vpn_ca();
              foreach ($cas as $ca) {
                print "<option value='".$ca['name']."'>".$ca['name']."</option>";
              }
            ?>
          </select>
          <select id="serverCert">
          </select>
       </td>
      </tr>
      <tr>
        <th>Port</th>
        <td><input id="serverPort" size=4 value="<?php print $vpn['port']; ?>"></td>
      </tr>
      <tr>
        <th>Description</th>
        <td><input id="serverDesc" size=35 value="<?php print $vpn['desc']; ?>"></td>
      </tr>
    </table>
    <table class='table'>
      <thead class="thead-light">
        <tr>
          <th colspan=2>Client Routes</th>
        </tr>
      </thead>
      <tr>
        <th>Network & Mask</th>
        <th>Action</th>
      </tr>
    </table>
    <?php
    exit(0);
  }
  if ($_POST['req'] == "editVPNFooter") {
    if ($_POST['server'] != "") { 
      ?>
      <button type="button" class="btn btn-success" 
       data-dismiss="modal">Edit VPN Server</button>
      <?php
    } else {
      ?>
      <button type="button" id="add-vpn-server" 
        class="btn btn-success">Add VPN Server</button>
      <script language="JavaScript">
        $(document).ready(function() {
          $('#add-vpn-server').click(function() {
            // Construct query string
            var query = "req=addVPNServer";
            query += "&serverName="+$('#serverName').val();
            query += "&serverProto="+$('#serverProto').children("option:selected").val();
            query += "&serverNetwork="+$('#serverNetwork').val();
            query += "&serverMask="+$('#serverMask').val();
            query += "&serverCA="+$('#serverCA').children("option:selected").val();
            query += "&serverCert="+$('#serverCert').children("option:selected").val();
            query += "&serverPort="+$('#serverPort').val();
            query += "&serverDesc="+$('#serverDesc').val();
            $.ajax({
              url: "ajax/vpn.php",
              type: 'POST',
              data: query,
              dataType: 'html',
              success: function(status) {
                  $('#ModalPopup').modal('hide')
              },
            })
          })
        });
      </script>
      <?php
    }
    ?>
    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <?php
    exit(0);
  }
  if ($_POST['req'] == "getCertsForCA") {
      $certs = get_vpn_certs();
      $result = Array();
      foreach ($certs as $cert) {
        if ($_POST['ca'] == $cert['ca']) {
          $result[] = $cert['name'];
        }
      }
      print json_encode($result);
      return 0;
  }

?>
