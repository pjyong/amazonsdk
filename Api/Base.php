<?php
/**
 * Name:
 *	Base.php
 *
 * Description:
 *	Base class for all objects
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Api\Server;

class Base{

	public function checkParameters(Request $request, $arr = array()){
		// necessary parameters
		// fromEmail,to,subject,body
		$errorMsg = '';
		$errorCode = 0;
		foreach($arr as $a){
			if(!$request->request->get($a)){
				if($errorCode == 1){
					$errorMsg .= ',';
				}
				$errorMsg .= "'" . $a . "'";
				$errorCode = 1;
			}
		}

		if($errorCode){
			$errorMsg = 'The following parameters are necessary: [' . $errorMsg . ']';
			return array('errorCode' => 1, 'errorMsg' => $errorMsg);
		}

		return false;
	}
	
}