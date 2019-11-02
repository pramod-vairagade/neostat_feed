<?php
if(isset($_GET['submit']) && !empty($_GET['submit']))
{
    $method = $_SERVER['REQUEST_METHOD'];
    $data = array('start_date' => $_GET['start_date'],
        'end_date' => $_GET['end_date'],
        'api_key' => 'cRgktW5ZKJH6oZSJ4hMTcfQDeeKCQsKujvTjIjlt');
    $url = 'https://api.nasa.gov/neo/rest/v1/feed';
    $neoStatResponse = callApi($method, $url, $data);
    $neoFeed = json_decode($neoStatResponse);
    $asteroidsPerDay = array();
    $asteroidsSpeedDistance = array();
    $datewiseAsteroidsSize = array();
    $asteroidsSize = array();
    foreach ($neoFeed->near_earth_objects as $eachDay => $numberOfAsteroids) {
        $asteroidsPerDay[] = array('each_day' => $eachDay, 'number_of_asteroids' => count($numberOfAsteroids));
        foreach ($numberOfAsteroids as $asteroidKey => $asteroidValue) {
            foreach ($asteroidValue->estimated_diameter as $estimatedDiameterKey => $estimatedDiameterValue) {
                if ($estimatedDiameterKey == 'kilometers') {
                    //as exact diameter not given ,assume to be calculated as below
                    $diameter = ($estimatedDiameterValue->estimated_diameter_min + $estimatedDiameterValue->estimated_diameter_max) / 2;
                    $asteroidsSize[] = $diameter;
                    $datewiseAsteroidsSize[$eachDay][] = $diameter;
                }
            }
            foreach ($asteroidValue->close_approach_data as $closeApproachKey => $closeApproachValue) {
                $asteroidsSpeedDistance[$asteroidKey]['asteroid_id'] = $asteroidValue->id;
                $asteroidsSpeedDistance[$asteroidKey]['speed'] = $closeApproachValue->relative_velocity->kilometers_per_hour;
                $asteroidsSpeedDistance[$asteroidKey]['distance'] = $closeApproachValue->miss_distance->kilometers;
            }
        }
    }
    $speed = array_column($asteroidsSpeedDistance, 'speed');
    $fastestAsteroid = $asteroidsSpeedDistance[array_search(max($speed), $speed)];
    $distance = array_column($asteroidsSpeedDistance, 'distance');
    $closestAsteroid = $asteroidsSpeedDistance[array_search(min($distance), $distance)];
    echo "<div><b>Fastest Asteroid in km/h (Respective Asteroid ID & its speed):</b></div>";
    echo "<div>Asteroid ID :" . $fastestAsteroid['asteroid_id'] . "</div>";
    echo "<div>Speed :" . $fastestAsteroid['speed'] . "</div>";
    echo "<div>&nbsp;</div>";
    echo "<div><b>Closest Asteroid (Respective Asteroid ID & its distance):</b></div>";
    echo "<div>Asteroid ID :" . $closestAsteroid['asteroid_id'] . "</div>";
    echo "<div>Distance :" . $closestAsteroid['distance'] . "</div>";
    echo "<div>&nbsp;</div>";
    echo "<div><b>Datewise Average Size of Asteroids in km:</b></div>";
    foreach ($datewiseAsteroidsSize as $sizeKey => $sizeValue) {
        $datewiseSizeAverage = array_sum($sizeValue) / count($sizeValue);
        echo "<div>$sizeKey : " . $datewiseSizeAverage . "</div>";
    }
    echo "<div>&nbsp;</div>";
    echo "<div><b>Average Size of Asteroids in km:</b></div>";
    $datewiseSizeAverage = array_sum($asteroidsSize) / count($asteroidsSize);
    echo "<div>Size : " . $datewiseSizeAverage . "</div>";
    echo "<div>&nbsp;</div>";
}
function callApi($method, $url, $data = false)
{
    $curl = curl_init();
    if ($method)
    {
        if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}
?>
<html>
<head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(drawChart);
        var asteroidsPerDay = <?php echo json_encode($asteroidsPerDay); ?>;
        var chartData=[];
        var header= ['Each Day', 'Number Of Asteroids', { role: 'style' }];
        chartData.push(header);
        for (var i = 0; i < asteroidsPerDay.length; i++) {
            var temp=[];
            temp.push(asteroidsPerDay[i].each_day);
            temp.push(asteroidsPerDay[i].number_of_asteroids);
            temp.push(asteroidsPerDay[i].blue);
            chartData.push(temp);
        }
        function drawChart() {
            var data = google.visualization.arrayToDataTable(chartData);
            var view = new google.visualization.DataView(data);
            view.setColumns([0, 1,
                { calc: "stringify",
                    sourceColumn: 1,
                    type: "string",
                    role: "annotation" },
                2]);
            var options = {
                title: "Number of asteroids per day",
                width: 600,
                height: 400,
                bar: {groupWidth: "95%"},
                legend: { position: "none" },
            };
            var chart = new google.visualization.BarChart(document.getElementById("barchart_values"));
            chart.draw(view, options);
        }
    </script>
</head>
<body>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="GET" >
        Start Date : <input type="date" name="start_date" value="<?php echo $_GET['start_date'] ?>">
        End Date :  <input type="date" name="end_date" value="<?php echo $_GET['end_date'] ?>">
        <input type="submit" name="submit">
</form>
<div id="barchart_values" style="width: 900px; height: 500px;"></div>
</body>
</html>
