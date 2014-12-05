<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
//ini_set('register_globals', 'off');
header("Content-type: text/json");

$train = $_REQUEST['train'];
$station = '%'.$_REQUEST['station'].'%';
$direction = $_REQUEST['direction'];
$debug = false;
$cacheTime = 3600*24*7;

if (is_numeric($direction) && isset($train) && isset($station)) {
	include('db.settings.php');
	
	//$db = mysql_select_db($database, $connect);
	if ($db){
		/*----- find station -----*/
		$db->SetFetchMode(ADODB_FETCH_ASSOC);
		$db->cacheSecs = $cacheTime;
		$stop_id_query = "SELECT * FROM stops WHERE stop_name LIKE ? AND location_type <= 1";
		$stop_id_data =& $db->CacheExecute($stop_id_query, array($station));
		$stop_ids = array();

		while (!$stop_id_data->EOF) {
			array_push($stop_ids, $stop_id_data->fields['stop_id']);
			$stop_id_data->MoveNext();
		}

		// DEBUG
		if ($debug) echo '<p>You have selected a <strong>'. ($direction == 0 ? 'Northbound #' : 'Southbound #') .$train .'</strong> train arriving at the <strong>'.$station.' Station</strong>.</p><pre>'; 
		if ($debug) print_r($stop_ids);
		
		/*----- trips by day -----*/
		$day = strtoupper(date('D'));
		if ($day != 'SAT' || $day != 'SUN') {
			$day = '%WKD';
		} else {
			$day = '%'.$day;
		}
		$service_id_query = "SELECT * FROM calendar WHERE service_id LIKE ?";
		$service_id_data =& $db->CacheExecute($service_id_query, array($day));
		$service_ids = array();
		
		while (!$service_id_data->EOF) {
			array_push($service_ids, $service_id_data->fields['service_id']);
			$service_id_data->MoveNext();
		}
		
		if ($debug) print_r($service_ids); // DEBUG
		$o=0;
		
		/*----- trip ids based on direction and route -----*/
		$trip_id_query = "SELECT * FROM trips WHERE service_id = ? AND direction_id = ? AND route_id = ?";
		for ($i=0;$i<count($service_ids);$i++){
			$trip_id_data =& $db->CacheExecute($trip_id_query, array($service_ids[$i], $direction, $train));
			if ($trip_id_data->RecordCount() > 0) {
				$service_id = $service_ids[$i];
				$o .= 1;
			}
		}
 		
		if ($debug) echo 'service id: '.$service_id; // DEBUG
       
		/*----- loop thru each stop id and each trip id -----*/
		$final = array();
		$time_now = date("H:i:00");
		$final_query = "SELECT * FROM trips LEFT JOIN (stop_times) ON (stop_times.trip_id=trips.trip_id) WHERE stop_id = ? AND service_id = ? AND direction_id = ? AND route_id = ? AND arrival_time > ? ORDER BY arrival_time LIMIT 2";
		for ($i=0;$i<count($stop_ids);$i++){
			$final_data =& $db->CacheExecute($final_query, array($stop_ids[$i], $service_id, $direction, $train, $time_now));
	 		
			if ($debug) echo '<br>matched: '.$final_data->RecordCount(); // DEBUG
			if ($final_data->RecordCount() > 0) {
				while (!$final_data->EOF){
					array_push($final,$final_data->fields['arrival_time']);
					$final_data->MoveNext();
				}
			}
		}
		
		/*----- return final values and format in json array -----*/
		$return = array();
		foreach($final as $key=>$val){
			array_push($return,date("g:i A", strtotime($val)));
		}
		if (count($stop_ids) == 0) {
			$return = array('Error', 'Missing', 'Variables');
		}
		
		// DEBUG
		if ($debug) {
			echo '<br/>';
			print_r($final);
			echo 'service ids: '.$o.'</pre>';
		}
	} else {
		$return = array('Database', 'Error', 'Variables');
	}
} else {
	$return = array('Error', 'Missing', 'Variables');
}
echo json_encode($return);
