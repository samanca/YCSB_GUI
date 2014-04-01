<?php
function process_data($data) {
	for ($i = 0; $i < count($data); $i++) {
		$key = $data[$i][0];
		if (is_numeric($data[$i][1]) || trim($data[i][1])) {
			$result[$key]['data'][intval($data[$i][1])] = floatval($data[$i][2]);
		}
		else {
			$result[$key]['stat'][trim($data[$i][1])] = trim($data[$i][2]);
		}
	}
	return $result;
}

function sample($data, $sample_size) {
	if (count($data) <= $sample_size) return $data;
	else {
		$temp = array();
		$sample_rate = count($data) / $sample_size;
		$counter = 0;
		foreach($data as $key=>$value) {
			if (++$counter % $sample_rate == 0)
				$temp[$key] = $value;
		}
		return $temp;
	}
}

function running_chart($data, $name, $title, $subtitle) {
    return array(
        'title' => array('text' => $title, 'x' => -20),
        'subtitle' => array('text' => $subtitle, 'x' => -20),
        'xAxis' => array('categories' => array_keys($data)),
        'yAxis' => array('title' => 'Average Latency (ns)', 'plotLines' => array(array('value' => 0, 'width' => 1, 'color' => '#808080'))),
        'tooltip' => array('valueSuffix' => 'ns'),
        'legend' => array('layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'middle', 'borderWidth' => 0),
        'series' => array(
		array('name' => $name, 'data' => array_map(function($v) { return intval($v); }, array_values($data))),
	),
    );
}


?>
<html>
    <head>
        <title>YCSB GUI</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Bootstrap -->
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen"></title>
	    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    </head>
    <body>
	<div class="container-fluid">
	    <?php
	    $dir_path = realpath('../experiments/single/pmfs/journal/') . '/';//"/Users/saman/Desktop/experiments/single/pmfs/journal/";
	    $workloads = scandir($dir_path);
            $charts = array();

	    foreach($workloads as $workload) {
		if ($workload == '.' || $workload == '..') continue;
		echo '<div class="row row-fluid">';
		echo '<div class="span12">';
		echo "<h1>Workload $workload</h1>";
		echo '</div>';
		
		foreach(array('histogram', 'timeseries') as $type) {
			foreach(array('load', 'run') as $mode) {
				$data = explode("\n", file_get_contents($dir_path . $workload . "/{$type}_{$mode}.txt"));
				$data = array_slice($data, 4);
				$data = array_slice($data, 0, count($data) - 2);
				$data = array_map(function($v) { return explode(',', $v); }, $data);
				echo '<div class="span12">';
				echo "<h2>$type ($mode)</h2>";
				echo '<p><strong>Overall' . $data[0][1] . ':' . $data[0][2] . '</strong></p>';
				echo '<p><strong>Overall' . $data[1][1] . ':' . $data[1][2] . '</strong></p>';
				$data = process_data(array_slice($data, 2));
				
				echo '<div class="row">';
				$ops = array_keys($data);
				foreach($ops as $op) {
					echo '<div class="span' . (12 / count($ops)) . '">';
					echo "<h3>$op</h3>";
					if ($type == 'histogram') {
						echo '<p>Pie Chart</p>';
					}
					else {
                        echo '<div id="chart_' . count($charts) . '" style="min-width: 310px; height: 400px; margin: 0 auto"></div>';
                        $charts[] = '$(\'#chart_' . count($charts) . '\').highcharts(' .
                            json_encode(running_chart(sample($data[$op]['data'], 20), 'Average Latency', $op, '')) . ');';
					}
					echo '</div>';
				}
				echo '</div>';
				echo '</div>';
			}
		}
		echo '</div>';
	    }
	    ?>
	</div>	
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>

    <script type="text/javascript">	
        $(function () {
        <?php
        foreach($charts as $chart) {
            echo $chart . "\n";
        }
        ?>
        });
    </script>

	<script src="highcharts-3.0.10/js/highcharts.js"></script>
	<script src="highcharts-3.0.10/js/modules/exporting.js"></script>
    </body>
</html>
