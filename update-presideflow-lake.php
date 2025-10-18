<?php
header("Content-Type: application/json; charset=UTF-8");

include 'mysqldb.php';

$get_mcdate_sql = "SELECT MAX(pre_date) AS date, source FROM presideflow GROUP BY source";
$get_mcdate_result = $conn->query($get_mcdate_sql);

$max_cfsv2 = "";
$max_nwp = "";

if ($get_mcdate_result->num_rows > 0) {
    while($get_mcdate_row = $get_mcdate_result->fetch_assoc()) {
        if($get_mcdate_row["source"] == "HM-CFSV2")
            $max_cfsv2 = $get_mcdate_row["date"];
        if($get_mcdate_row["source"] == "HM-NWP") 
            $max_nwp = $get_mcdate_row["date"];
    }
}

$chk_update_sql = "SELECT pre_date, source FROM presideflow_lake 
WHERE (pre_date = '$max_cfsv2' AND source = 'HM-CFSV2') OR (pre_date = '$max_nwp' AND source = 'HM-NWP') GROUP BY source ,pre_date";
$chk_update_result = $conn->query($chk_update_sql);

if ($chk_update_result->num_rows > 0) {
    exit;
}
else{
    $data_sql = "SELECT pre_date, station, source, pred_sf_value FROM presideflow";
    $data_result = $conn->query($data_sql);

    $result = [];
    while($data_row = $data_result->fetch_assoc()) {
        $result[] = $data_row;
    }

    $stmt = $conn->prepare("INSERT INTO presideflow_lake (stamp_date, pre_date, station, source, pred_sf_value) VALUES (CURRENT_TIMESTAMP(), ?, ?, ?, ?)");
    $stmt->bind_param("sssd", $pre_date, $station, $source, $pred_sf_value);

    foreach ($result as $item) {
        $pre_date = $item['pre_date'];
        $station = $item['station'];
        $source = $item['source'];
        $pred_sf_value = $item['pred_sf_value'];
        $stmt->execute();
    }
    $stmt->close();
}

$conn->close();
?>