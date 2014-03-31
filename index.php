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

function running_chart($data) {
	return $data['data'];
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
	<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	    <?php
	    $dir_path = "/home/amirsaman/Desktop/experiments/single/pmfs/journal/";
	    $workloads = scandir($dir_path);

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
						echo '<p>Running Chart</p>';
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
        $('#container').highcharts({
            title: {
                text: 'Monthly Average Temperature',
                x: -20 //center
            },
            subtitle: {
                text: 'Source: WorldClimate.com',
                x: -20
            },
            xAxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yAxis: {
                title: {
                    text: 'Temperature (°C)'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: '°C'
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: 'Tokyo',
                data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6]
            }, {
                name: 'New York',
                data: [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5]
            }, {
                name: 'Berlin',
                data: [-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0]
            }, {
                name: 'London',
                data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8]
            }]
        });
    });
    

		</script>

	<script src="highcharts-3.0.10/js/highcharts.js"></script>
	<script src="highcharts-3.0.10/js/modules/exporting.js"></script>
    </body>
</html>
