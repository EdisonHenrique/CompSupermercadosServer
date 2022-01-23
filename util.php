<?php

		function finish_with_json_response(int $resultCode, $result) {
		$response = [
			"result_code" => $resultCode,
			"result" => $result
		];
		echo json_encode($response);
		exit;
	}


	function throw_exception_response(string $message, int $errorCode=-1) {
		$message = preg_replace("/[[:blank:]]+/", " ", $message); // erros SQL ficam com um monte de espaços; essa linha corrige isso
		finish_with_json_response($errorCode, $message);
	}
	
	
	function request_param_valid(string $requestMethod, string $parameter) { 
		switch (strtolower(trim($requestMethod))) {
			default:
				throw new Exception("Invalid request method '$requestMethod'");
				break;
			case "get":
				$superglobalName = $_GET;
				break;
			case "post":
				$superglobalName = $_POST;
				break;
			case "server":
				$superglobalName = $_SERVER;
				break;
		}
		
		if (
			!array_key_exists($parameter, $superglobalName) 
			or !isset($superglobalName[$parameter])
			or $superglobalName[$parameter] == ""
		) {
			return FALSE;
		}
		
		return TRUE;
	}
	

	function check_superglobal_params(string $requestMethod, array $parameters) { 
		foreach ($parameters as $parameter) {
			if (!request_param_valid($requestMethod, $parameter)) {
				throw_exception_response("Invalid or missing parameter: $parameter");
			}
		}
		return TRUE;
	}


	function var_or_default(&$var, $default="") {
		return isset($var) ? $var : $default;
	}


	function run_query(string $query, bool $returnErrorInsteadOfExiting=false) {
		// Attempt server connection
		$conn = pg_connect(getenv("DATABASE_URL"));
		if (!$conn){
			throw_exception_response("Server connection failed");
		}

		// Run SQL query
		$result = pg_query($conn, $query);
		$error = pg_last_error($conn);

		// Close server connection 
		pg_close($conn);

		if (!$result) {
			if ($returnErrorInsteadOfExiting){
				return $error;
			} else {
				throw_exception_response($error);
			}
		}

		return pg_fetch_all($result);
	}

?>