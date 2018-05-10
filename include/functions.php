<?php
function verifyRequiredParams($requiredKeys='', $requiredValues='', $errorcode) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    /** key empty or not */
    if (!empty( $requiredKeys )){
	    foreach ($requiredKeys as $field) {
	        if (!isset($request_params[$field]) ) {
	            $error = true;
	            $error_fields .= $field . ', ';
				 
	        }
	    }
    }

    if ($error) {
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["errorCode"] = $errorcode;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing';
        echoResponse($response);
        $app->stop();
    }else{
    	/** value empty or not */
    	$error1 = false;
    	$error_fields1 = "";
    	if (!empty( $requiredValues )) {
	    	foreach ($requiredValues as $field) {
	    		if (empty($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
	    			$error1 = true;
	    			$error_fields1 .= $field . ', ';
	    	
	    		}
	    	}
    	}
    	
    	if ($error1) {
    		$response = array();
    		$app = \Slim\Slim::getInstance();
    		$response["errorCode"] = $errorcode;
    		$response["message"] = 'Required field(s) ' . substr($error_fields1, 0, -2) . ' is empty';
    		echoResponse($response);
    		$app->stop();
    	}
    }
}

/*function verifyRequiredValues($required_fields,$errorcode) {
	$error = false;
	$error_fields = "";
	$request_params = array();
	$request_params = $_REQUEST;
	if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
		$app = \Slim\Slim::getInstance();
		parse_str($app->request()->getBody(), $request_params);
	}

	foreach ($required_fields as $field) {
		if (empty($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
			$error = true;
			$error_fields .= $field . ', ';

		}
	}

	if ($error) {
		$response = array();
		$app = \Slim\Slim::getInstance();
		$response["errorCode"] = $errorcode;
		$response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is empty';
		echoResponse($response);
		$app->stop();
	}
}*/

function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["errorCode"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse($response);
        $app->stop();
    }
}

function echoResponse($response) {
    $app = \Slim\Slim::getInstance();
    $app->contentType('application/json');
    echo json_encode($response,JSON_UNESCAPED_SLASHES);		
}

function validateParamsAPISecret($securekey, $params) {
	$postparams=$params; 
	if($securekey==$postparams){
		$status=1;
	}else{
		$status=0;
	}
	
	return $status;
}
?>