<?php
header("Content-Type: application/json; charset=UTF-8");
include 'mysqldb.php';

function normalizeDate($input) {
    try {
        $date = new DateTime($input);
        return $date->format('Y-m-d');
    }
    catch (Exception $e) {
        return null;
    }
}
$csfv2File = __DIR__ . "/data/Model_Runoff_Sideflow_csfv2.csv";
$NWPFile = __DIR__ . "/data/Model_Runoff_Sideflow_NWP.csv";

if (!file_exists($csfv2File)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found csfv2"]);
    exit;
}

if (!file_exists($NWPFile)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found NWP"]);
    exit;
}

$rows = [];
$result = [];

$indexName = array("date", "station", "source", "pred_sf_value");
if (($handle = fopen($csfv2File, "r")) !== false) {
    $headers = fgetcsv($handle, 0, ",", '"', "\\"); // อ่านบรรทัดแรกเป็นหัวคอลัมน์
    while (($data = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
        $rows[] = array_combine($headers, $data);
    }
    
    fclose($handle);
    $date = "";
    for($i = 4; $i < count($rows); $i++){
        $cnt = 0;
        foreach ($rows[$i] as $key => $val){
            if($cnt == 0) $date = normalizeDate(substr($val, 1));
            else{
                $result[] = array_combine($indexName, array($date, $key, "HM-CFSV2", $val));
            }
            $cnt++;
        }
    }
}

$rows = [];

if (($handle = fopen($NWPFile, "r")) !== false) {
    $headers = fgetcsv($handle, 0, ",", '"', "\\"); // อ่านบรรทัดแรกเป็นหัวคอลัมน์
    while (($data = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
        $rows[] = array_combine($headers, $data);
    }
    
    fclose($handle);
    $date = "";
    for($i = 4; $i < count($rows); $i++){
        $cnt = 0;
        foreach ($rows[$i] as $key => $val){
            if($cnt == 0) $date = normalizeDate(substr($val, 1));
            else{
                $result[] = array_combine($indexName, array($date, $key, "HM-NWP", $val));
            }
            $cnt++;
        }
    }
}
if ($conn->query("TRUNCATE presideflow") === TRUE) {
    $stmt = $conn->prepare("INSERT INTO presideflow (stamp_date, pre_date, station, source, pred_sf_value) VALUES (CURRENT_TIMESTAMP(), ?, ?, ?, ?)");
    $stmt->bind_param("sssd", $pre_date, $station, $source, $pred_sf_value);

    foreach ($result as $item) {
        $pre_date = $item['date'];
        $station = $item['station'];
        $source = $item['source'];
        $pred_sf_value = $item['pred_sf_value'];
        $stmt->execute();
    }
    $stmt->close();
}
else {
    echo "Error truncating table: " . $conn->error;
    exit;
}

$conn->close();
?>