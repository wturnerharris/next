<?php
	include("adodb-exceptions.inc.php");
	include('adodb.inc.php');
	
	$host = "127.0.0.1";
	$data = "mta_data";
	$user = "root";
	$pass = "apple";
	
	$dsn = 'mysql://'.$user.':'.$pass.'@'.$host.'/'.$data.'?persist';
	//$db = NewADOConnection($dsn);
	try { 
		$db = NewADOConnection($dsn);
	} catch (exception $e) { 
		var_dump($e); 
		adodb_backtrace($e->gettrace());
	} 
?>