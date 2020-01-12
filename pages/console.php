<?php
session_start();
if(empty($_SESSION['auth'])) {
	header('Location: login.php');
	die();
}

include '../sys/head.php';

if(!empty($_GET['clear'])) {
  header("location: console.php?uuid={$_GET['clear']}");
  $db->table('console')->find('uuid', $_GET['clear'])->change('log', '[CONSOLE START]');
  die();
}
if(empty($uuid = $_GET['uuid'])) {
  die();
}
?>
<title>Javascript Console</title>
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
input[type="text"]#JSTermInput{
    -webkit-appearance:none!important;
    border:2px solid #000000;
    border-bottom: 0px;
    border-left:0px;
    margin:0 0 0 -5px;
    background: #343a40;
    color: #FFFFFF;
    width: 80%;
    height:30px;
    font-size:14pt;
}
input[type="text"]#JSTermDenote{
    -webkit-appearance:none!important;
    width:75px;
    border:2px solid #000000;
    border-right:0px;
    border-bottom: 0px;
    outline:none;
    background:white;
    width: 18px;
    color: #00FF00;
    background: #343a40;
    height:30px;
    font-size:14pt;
}

input.JSTerm:focus, textarea {
    outline: none;
}

textarea {
  border:2px solid #000000;
  border-top:1px solid #000000;
  background: #343a40;
  font-size:14pt;
  color: #cccccc;
  width:100%;
  height:92%;
  resize: none;
}
</style>
<?php
echo "<h2 class='logsViewPort'>Javascript Console: </h2>{$uuid} - <b>";
echo "<a href='console.php?clear={$uuid}'>Clear Console</a></b><br />";
?>
<input class="JSTerm" type="text" id="JSTermDenote" value="$ " disabled/>
<input class="JSTerm" type="text" id="JSTermInput" />
<?php
echo "<textarea id='JSTermOutput' disabled></textarea>";
?>
<x><img src="../img/loader.gif" /><br /><br />(Waiting for response... Can take up to 35 seconds.)</x>
<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$(document).ready(function() {
  const sleep = (milliseconds) => {
    return new Promise(resolve => setTimeout(resolve, milliseconds))
  }
  function doResize() {
    $("#JSTermInput").css({
      'width': (($("#JSTermOutput").width() - 11) + 'px')
    });
  }
  doResize();
  $(window).on("resize", doResize);
  $("#JSTermInput").focus();

  function updateTerm( uuid ) {
    $.ajax({
        type: 'POST',
        url: "../ajax/cnc.php",
        data: {cmd: 'getconsole', uuid: '<?=$uuid?>'},
        success: function(x) {
          $('#JSTermOutput').val(x);
        }
    })
  }
  updateTerm("<?=$uuid;?>");

  $('#JSTermInput').on('keypress', function (e) {
    if(e.which === 13){
      $(this).attr("disabled", "disabled");
      var cmd = $(this).val();
      console.log('SENT> ' + $(this).val());
      $(this).val("");
      $.ajax({
          type: 'POST',
          url: "../ajax/cnc.php",
          data: { cmd: 'console', arg: cmd, uuid: '<?=$uuid?>' },
          success: function(x) {
            console.log(x);
            $('x').show();
            sleep(35000).then(() => {
              updateTerm("<?=$uuid;?>");
              $('x').hide();
              $('#JSTermInput').removeAttr("disabled");
              $('#JSTermInput').focus();
            });
          }
      })
    }
  });
});
</script>
