<?php
header("Content-Type: application/json; charset=UTF-8");

include '../mysqldb.php';

function NoResult($desc){
    echo json_encode([
        "status" => "success",
        "description" => "No result."."($desc)",
        "data" => Null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$indexName = array("date", "station", "source", "pred_sf_value");
$data = [];

$data_sql = "SELECT pre_date as date, station, source, pred_sf_value FROM presideflow";
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
    NoResult("ERR01");
}
/*
$current_date = "";

$get_date_sql = "SELECT CAST(MAX(stamp_date) AS DATE) AS CurrentDate FROM presideflow";
$get_date_result = $conn->query($get_date_sql);

if ($get_date_result->num_rows > 0) {
    if($get_date_row = $get_date_result->fetch_assoc()) {
        $current_date = $get_date_row["CurrentDate"];
        $current_date = "2024-01-01";

        $data_sql = "SELECT pre_date as date, station, source, pred_sf_value FROM presideflow WHERE stamp_date LIKE '$current_date%'";
        $data_result = $conn->query($data_sql);
        if ($data_result->num_rows > 0) {
            while($data_row = $data_result->fetch_assoc()) {
                $data[] = $data_row;
            }
            echo json_encode([
                "status" => "success",
                "description" => "Updated $current_date",
                "data" => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        else {
            NoResult("ERR03");
        }
    }
    else {
        NoResult("ERR02");
    }
}
else {
    NoResult("ERR01");
}
*/
$conn->close();
?>