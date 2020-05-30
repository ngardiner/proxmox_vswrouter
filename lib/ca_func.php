<?php

  function ca_conf_server() {
    # Generate a configuration file for creating server certs
    $conf  = "[ ca ]\n";
    $conf .= "default_ca	= CA_default\n\n";
    $conf .= "[ CA_default ]\n";
    $conf .= "dir           = '/tmp'\n";
    $conf .= "certs         = \$dir\n";
    $conf .= "database      = \$dir/index.txt\n";
    $conf .= "new_certs_dir = \$dir\n\n";
    $conf .= "certificate   = \$dir/cacert.pem\n";
    $conf .= "serial        = \$dir/serial\n";
    $conf .= "private_key   = \$dir/cakey.pem\n";
    $conf .= "policy        = policy_anything\n";
    $conf .= "default_days  = 3650\n";
    $conf .= "default_md    = default\n\n";
    $conf .= "[ req ]\n";
    $conf .= "default_bits     = 2048\n";
    $conf .= "default_keyfile  = key.pem\n";
    $conf .= "default_md       = sha256\n";
    $conf .= "string_mask      = nombstr\n";
    $conf .= "distinguished_name = req_distinguished_name\n";
    $conf .= "req_extensions   = v3_req\n\n";
    $conf .= "[ req_distinguished_name ]\n";
    $conf .= "commonName              = openvpnhost.MyOrganisation.org\n";
    $conf .= "commonName_max          = 64\n\n";
    $conf .= "[server]\n";
    $conf .= "basicConstraints=CA:FALSE\n";
    $conf .= "nsCertType    = server\n";
    $conf .= "nsComment     = 'Server Certificate'\n";
    $conf .= "subjectKeyIdentifier=hash\n";
    $conf .= "authorityKeyIdentifier=keyid,issuer:always\n";
    $conf .= "extendedKeyUsage=serverAuth\n";
    $conf .= "keyUsage = digitalSignature, keyEncipherment\n\n";
    $conf .= "[ v3_req ]\n";
    $conf .= "basicConstraints       = CA:FALSE\n";
    $conf .= "subjectKeyIdentifier   = hash\n\n";
    $conf .= "[ policy_anything ]\n";
    $conf .= "countryName            = optional\n";
    $conf .= "stateOrProvinceName    = optional\n";
    $conf .= "localityName           = optional\n";
    $conf .= "organizationName       = optional\n";
    $conf .= "organizationalUnitName = optional\n";
    $conf .= "commonName             = supplied\n";
    $conf .= "name                   = optional\n";
    $conf .= "emailAddress           = optional\n\n";
    file_put_contents("/tmp/openssl.cnf", $conf);
  }

  function ca_make_cacert($key, $cert, $subj) {
    exec("/usr/bin/openssl req -new -x509 -days 3650 $subj -key $key -out $cert 2>&1", $a, $s);
    print_r($a);
    print($s);
  }

  function ca_make_dh($length) {
    exec("/usr/bin/openssl dhparam -out /tmp/dhparam.pem $length", $a, $s);
    $dhparam = file_get_contents("/tmp/dhparam.pem");
    unlink("/tmp/dhparam.pem");
    return $dhparam;
  }

  function ca_make_svrcert($key, $name) {

    # Create openssl config
    ca_conf_server();

    exec("/usr/bin/openssl req -nodes -new -config /tmp/openssl.cnf -extensions server -key $key -subj '/CN=".$name."' -out /tmp/server.csr 2>&1", $a, $s);
    exec("/usr/bin/openssl ca -config /tmp/openssl.cnf -extensions server -batch -out /tmp/servercrt.pem -in /tmp/server.csr 2>&1", $a, $s);
    print_r($a);
    print($s);

    # Remove config file
    unlink("/tmp/openssl.cnf");

    # Remove CSR file
    unlink("/tmp/server.csr");

    # Read cert file and return
    $cert = file_get_contents("/tmp/servercrt.pem");
    unlink("/tmp/servercrt.pem");
    return $cert;
  }

  function ca_make_key($keylen) {
    # Create openssl private key
    $config = array(
      "private_key_bits" => $keylen
    );
    $pkey = openssl_pkey_new($config);
    openssl_pkey_export($pkey, $privkey);
    return $privkey;
  }

  function ca_set_subject($country, $state, $org) {
    $subj = "-subj '/C=".$country."/ST=".$state;
    $subj .= "/O=".$org."/CN=VPN CA'";
    return $subj;
  }

?>
