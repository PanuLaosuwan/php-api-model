<?php
header("Content-Type: application/json; charset=UTF-8");

$csfv2File = __DIR__ . "/Model_Runoff_Sideflow_csfv2.csv";
$NWPFile = __DIR__ . "/Model_Runoff_Sideflow_NWP.csv";

if (!file_exists($csfv2File)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found"]);
    exit;
}

if (!file_exists($NWPFile)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found"]);
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
    $cnt = 0;
    for($i = 5; $i < count($rows); $i++){
        foreach ($rows[$i] as $key => $val){
            if($cnt == 0) $date = $val;
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
    $cnt = 0;
    for($i = 5; $i < count($rows); $i++){
        foreach ($rows[$i] as $key => $val){
            if($cnt == 0) $date = $val;
            else{
                $result[] = array_combine($indexName, array($date, $key, "HM-NWP", $val));
            }
            $cnt++;
        }
    }
}

echo json_encode([
    "status" => "success",
    //"count" => count($result),
    "data" => $result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
