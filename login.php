<?php
if(!file_exists('sys/head.php')) {
  header('location: install.php');
  die();
}
if(key($_GET) === 'killsesh') {
  session_start();
  session_destroy();
  header('location: login.php');
  die();
}
session_start();
if(!empty($_SESSION['auth'])) {
  header('location: index.php');
  die();
}
include 'sys/head.php';
if(isset($_POST['username']) && isset($_POST['password'])) {
  if($user = $db->table('admin')->find('username', $_POST['username'], 1)->get());
  @$user = $user[array_key_first($user)];
  if (sha1($_POST['password']) === $user['password']) {
    $_SESSION['auth'] = 'true';
    header('location: index.php');
    die();
  }
}
?>
<!DOCTYPE html>
<html>
  <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>JSBCC::Auth</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Muli:300,400,700">
    <link rel="stylesheet" href="css/style.blue.css?lol=<?=rand(0,999);?>" id="theme-stylesheet">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="shortcut icon" href="img/favicon.ico">
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="login-page">
      <div class="container d-flex align-items-center">
        <div class="form-holder has-shadow">
          <div class="row">
            <div class="col-lg-6">
              <div class="info d-flex align-items-center">
                <div class="content" style="width:100%">
                  <div class="logo" style="width:100%">
                    <div class="brand-text brand-big visible" style="width:100%">
                      <center><div style="border:3px solid darkgrey;">
                        <strong class="text-primary">
                          <h1>JSBCC</h1>
                        </strong>
                        <strong style="color:darkgrey;">
                          <h1>::</h1>
                        </strong>
                        <strong><h1>Authentication</h1></strong>
                      </div></center>
                    </div>
                  </div>
                  <p></p>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form d-flex align-items-center">
                <div class="content">
                  <form method="post" class="form-validate mb-4" action="">
                    <div class="form-group">
                      <input id="login-username" type="text" name="username" required data-msg="Please enter your username" class="input-material">
                      <label for="login-username" class="label-material">User Name</label>
                    </div>
                    <div class="form-group">
                      <input id="login-password" type="password" name="password" required data-msg="Please enter your password" class="input-material">
                      <label for="login-password" class="label-material">Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <script src="js/front.js"></script>
  </body>
</html>
