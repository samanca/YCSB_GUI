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

function prep_pie($data, $total, $limit = 20) {
	$temp = array_filter($data, function($v) { return intval($v) > 0; });
	arsort($temp);
	if (count($temp) > $limit) $temp = array_slice($temp, 0, $limit, true);
	$sum = 0;
	foreach($temp as $key=>$value) {
		$temp[$key] = ($value * 100) / $total;
		$sum = $sum + $temp[$key];
	}
	if ($sum < 100) $temp['Other'] = 100 - $sum;
	return $temp;
}

function pie_chart($data, $name, $title) {
	return array(
		'chart' => array('plotBackgroundColor' => null, 'plotBorderWidth' => null, 'plotShadow' => false),
		'title' => array('text' => $title),
		'tooltip' => array('pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b>'),
		'plotOptions' => array(
			'pie' => array(
				'allowPointSelect' => true,
                		'cursor' => 'pointer',
				'dataLabels' => array(
					'enabled' => true,
                    			'color' => '#000000',
                    			'connectorColor' => '#000000',
                    			'format' => '<b>{point.name}</b>: {point.percentage:.1f} %'
				),
			),
		),
		'series' => array(
			array(
				'type' => 'pie',
				'name' => $name,
				'data' =>  array_map(function($key) use($data) {
					return array(
						($key == 'Other' ? 'Other' : "Bucket[$key]"),
						$data[$key]
					);
				}, array_keys($data)),
			),
		),
	);
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
		array('name' => $name, 'data' => array_map(function($v) { return floatval($v); }, array_values($data))),
	),
    );
}

$base_path = realpath('../experiments/single/') . '/';
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
	<div id="navbar-example" class="navbar navbar-static">
		<div class="navbar-inner">
			<div class="container" style="width: auto;">
				<a class="brand" href="#">YCSB GUI</a>
				<ul class="nav" role="navigation">
			<?php
			$mc = 0;
			foreach(scandir($base_path) as $tmode) {
				if (strpos($tmode, '.') === 0) continue;
				foreach(scandir($base_path . $tmode) as $kernel) {
					if (strpos($kernel, '.') === 0) continue;
					echo '<li class="dropdown">';
					echo '<a id="drop' . $mc . '" href="#" role="button" class="dropdown-toggle" data-toggle="dropdown">' .
						$kernel . ' (' . $tmode . ') <b class="caret"></b></a>';
					echo '<ul class="dropdown-menu" role="menu" aria-labelledby="drop' . $mc . '">';
					foreach(scandir($base_path . $tmode . '/' . $kernel) as $mode) {
						if (strpos($mode, '.') === 0) continue;
						echo '<li role="presentation"><a role="menuitem" tabindex="-1" href="?path=' .
							$tmode . '/' . $kernel . '/' . $mode . '">';
						echo $mode;
						echo '</a></li>';
					}
					echo '</ul></li>';
					$mc++;
				}
			}
			?>
					<li><a href="running_chart.php">Running Chart</a></li>
					<li><a href="tabular.php">Tabular</a></li>
    				</ul>
			</div>
		</div>
	</div>
	<div class="container-fluid">
	    <?php
	    $dir_path = $base_path . (isset($_GET['path']) ? $_GET['path'] : 'fsync_safe/pmfs/nojournal') . '/';
	    $workloads = scandir($dir_path);
            $charts = array();

	    foreach($workloads as $workload) {
		if ($workload == '.' || $workload == '..') continue;
		echo '<div class="row row-fluid">';
		echo '<div class="span10 offset1 hero-unit">';
		echo '<h1>Workload ' . strtoupper($workload) . '</h1>';
		echo '</div>';

		foreach(array('histogram', 'timeseries') as $type) {
			foreach(array('load', 'run') as $mode) {
				$data = explode("\n", file_get_contents($dir_path . $workload . "/{$type}_{$mode}.txt"));
				$data = array_slice($data, 4);
				$data = array_slice($data, 0, count($data) - 2);
				$data = array_map(function($v) { return explode(',', $v); }, $data);
				echo '<div class="span10 offset1">';
				echo '<h2>' . ucfirst($type . ' ( ' . $mode) . ' )</h2>';
				echo '<p><strong>Overall' . $data[0][1] . ':' . $data[0][2] . '</strong></p>';
				echo '<p><strong>Overall' . $data[1][1] . ':' . $data[1][2] . '</strong></p>';
				$data = process_data(array_slice($data, 2));

				echo '<div class="row">';
				$ops = array_keys($data);
				foreach($ops as $op) {
					echo '<div class="span' . (12 / count($ops)) . '">';
					echo '<div id="chart_' . count($charts) .
						'" style="min-width: 310px; height: 400px; margin: 0 auto"></div>';
					if ($type == 'histogram') {
						$data[$op]['data']['>1000'] = $data[$op]['stat']['>1000'];
						$charts[] = '$(\'#chart_' . count($charts) . '\').highcharts(' .
						json_encode(pie_chart(prep_pie($data[$op]['data'], intval($data[$op]['stat']['Operations']), 30 / count($ops)),
							'Operations per Bucket', $op)) . ');';
					}
					else {
						$charts[] = '$(\'#chart_' . count($charts) . '\').highcharts(' .
						json_encode(running_chart(sample($data[$op]['data'], 40 / count($ops)), 'Average Latency', $op, '')) . ');';
					}
					echo '</div>';
				}
				echo '</div>';
				echo '<hr />';
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
