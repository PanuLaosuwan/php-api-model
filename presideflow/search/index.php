<?php
header("Content-Type: application/json; charset=UTF-8");

include '../../mysqldb.php';

function normalizeDate($input) {
    try {
        $date = new DateTime($input);
        return $date->format('Y-m-d');
    }
    catch (Exception $e) {
        return null;
    }
}

function NoResult($desc){
    echo json_encode([
        "status" => "success",
        "description" => "No result."."($desc)",
        "data" => Null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$station = $_GET['station'] ?? null;
$source  = $_GET['source']  ?? null;
$date  = $_GET['date']  ?? null;
$duration  = $_GET['duration']  ?? null;

$date = normalizeDate($date);

if($station && $source && $date && $duration){
    $start_date = $date;
    $end_date = date('Y-m-d', strtotime($start_date . ' + '.$duration.'  days'));
    //$end_date = date('Y-m-d', strtotime($end_date . ' + 1  days'));
    $start_date = date('Y-m-d', strtotime($start_date . ' - 1 days'));
    $data_sql = 
    "SELECT pre_date as date, station, source, pred_sf_value 
    FROM presideflow_lake 
    WHERE 
    station = '$station' 
    AND source = '$source' 
    AND stamp_date LIKE '$date%' AND pre_date > '$start_date' AND pre_date < '$end_date'";
    $data_result = $conn->query($data_sql);
    if ($data_result->num_rows > 0) {
        while($data_row = $data_result->fetch_assoc()) {
            $data[] = $data_row;
        }
        echo json_encode([
            "status" => "success",
            "description" => "Total number of data : ".count($data),
            "data" => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    else {
            NoResult("ERR02");
    }
}
else {
    NoResult("ERR01 : Missing parameter");
}

$conn->close();
?>