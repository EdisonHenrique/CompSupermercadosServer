<?php

	function output_json_response(int $resultCode, $result) {
		$response = [
			"result_code" => $resultCode,
			"result" => $result
		];
		echo json_encode($response);
	}

	
	function exit_with_error_response(string $message, int $errorCode=-1) {
		output_json_response($errorCode, $message);
		exit;
	}

	
	function request_param_valid(string $requestMethod, string $parameter) { 
		switch (strtolower(trim($requestMethod))) {
			default:
				throw new Exception("Invalid request method '$requestMethod'");
				break;	
				
			case "get":
				$requestVar = $_GET;
				break;
				
			case "post":
				$requestVar = $_POST;
				break;
		}
		
		if (
			!array_key_exists($parameter, $requestVar) 
			or !isset($requestVar[$parameter])
			or $requestVar[$parameter] == ""
		) {
			return FALSE;
		}
		
		return TRUE;
	}
	

	function check_request_params(string $requestMethod, array $parameters) { 
		foreach ($parameters as $parameter) {
			if (!request_param_valid($requestMethod, $parameter)) {
				exit_with_error_response("Invalid or missing parameter: $parameter");
			}
		}
		return TRUE;
	}

?>