<?php

function import_data($file_path) {
	$data = explode("\n", file_get_contents($file_path));
	$data = array_slice($data, 4);
	$data = array_slice($data, 0, 2);
	$data = array_map(function($v) { return explode(',', $v); }, $data);
	$data = array_map(function($v) { return array(
		strtolower(trim(substr($v[1], 0, strpos($v[1], '(')))), 
		floatval($v[2])
	); }, $data);

	$retVal = array();
	foreach($data as $t)
		$retVal[$t[0]] = $t[1];
	return $retVal;
}

$root_path = "../experiments/single/";
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'run';
$workload = isset($_POST['workload']) ? $_POST['workload'] : 'a';
$metric = isset($_POST['metric']) ? $_POST['metric'] : 'throughput';
$type = isset($_POST['type']) ? $_POST['type'] : 'timeseries';
?>
<html>
    <head>
        <title>YCSB GUI</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Bootstrap -->
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen"></title>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<?php
	if (isset($chart)) {
	    echo '<script type="text/javascript">$(function () {' . "\n";
	    foreach($chart as $key=>$c)
		echo '$(\'#chart_' . $key . '\').highcharts(' . json_encode($c) . ");\n";
            echo '});</script>';
	}
	?>
    </head>
    <body>
	<div id="navbar-example" class="navbar navbar-static">
		<div class="navbar-inner">
			<div class="container" style="width: auto;">
				<a class="brand" href="index.php">YCSB GUI</a>
			</div>
		</div>
	</div>
	<div class="container-fluid">
	<div class="row-fluid">
	    <form action="tabular.php" method="POST">
		<div class="span2">
		    <p>
		        <select name="mode">
		            <option value="load"<?=($mode == 'load' ? ' selected' : '')?>>Load</option>
		            <option value="run"<?=($mode == 'run' ? ' selected' : '')?>>Run</option>
		        </select>
		    </p>		    
		</div>
		<div class="span2">
		    <p>
		        <select name="workload">
			<?php foreach(array('a', 'b', 'c', 'd', 'e', 'f') as $wl): ?>
		            <option value="<?=$wl?>"<?=($workload == $wl ? ' selected' : '')?>>Workload <?=$wl?></option>
			<?php endforeach; ?>
		        </select>
		    </p>		    
		</div>
		<div class="span2">
		    <p>
		        <select name="metric">
		            <option value="runtime"<?=($metric == 'runtime' ? ' selected' : '')?>>Runtime (msec)</option>
		            <option value="throughput"<?=($metric == 'throughput' ? ' selected' : '')?>>Throughput (ops)</option>
		        </select>
		    </p>		    
		</div>
		<div class="span2">
		    <p>
		        <select name="type">
		            <option value="histogram"<?=($type == 'histogram' ? ' selected' : '')?>>Histogram</option>
		            <option value="timeseries"<?=($type == 'timeseries' ? ' selected' : '')?>>Timeseries</option>
		        </select>
		    </p>		    
		</div>
		<div class="span4">
			<p><input type="submit" value="Submit" class="btn" /></p>
		</div>
	    </form>
	</div>
	<?php
	$wc = array(
		'nojournal' => array('acknowledged', 'fsync_safe', 'safe', 'unacknowledged'),
		'journal' => array('acknowledged', 'journaled', 'safe', 'unacknowledged'),
	);
	foreach(array_keys($wc) as $i):
	?>
	<hr />
	<div class="row-fluid">
	    <div class="span10 offset1">
		<h3>mongod --<?=$i?></h3>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<td>Number of Threads</td>
					<td colspan="4"><p class="text-center">1</p></td>
					<td colspan="4"><p class="text-center">4</p></td>
					<td colspan="4"><p class="text-center">16</p></td>
				</tr>
				<tr>
					<td>Write Concern</td>
					<td><p class="text-center"><?=$wc[$i][0]?></p></td>
					<td><p class="text-center"><?=$wc[$i][1]?></p></td>
					<td><p class="text-center"><?=$wc[$i][2]?></p></td>
					<td><p class="text-center"><?=$wc[$i][3]?></p></td>
					<td><p class="text-center"><?=$wc[$i][0]?></p></td>
					<td><p class="text-center"><?=$wc[$i][1]?></p></td>
					<td><p class="text-center"><?=$wc[$i][2]?></p></td>
					<td><p class="text-center"><?=$wc[$i][3]?></p></td>
					<td><p class="text-center"><?=$wc[$i][0]?></p></td>
					<td><p class="text-center"><?=$wc[$i][1]?></p></td>
					<td><p class="text-center"><?=$wc[$i][2]?></p></td>
					<td><p class="text-center"><?=$wc[$i][3]?></p></td>
				</tr>
			</thead>
			<?php
			$rows = array();
			foreach(scandir(realpath($root_path)) as $writeConcern) {
				if(strpos($writeConcern, '.') !== false || !in_array($writeConcern, $wc[$i])) continue;
				foreach(scandir(realpath($root_path . $writeConcern)) as $fs) {
					if(strpos($fs, '.') !== false) continue;
					$rows[$fs][$writeConcern] = array();
					$path = $root_path . $writeConcern . '/' . $fs . '/' . $i . '/';
					foreach(array(1, 4, 16) as $nt) {
						$file = realpath($path . $workload . '_' . $nt . '/' . $type . '_' . $mode . '.txt');
						$rows[$fs][$writeConcern][$nt] = import_data($file)[$metric];
					}
				}
			}
			?>
			<?php foreach($rows as $fs=>$value): ?>
			<tr>
				<td><?=$fs?></td>
				<?php foreach(array(1, 4, 16) as $nt): ?>
					<?php foreach($wc[$i] as $writeConcern): ?>
						<td><p class="text-center"><?=$rows[$fs][$writeConcern][$nt]?></p></td>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tr>			
			<?php endforeach; ?>
		</table>
	    </div>
	</div>
	<?php
	endforeach;
	?>
	</div>
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>

	<script src="highcharts-3.0.10/js/highcharts.js"></script>
	<script src="highcharts-3.0.10/js/modules/exporting.js"></script>
    </body>
</html>
