<?php

class Next_Train_API {
	private $response, 
			$mysqli,
			$calendar;

	public 	$method, 
			$action;

	function __construct(){
		date_default_timezone_set('America/New_York');
		$mysqli         = new mysqli("127.0.0.1","witdesig_mta","author!$3d","witdesig_mta_data");
		$this->method   = $_SERVER['REQUEST_METHOD'];
		$this->action   = @$_REQUEST['action'];
		$this->mysqli   = $mysqli;
		$this->calendar = $this->get_service_calendar();
		$this->response = ($this->mysqli->connect_errno) ? array(
			"status" => "({$this->mysqli->connect_errno}) {$this->mysqli->connect_error}"
		) : $this->do_action($this->action);
		header("Content-type: text/json");
		echo json_encode($this->response);
		mysqli_close($this->mysqli);
		exit;
	}
	
	private function get_nearest_stops($limit = 10, $unit = 'M') {
		$lat = deg2rad( (float)@$_GET['lat'] );
		$lon = (float)@$_GET['lon'];
		$dir = (int)@$_GET['dir'];
		$direction = $dir > 0 ? 'N' : 'S';
		$distance_threshold = 0.6;
		
		switch( strtoupper($unit) ) {
			case 'K' : $factor = 1.609344; break;
			case 'N': $factor = 0.8684; break;
			default: $factor = 1;
		}
		$sql = sprintf("SELECT stop_id, stop_lat, stop_lon, 
			(
				DEGREES(
					ACOS(
						( SIN( $lat ) * SIN( RADIANS(stop_lat) ) ) + (
							COS( $lat ) * COS( RADIANS(stop_lat) ) * cos( RADIANS($lon - stop_lon) )
						)
					)
				) * 60 * 1.1515 * $factor
			) AS distance FROM stops WHERE location_type = 1 ORDER BY distance ASC LIMIT 10;");

		$query = $this->mysqli->query($sql);
		$result = $query->num_rows ? $query->fetch_all(MYSQLI_ASSOC) : array();
		
		$trains = array();
		foreach( $result as $stop ) {
			if ( $stop['distance'] > $distance_threshold ) continue;
			$stop_id = $stop['stop_id'];
			$times = $this->get_times_by_stop_id($stop_id, $direction);
			foreach( $times as $idx => $time ) {
				$times[$idx]['ts'] = strtotime( $time['arrival_time'] );
			}
			$next_train = @array_shift($times);
			$trains[] = array(
				'stop_id' => (string)$stop_id,
				'distance' => number_format($stop['distance'], 4),
				'next_train' => $next_train,
				'trains' => $times,
				'direction' => $direction
			);
		}
		return $trains;
	}
	
	private function get_nearest_stops_all() {
		$sql = sprintf("SELECT stop_id, stop_lat, stop_lon FROM stops WHERE location_type = 1;");
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
	
	private function get_nearest_trains( $locations ){
		$lat = (float)@$_GET['lat'];
		$lon = (float)@$_GET['lon'];
		$dir = (int)@$_GET['dir'];
		$direction = $dir > 0 ? 'N' : 'S';
		$threshold = 0.5; // in miles
		$distances = $trains = array();
		foreach( $locations as $stop_id => $stop ) {
			$distance = $this->get_distance($lat, $lon, (float)$stop['lat'], (float)$stop['lon']);
			if ( $distance <= $threshold ) $distances[$stop_id] = $distance;
		}

		// compare the distances in the array
		asort($distances);
		reset($distances);
		
		foreach( $distances as $stop_id => $distance ) {
			$times = $this->get_times_by_stop_id($stop_id, $direction);
			$trains[] = array(
				'stop_id' => (string)$stop_id,
				'distance' => $distance,
				'trains' => $times,
				'direction' => $direction
			);
		}
		
		return $trains ? $trains : array(
			'stop_ids' => array_keys($distances),
			'distances' => array_values($distances) 
		);
	}

	private function get_service_calendar() {
		$day_of_week = date('l');
		$sql = "SELECT service_id FROM calendar WHERE $day_of_week = 1";
		return $sql;
	}

	private function get_times_by_stop_id($stop_id, $cardinality = 'N', $limit = 10) {
		$stop_id .= $cardinality;
		$sql = sprintf("SELECT stop_times.arrival_time, trips.route_id, trips.trip_headsign, stops.stop_name
			FROM stop_times, trips, stops WHERE arrival_time >= CURTIME() 
			AND arrival_time <= CURTIME() + INTERVAL 25 MINUTE
			AND stop_times.stop_id = '%s'
			AND trips.service_id = ANY ( %s )
			AND stop_times.stop_id = stops.stop_id
			AND stop_times.trip_id = trips.trip_id 
			ORDER BY arrival_time ASC LIMIT %d;", $stop_id, $this->calendar, $limit);

		$query = $this->mysqli->query($sql);
		$result = $query->num_rows ? array_values($this->fetch_all($query)) : false;
		return $result;
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
				$stop_id = @$_GET['stop_id'];
				$limit = @$_GET['limit'];
				$dir = @$_GET['dir'];
				$direction = $dir > 0 ? 'N' : 'S';
				
				$times = get_times_by_stop_id($stop_id, $direction);
			break;
			case 'getTrains':
				$stops = $this->get_nearest_stops();
				$json = array( 
					'status' => (!empty($stops)?200:400), 
					'payload' => $stops,
				); 
			break;
		}
		return $json;
	}
}
new Next_Train_API();
