<?php
  $server="localhost";
  $user="root";
  $pass="";
  $dbname="stuby";
  date_default_timezone_set('Asia/Manila');
  $conn = new mysqli($server,$user,$pass,$dbname);
  if($conn->connect_error){
    die('Connection Failed:'.$conn->connect_error);
  }
?>
