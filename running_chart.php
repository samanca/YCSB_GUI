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

function import_data($file_path) {
	$data = explode("\n", file_get_contents($file_path));
	$data = array_slice($data, 4);
	$data = array_slice($data, 0, count($data) - 2);
	$data = array_map(function($v) { return explode(',', $v); }, $data);

	$data = process_data(array_slice($data, 2));
	return $data;
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

function running_chart($cats, $data, $name, $title, $subtitle) {
    return array(
        'title' => array('text' => $title, 'x' => -20),
        'subtitle' => array('text' => $subtitle, 'x' => -20),
        'xAxis' => array('categories' => $cats),
        'yAxis' => array('title' => 'Average Latency (ns)', 'plotLines' => array(array('value' => 0, 'width' => 1, 'color' => '#808080'))),
        'tooltip' => array('valueSuffix' => 'ns'),
        'legend' => array('layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'middle', 'borderWidth' => 0),
        'series' => 
		array_map(function($key) use($data) {
			$t = $data[$key]['data'];
			return array(
				'name' => $key, 
				'data' => array_map(function($v) use($t) { 
					return floatval($t[$v]); 
				}, array_keys($data[$key]['data']))
			); }, array_keys($data))
    );
}


if (isset($_POST['input_data'])) {
	$lines = explode("\n", $_POST['input_data']);
	$charts = array_map(function($line) { return explode(',', $line); }, $lines);

	$mode = $_POST['mode'];

	$chart_data = array();
    $summary = array();
	foreach($charts as $t) {
		$data = import_data($t[0] . '/timeseries_' . $mode . '.txt');
		foreach(array_keys($data) as $op) {
            if (count($data[$op]['data']) > 1) $data[$op]['data'] = array_slice($data[$op]['data'], 1);
			$chart_data[$op][trim($t[1])] = sample($data[$op], 100);
            $summary[$op][$t[1]] = $data[$op]['stat'];
        }
	}
	
	foreach($chart_data as $key=>$value) {
		$name = substr($key, 1, strlen($key) - 2);		
		$chart[$name] = running_chart(array_keys($chart_data[$key][array_keys($value)[0]]['data']), $chart_data[$key], 'Average Latency', 'Average Latency', '');
	}

}
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
	    <form action="running_chart.php" method="POST">
		<div class="span4 offset1">		
		    <p>Please enter input in the following format:</p>
		    <p>[file path], [label]</p>
            <p>
                <select name="mode">
                    <option value="load" selected>Load</option>
                    <option value="run">Run</option>
                </select>
            </p>
		    <p><input type="submit" value="Submit" class="btn" /></p>
		</div>
		<div class="span6">
		    <textarea rows="6" cols="15" name="input_data"><?php if(isset($_POST['input_data'])) echo $_POST['input_data']; ?></textarea>
		</div>
	    </form>
	</div>
    <hr />
    <div class="row-fluid">
        <div class="span10 offset1">
            <?php if (isset($summary)): ?>
            <h1>Summary</h1>
            <?php
            $operations = array_keys($summary);
            $titles = array_keys($summary[$operations[0]]);
            ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <td>&nbsp;</td>
                        <?php
                        foreach($titles as $title) {
                            echo "<td>$title</td>";
                        }
                        ?>
                    </tr>
                </thead>
                <?php foreach($operations as $op) {
                    echo '<tr>';
                    echo "<td>$op</td>";
                    foreach($titles as $title) {
                        echo '<td>' . $summary[$op][$title]['AverageLatency(ns)'] . '</td>';
                    }
                    echo '</tr>';
                }
                ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
	<hr />
	<div class="row-fluid">
	    <div class="span10 offset1">
		<?php
		if (isset($chart))
			foreach($chart as $key=>$c) {
				echo "<h1>$key</h1>";
				echo "<div id=\"chart_$key\" style=\"min-width: 400px; height: 400px; margin: 0 auto\"></div>";
				echo '<hr />';
			}
		?>
	    </div>
	</div>
	</div>	
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>    

	<script src="highcharts-3.0.10/js/highcharts.js"></script>
	<script src="highcharts-3.0.10/js/modules/exporting.js"></script>
    </body>
</html>
