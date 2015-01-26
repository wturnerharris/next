<?php

class Next_Train_API {
	private $response, 
			$mysqli;

	public 	$method, 
			$action;

	function __construct(){
		// TODO: Add better security like a api key/secret
		$mysqli         = new mysqli("127.0.0.1","root","apple","mta_data");
		$this->method   = $_SERVER['REQUEST_METHOD'];
		$this->action   = @$_REQUEST['action'];
		$this->mysqli   = $mysqli;
		$this->response = ($this->mysqli->connect_errno) ? array(
			"status" => "({$this->mysqli->connect_errno}) {$this->mysqli->connect_error}"
		) : $this->do_action($this->action);
		header("Content-type: text/json");
		echo json_encode($this->response);
		mysqli_close($this->mysqli);
		exit;
	}
	
	// return random entry
	private function get_nearest_stops() {
		$sql = "SELECT stop_id, stop_lat, stop_lon FROM stops WHERE location_type = 1;";
		$query = $this->mysqli->query($sql);
		$result = $query->num_rows ? $query->fetch_all(MYSQLI_ASSOC) : array();
		$locations = array();

		foreach( $result as $train ) {
			$locations[$train['stop_id']] = array(
				'lat' => $train['stop_lat'],
				'lon' => $train['stop_lon']
			);
		}

		return $this->get_nearest_trains($locations);
	}

	private function get_times_by_stop_id($train_id, $limit = 5, $cardinality = '%') {
		$train = $train_id.$cardinality;
		$sql = sprintf("SELECT * FROM stop_times WHERE arrival_time >= CURTIME() 
			AND arrival_time <= CURTIME() + INTERVAL 5 MINUTE
			AND stop_id IN (%s) LIMIT %d;", $train, $limit);

		$query = $this->mysqli->query($sql);
		$result = $query->num_rows ? $query->fetch_all(MYSQLI_ASSOC) : false;
		return $result;
	}

	public function get_nearest_trains( $locations ){
		$lat = (float)@$_GET['lat'];
		$lon = (float)@$_GET['lon'];
		$threshold = 1; // in miles
		$distances = $return = array();
		foreach( $locations as $stop_id => $stop ) {
			$distance = $this->get_distance($lat, $lon, (float)$stop['lat'], (float)$stop['lon']);
			if ( $distance <= $threshold ) $distances[$stop_id] = $distance;
		}

		// compare the distances in the array
		asort($distances);
		reset($distances);
		
		foreach ( $distances as $stop_id => $distance) {
			array_push( $return, array( $stop_id => $distance ));
		}
		return $return;
	}

	/**
	 * This routine calculates the distance between two points (given the
	 * latitude/longitude of those points). It is being used to calculate
	 * the distance between two locations using GeoDataSource(TM) Products
	 *
	 * @param   $args array latitude/longitude coordinates for two locations
	 * @return  float distance
	 */
	public function get_distance($lat1, $lon1, $lat2, $lon2, $unit = "M") {
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2));
		$dist += (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		
		switch( strtoupper($unit) ) {
			case 'K' : return ($miles * 1.609344);
			case 'N': return ($miles * 0.8684);
			case 'M': return $miles;
		}
	}

	public function is_valid_request($action) {
		$map = array(
			'POST' => array(
				//'submit', 'logSubmission'
			),
			'GET' => array(
				'getTrains', 'getTimes'
			),
		);
		return in_array($action, $map[$this->method]);
	}
	
	public function do_action($action = 'check') {
		if ( !$this->is_valid_request($action) ) {
			return array('status' => 'INVALID_API_REQUEST');
		}
		switch($action) {
			case 'getTimes':
				$train_id = @$_GET['train_id'];
				$limit = @$_GET['limit'];
				$direction = @$_GET['direction'];
				
				$times = get_times_by_stop_id($train_id);
			break;
			case 'getTrains':
				$json = array( 
					'status' => ($stops?200:400), 
					'payload' => $this->get_nearest_stops(),
				); 
			break;
		}
		return $json;
	}
}
new Next_Train_API();
