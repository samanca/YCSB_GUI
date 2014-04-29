<?php
function prepare($path) {
    $data = explode("\n", file_get_contents($path));
    $data = array_slice($data, 4);
    $data = array_slice($data, 0, count($data) - 2);
    return array_map(function($v) { return explode(',', $v); }, $data);
}

function process_data($data) {
    $result = array();
	for ($i = 0; $i < count($data); $i++) {
		$key = substr($data[$i][0], 1, strlen($data[$i][0]) - 2);
		if (!is_numeric($data[$i][1]) && strpos($data[$i][1], 'Return=') === false) {
            $result[$key][trim($data[$i][1])] = floatval($data[$i][2]);
		}
	}
	return $result;
}

function subdirs($path) {
    $dirs = scandir($path);
    $dirs = array_filter($dirs, function($dir) {
        return !($dir == '.' || $dir == '..' || $dir == '.DS_Store');
    });
    return $dirs;
}

function average_latency($d) {
	$opCount = 0;
	$sum = floatval(0);
	foreach(array_keys($d) as $cat) {
		if (in_array($cat, array('OVERALL', 'CLEANUP'))) continue;
		$opCount += intval($d[$cat]['Operations']);
		$sum += intval($d[$cat]['Operations']) * $d[$cat]['AverageLatency(us)'];
	}
	return $sum / $opCount;
}

$base_path = realpath('../experiments/single/') . '/';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'run';

$table = array();
foreach(subdirs($base_path) as $wc) {
    foreach(subdirs($base_path . $wc) as $fs) {
        foreach(subdirs($base_path . $wc . '/' . $fs) as $journal_mode) {
            foreach(subdirs($base_path . $wc . '/' . $fs . '/' . $journal_mode) as $run) {
                $tokens = explode('_', $run);
                $threadsQ = $tokens[1];
                $workload = $tokens[0];

                if (in_array(intval($threadsQ), array(4, 16))) continue;

                // Interpret data
                $path = "${base_path}/${wc}/${fs}/${journal_mode}/${run}/timeseries_${mode}.txt";
                $table[$fs][$journal_mode][$wc][$threadsQ][$workload] =
                    process_data(prepare($path));
            }
        }
    }
}

$mapper = array(
	'journal' => array(
		'pmfs' => 'journaled',
		'pmfs_replica' => 'replicas_safe',
		'rnvmm_sync' => 'journaled',
		'rnvmm_async' => 'journaled',
	),
	'nojournal' => array(
		'pmfs' => 'fsync_safe',
		'pmfs_replica' => 'replicas_safe',
		'rnvmm_sync' => 'fsync_safe',
		'rnvmm_async' => 'fsync_safe',
	),
);

foreach(array('journal', 'nojournal') as $journal_mode) {
	echo "<p>$journal_mode</p>";
	foreach(array('a', 'b', 'c', 'd', 'e', 'f') as $workload) {
		echo $workload . '&nbsp;';
		foreach(array('pmfs', 'pmfs_replica', 'rnvmm_async', 'rnvmm_sync') as $fs) {
			$lat = average_latency($table[$fs][$journal_mode][$mapper[$journal_mode][$fs]][1][$workload]);
			if (isset($_GET['latency']))
				$value = $lat;
			else
				$value = 1000000 / $lat;
			echo number_format($value, 2, ".", "") . '&nbsp;';
		}
		echo '<br />';
	}
}
