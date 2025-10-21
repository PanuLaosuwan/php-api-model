<?php
header("Content-Type: application/json; charset=UTF-8");

include 'mysqldb.php';

////////// Get max date ////////// Start
$max_cfsv2 = "";
$max_nwp = "";

$get_csfv2_maxdate_sql = "SELECT MAX(pre_date) AS date, source FROM presideflow WHERE source = 'HM-CFSV2'";
$get_csfv2_maxdate_result = $conn->query($get_csfv2_maxdate_sql);

if ($get_csfv2_maxdate_result->num_rows > 0) {
    while($get_csfv2_maxdate_row = $get_csfv2_maxdate_result->fetch_assoc()) {
        $max_cfsv2 = $get_csfv2_maxdate_row["date"];
    }
}

$get_nwp_maxdate_sql = "SELECT MAX(pre_date) AS date, source FROM presideflow WHERE source = 'HM-NWP'";
$get_nwp_maxdate_result = $conn->query($get_nwp_maxdate_sql);

if ($get_nwp_maxdate_result->num_rows > 0) {
    while($get_nwp_maxdate_row = $get_nwp_maxdate_result->fetch_assoc()) {
        $max_nwp = $get_nwp_maxdate_row["date"];
    }
}
////////// Get max date ////////// End

////////// Get min date ////////// Start
$min_cfsv2 = "";
$min_nwp = "";

$get_csfv2_mindate_sql = "SELECT MIN(pre_date) AS date, source FROM presideflow WHERE source = 'HM-CFSV2'";
$get_csfv2_mindate_result = $conn->query($get_csfv2_mindate_sql);

if ($get_csfv2_mindate_result->num_rows > 0) {
    while($get_csfv2_mindate_row = $get_csfv2_mindate_result->fetch_assoc()) {
        $min_cfsv2 = $get_csfv2_mindate_row["date"];
    }
}

$get_nwp_mindate_sql = "SELECT MIN(pre_date) AS date, source FROM presideflow WHERE source = 'HM-NWP'";
$get_nwp_mindate_result = $conn->query($get_nwp_mindate_sql);

if ($get_nwp_mindate_result->num_rows > 0) {
    while($get_nwp_mindate_row = $get_nwp_mindate_result->fetch_assoc()) {
        $min_nwp = $get_nwp_mindate_row["date"];
    }
}
////////// Get min date ////////// End

////////// Dump CSFV2 ////////// Start
$chk_update_sql = "SELECT pre_date, source FROM presideflow_lake 
WHERE pre_date = '$max_cfsv2' AND source = 'HM-CFSV2'";
$chk_update_result = $conn->query($chk_update_sql);

if ($chk_update_result->num_rows > 0) {
    exit;
}
else{
    $data_sql = "SELECT pre_date, station, source, pred_sf_value FROM presideflow WHERE source = 'HM-CFSV2'";
    $data_result = $conn->query($data_sql);
    
    $result = [];
    while($data_row = $data_result->fetch_assoc()) {
        $result[] = $data_row;
    }

    //******** Delete old data CSFV2 ********// Start
    $stmt = $conn->prepare("DELETE FROM presideflow_lake WHERE  pre_date >= ? AND source = ?");
    $del_source = 'HM-CFSV2';
    $stmt->bind_param("ss", $min_cfsv2 , $del_source);
    $stmt->execute();
    //******** Delete old data CSFV2 ********// End

    $stmt = $conn->prepare("INSERT INTO presideflow_lake (pre_date, station, source, pred_sf_value) VALUES ( ?, ?, ?, ?)");
    $stmt->bind_param("ssss", $pre_date, $station, $source, $pred_sf_value);
    
    foreach ($result as $item) {
        $pre_date = $item['pre_date'];
        $station = $item['station'];
        $source = $item['source'];
        $pred_sf_value = $item['pred_sf_value'];
        $stmt->execute();
    }
    $stmt->close();
}

////////// Dump CSFV2 ////////// End

////////// Dump NWP ////////// Start
$chk_update_sql = "SELECT pre_date, source FROM presideflow_lake 
WHERE pre_date = '$max_nwp' AND source = 'HM-NWP'";
$chk_update_result = $conn->query($chk_update_sql);

if ($chk_update_result->num_rows > 0) {
    exit;
}
else{
    $data_sql = "SELECT pre_date, station, source, pred_sf_value FROM presideflow WHERE source = 'HM-NWP'";
    $data_result = $conn->query($data_sql);
    
    $result = [];
    while($data_row = $data_result->fetch_assoc()) {
        $result[] = $data_row;
    }

    //******** Delete old data NWP ********// Start
    $stmt = $conn->prepare("DELETE FROM presideflow_lake WHERE  pre_date >= ? AND source = ?");
    $del_source = 'HM-NWP';
    $stmt->bind_param("ss", $min_nwp , $del_source );
    $stmt->execute();
    //******** Delete old data NWP ********// End

    $stmt = $conn->prepare("INSERT INTO presideflow_lake (pre_date, station, source, pred_sf_value) VALUES ( ?, ?, ?, ?)");
    $stmt->bind_param("ssss", $pre_date, $station, $source, $pred_sf_value);
    
    foreach ($result as $item) {
        $pre_date = $item['pre_date'];
        $station = $item['station'];
        $source = $item['source'];
        $pred_sf_value = $item['pred_sf_value'];
        $stmt->execute();
    }
    $stmt->close();
}

////////// Dump NWP ////////// End

$conn->close();
?>