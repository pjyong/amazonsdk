<?php
/**
 * Name:
 *	Stat.php
 *
 * Description:
 *	Some actions about email statistic
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\KeyType;
use Aws\DynamoDb\Enum\Select;
use Aws\DynamoDb\Enum\ProjectionType;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\Enum\ReturnValue;
use Aws\DynamoDb\Enum\ComparisonOperator;

use Api\Server;

class Stat extends Base{

	// If we want to use this functionnality for dev, we need to replace it with DEV LC domain,
	// don't end with slash.
	const LC_DOMAIN = 'http://67.72.16.196';

	// Fetch the email response from SNS, then update the corresponding record in Dynamo db.
	public function updateStat(Request $request){
		$server = Server::getInstance();
		$db = $server->getDb();
		$json = json_decode($request->getContent());

		//For Debugging.
		$logToFile = true;

		//For security you can (should) validate the certificate, this does add an additional time demand on the system.
		//NOTE: This also checks the origin of the certificate to ensure messages are signed by the AWS SNS SERVICE.
		//Since the allowed topicArn is part of the validation data, this ensures that your request originated from
		//the service, not somewhere else, and is from the topic you think it is, not something spoofed.
		$verifyCertificate = true;
		$signatureValid = false;
		$safeToProcess = true;

		if($logToFile){
			$log = new Log();
			$fileName = date("Ymdhis") . ".txt";
			$log->open($fileName);
		}

		if($verifyCertificate){
			//Build Up The String That Was Originally Encoded With The AWS Key So You Can Validate It Against Its Signature.
			if($json->Type == "SubscriptionConfirmation"){
				$validationString = "";
				$validationString .= "Message\n";
				$validationString .= $json->Message . "\n";
				$validationString .= "MessageId\n";
				$validationString .= $json->MessageId . "\n";
				$validationString .= "SubscribeURL\n";
				$validationString .= $json->SubscribeURL . "\n";
				$validationString .= "Timestamp\n";
				$validationString .= $json->Timestamp . "\n";
				$validationString .= "Token\n";
				$validationString .= $json->Token . "\n";
				$validationString .= "TopicArn\n";
				$validationString .= $json->TopicArn . "\n";
				$validationString .= "Type\n";
				$validationString .= $json->Type . "\n";
			}else{
				$validationString = "";
				$validationString .= "Message\n";
				$validationString .= $json->Message . "\n";
				$validationString .= "MessageId\n";
				$validationString .= $json->MessageId . "\n";
				if($json->Subject != ""){
					$validationString .= "Subject\n";
					$validationString .= $json->Subject . "\n";
				}
				$validationString .= "Timestamp\n";
				$validationString .= $json->Timestamp . "\n";
				$validationString .= "TopicArn\n";
				$validationString .= $json->TopicArn . "\n";
				$validationString .= "Type\n";
				$validationString .= $json->Type . "\n";
			}
			if($logToFile){
				$log->write("Data Validation String: \n ");
				$log->write($validationString);
			}
			
			$signatureValid = $this->validateCertificate($json->SigningCertURL, $json->Signature, $validationString);
			if(!$signatureValid){
				$safeToProcess = false;
				if($logToFile){
					$log->write("Data and Signature Do No Match Certificate or Certificate Error.\n");
				}
			}else{
				if($logToFile){
					$log->write("Data Validated Against Certificate.\n");
				}
			}
		}

		if($safeToProcess){
			//Handle A Subscription Request Programmatically
			if($json->Type == "SubscriptionConfirmation"){
				if($logToFile){
					$log->write($json->SubscribeURL);
				}
				
				$sns = $server->getSns();
				$sns->confirmSubscription(true, $json->Token, $json->TopicArn);
				//RESPOND TO SUBSCRIPTION NOTIFICATION BY CALLING THE URL
				// $ch = curl_init();
				// curl_setopt($ch, CURLOPT_URL, $json->SubscribeURL);
				// curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
				// curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
				// curl_setopt($ch, CURLOPT_HEADER, false);
				// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// $result = curl_exec($ch);
				// curl_close($ch);
			}
			
			
			//Handle a Notification Programmatically
			if($json->Type == "Notification"){
				if($logToFile){
					$log->write($json->Message);
				}

				// 
				$msg = json_decode($json->Message);
				if(isset($msg->mail)){
					$diagnosticCode = '';
					$action = '';
					$sendResultType = '';
					$emailAddress = 'test@intertechmedia.com';
					if(isset($msg->notificationType) && $msg->notificationType == 'Complaint'){
						$complaint = $msg->complaint;
						$sendResultType = 'complaint';
						if(count($complaint->complainedRecipients) > 0){
							$recipient = $complaint->complainedRecipients[0];
							$emailAddress = $recipient->emailAddress;
						}
					}else if(isset($msg->notificationType) && $msg->notificationType == 'Bounce'){
						$bounce = $msg->bounce;
						// hard bounce		
						if($bounce->bounceType == 'Permanent'){
							$sendResultType = 'hard bounce';
						}else{
							// transient
							$sendResultType = 'soft bounce';
						}
						if(count($bounce->bouncedRecipients) > 0){
							$recipient = $bounce->bouncedRecipients[0];
							$diagnosticCode = $recipient->diagnosticCode;
							$emailAddress = $recipient->emailAddress;
							$action = $recipient->action;
						}
					}
					$messageId = $msg->mail->messageId;

					// update EmailMessage DB
					if($sendResultType){
						$attributeUpdates = array(
							'sendResultType' => array(
								'Action' => AttributeAction::PUT,
								'Value' => array(
									Type::STRING => $sendResultType
								),
							),
						);
						if($diagnosticCode){
							$attributeUpdates['diagnosticCode'] = array(
								'Action' => AttributeAction::PUT,
								'Value' => array(
									Type::STRING => $diagnosticCode
								),
							);
						}
						$result = $db->updateItem(array(
							'TableName' => 'EmailMessage',
							'Key' => array(
								'messageId' => array( Type::STRING => $messageId),
							),
							'Expected' => array(
							    'emailAddress' => array( 'Value' => array(Type::STRING => $emailAddress ) )        
							),
							'AttributeUpdates' => $attributeUpdates,
							"ReturnValues" => ReturnValue::ALL_NEW
						));

						// update club stat table
						// automatically add 1
						$attributes = $result->getPath('Attributes');
						$clubStatId = $attributes['clubStatId'][Type::NUMBER];

						// update club stat table
						$attributeUpdates = array();
						if($sendResultType == 'soft bounce'){
							$attributeUpdates['softBounced'] = array(
								'Action' => AttributeAction::ADD,
								'Value' => array(
									Type::NUMBER => 1
								),
							);
						}else if($sendResultType == 'hard bounce'){
							$attributeUpdates['hardBounced'] = array(
								'Action' => AttributeAction::ADD,
								'Value' => array(
									Type::NUMBER => 1
								),
							);

							// Send request to update blaster status of current listener
							$url = self::LC_DOMAIN . '/lapp/bin/update_listener_blaster_status.php';
							$post = array(
								'clubStatId' => $clubStatId,
								'emailAddress' => $emailAddress,
							);
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url);
							curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
							curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
							curl_setopt($ch, CURLOPT_HEADER, false);
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							$result = curl_exec($ch);
							curl_close($ch);
						}else if($sendResultType == 'complaint'){
							$attributeUpdates['complaint'] = array(
								'Action' => AttributeAction::ADD,
								'Value' => array(
									Type::NUMBER => 1
								),
							);
						}
						$result = $db->updateItem(array(
							'TableName' => 'ClubStat',
							'Key' => array(
								'clubStatId' => array( Type::NUMBER => $clubStatId),
							),
							'AttributeUpdates' => $attributeUpdates,
							"ReturnValues" => ReturnValue::ALL_NEW
						));
					}
				}
			}
		}
		if($logToFile){
			$log->close();
		}
		return new Response();
	}

	// Get hardBounced/softBounced/complaint number of corresponding email statistic
	public function getStat(Request $request){
		if($res = $this->checkParameters($request, array('clubStatId'))){
			$response = new Response();
			$response->setContent(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			
			return $response;
		}
		$server = Server::getInstance();
		$db = $server->getDb();
		$clubStatId = $request->request->get('clubStatId');
		$data = array();
		$data['hardBounced'] = 0;
		$data['softBounced'] = 0;
		$data['complaint'] = 0;
		// get hard bounce
		$result = $db->getItem(
			array(
				"TableName" => "ClubStat",
				"Key" => array(
					"clubStatId" => array(Type::NUMBER => $clubStatId)
				)
			)
		);
		$item = $result->getPath('Item');
		if(isset($item['complaint'])){
			$data['complaint'] = $item['complaint'][Type::NUMBER];
		}
		if(isset($item['hardBounced'])){
			$data['hardBounced'] = $item['hardBounced'][Type::NUMBER];
		}
		if(isset($item['softBounced'])){
			$data['softBounced'] = $item['softBounced'][Type::NUMBER];
		}
		$response = new Response();
		$response->setContent(json_encode($data));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	//A Function that takes the key file, signature, and signed data and tells us if it all matches.
	public function validateCertificate($keyFileURL, $signatureString, $data){
		$signature = base64_decode($signatureString);
		// fetch certificate from file and ready it
		$fp = fopen($keyFileURL, "r");
		$cert = fread($fp, 8192);
		fclose($fp);
		$pubkeyid = openssl_get_publickey($cert);
		$ok = openssl_verify($data, $signature, $pubkeyid, OPENSSL_ALGO_SHA1);
		if ($ok == 1) {
		    return true;
		} elseif ($ok == 0) {
		    return false;
		} else {
		    return false;
		}	
	}

}