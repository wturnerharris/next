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
	private function get_stops() {
		$sql = "SELECT * FROM stops WHERE location_type = 1;";
		$query = $this->mysqli->query($sql);
		$result = $query->num_rows ? $query->fetch_all(MYSQLI_ASSOC) : false;
		return $result;
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
				
				$times = get_times_by_stop_id();
			break;
			default:
				$stops = $this->get_stops();
				$json = array( 'status' => ($stops?200:400), 'payload' => $stops); 
			break;
		}
		return $json;
	}
}
new Next_Train_API();
