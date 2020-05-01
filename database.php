<?php

# Check database exists
if (! file_exists("database.db3")) {
  $dbh = new PDO("sqlite:database.db3");

  $dbh->execute("CREATE TABLE SETTINGS (setting varchar(24), value varchar(64), primary key(setting))");
  $sth = $dbh->prepare("INSERT INTO SETTINGS (setting, value) VALUES (:setting, :value)");
  $sth->execute(array(':setting' => "db_version", ':value' => "1"));
  $sth->execute(array(':setting' => "install_type", ':value' => "simple"));

}
?>
