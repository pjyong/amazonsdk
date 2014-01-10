<?php
/**
 * Name:
 *	Command.php
 *
 * Description:
 *	Assign the command to corresponding object
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Command{

	public static function excute($command = ''){
		$request = Request::createFromGlobals();
		if(!$command){
			$command = $request->query->get('command');
		}
		// verify the command format
		$commandArr = self::parseCommand($command);
		if(!$commandArr){
			// throw command error
			$response = new Response();
			$response->setContent(json_encode(array('errorCode' => 1, 'errorMsg' => "Command doesn't exist.")));
			$response->headers->set('Content-Type', 'application/json');

			return $response;
		}
		$obj = new $commandArr['className']();
		
		return $obj->$commandArr['methodName']($request);
	}

	public static function parseCommand($command){
		$parts = explode('/', $command);

		if(count($parts) != 2){
			return false;
		}
		$className = 'Api\\'. $parts[0];
		$functionName = $parts[1];
		if(!class_exists($className) || !method_exists($className, $functionName)){
			return false;
		}

		return array('className' => $className, 'methodName' => $functionName);
	}

}