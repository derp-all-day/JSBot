<?php
include 'classes/phdtools.class.php';
include 'classes/phd.class.php';

if(key($_GET) === 'validate')
	die(print('valid'));

if(empty($_GET['user']) || empty($_GET['pass']) || empty($_GET['db']))
	die(print('failure'));

$socket = new PHD($_GET['user'], $_GET['pass'], $_GET['db']);

if(!$socket->isAuthenenticated())
	die(print('failure'));

echo $socket->nodeInit($_GET['query']);
?>
