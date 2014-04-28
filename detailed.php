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
            $result[$key][$data[$i][1]] = floatval($data[$i][2]);
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

foreach(array_keys($table) as $fs) {
    echo "==================================================<br />";
    echo "<p>$fs</p>";
    foreach(array_keys($table[$fs]) as $journal_mode) {
        foreach(array_keys($table[$fs][$journal_mode]) as $wc) {
            foreach(array_keys($table[$fs][$journal_mode][$wc]) as $threadsQ) {
                echo "--------------------------------------------------<br />";
                echo "<p>--$journal_mode ($wc) x$threadsQ</p>";
                foreach(array_keys($table[$fs][$journal_mode][$wc][$threadsQ]) as $workload) {
                    echo '+++++<br />';
                    echo "<p>$workload</p>";
                    $data = $table[$fs][$journal_mode][$wc][$threadsQ][$workload];
                    foreach(array_keys($data) as $cat) {
                        echo "<p>$cat<br />";
                        foreach($data[$cat] as $key=>$value) {
                            echo "$key: $value<br/>";
                        }
                        echo '</p>';
                    }
                }
            }
        }
    }
}
