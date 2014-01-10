<?php
/**
 * Name:
 *	Mail.php
 *
 * Description:
 *	Some actions about email
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
use Aws\DynamoDb\Enum\ComparisonOperator;

use Api\Server;

class Mail extends Base{

	const AMAZON_API_SERVER_DOMAIN = 'http://ec2-54-226-201-161.compute-1.amazonaws.com';

	// 
	public function sendMail(Request $request){
		if($res = $this->checkParameters($request, array('fromEmail', 'to', 'subject', 'body'))){
			$response = new Response();
			$response->setContent(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			
			return $response;
		}

		$fromEmail = $request->request->get('fromEmail');
		$to = (array)json_decode($request->request->get('to'), true);
		$subject = $request->request->get('subject');
		$body = $request->request->get('body');
		$emailStatId = $request->request->get('emailStatId');

		$server = Server::getInstance();
		$ses = $server->getSes();
		$sns = $server->getSns();
		// create one SNS to receive bounce and complaint email response
		$topicName = 'lc_email_topic';
		$topicArn = $sns->createTopic(array('Name' => $topicName))->getPath('TopicArn');
		// Once there is bounce or complaint response, the corresponding data will be sent to the following script.
		$sns->subscribe(
			array(
				'TopicArn' => $topicArn,
				'Protocol' => 'http',
				'Endpoint' => self::AMAZON_API_SERVER_DOMAIN . '/insertEmailMessage.php'
			)
		);
		// bind topics to the identity of email
		$ses->setIdentityNotificationTopic(array(
			'Identity' => $fromEmail,
			'NotificationType' => 'Complaint',
			'SnsTopic' => $topicArn
		));
		$ses->setIdentityNotificationTopic(array(
			'Identity' => $fromEmail,
			'NotificationType' => 'Bounce',
			'SnsTopic' => $topicArn
		));

		// send email
		$args = array(
			'Source' => $fromEmail,
			'Destination' => array(
				'ToAddresses' => '',
			),
			'Message' => array(
				'Subject' => array(
					'Data' => stripslashes($subject)
				),
				'Body' => array(
					'Html' => array(
						'Data' => stripslashes($body)
					)
				)
			)
		);
		// I break one send request to many request, because I don't want they affect each other.
		foreach($to as $emailAddress){
			$args['Destination']['ToAddresses'] = (array)$emailAddress;
			$messageId = $ses->sendEmail($args)->getPath('MessageId');
			if($messageId){
				// If current send request needs to be inspected, so I save the corresponding data to Dynamo db.
				if($emailStatId){
					$db = $server->getDb();
					$result = $db->putItem(array(
						'TableName' => 'EmailMessage',
						'Item' => array(
							'messageId' => array( Type::STRING => $messageId),
							'clubStatId' => array( Type::NUMBER => $emailStatId),
							'emailAddress' => array( Type::STRING => $emailAddress),
						),
					));
				}
			}
		}
		$response = new Response();
		$response->setContent(json_encode(array('errorCode' => 0, 'errorMsg' => NULL)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	// Get soft bounce, hard bounce or complaint emails list for corresponding email statistic
	public function getBounceEmails(Request $request){
		// check necessary parameters
		if($res = $this->checkParameters($request, array('clubStatId', 'type'))){
			$response = new Response();
			$response->setContent(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			
			return $response;
		}
		$server = Server::getInstance();
		$db = $server->getDb();

		$clubStatId = (int)$request->request->get('clubStatId');
		$type = $request->request->get('type');

		if($type == 'hardBounced'){
			$type = 'hard bounce';
		}else if($type == 'softBounced'){
			$type = 'soft bounce';
		}
		// scan the whole table
		// here I want to create one global secondary index to avoid scanning the whole table
		// but the createTable doesn't work for global secondary index
		// maybe I need to wait some days more, because Amazon released this functionality not long time ago.
		// Sure, I can create it in Amazon Console, but I don't have access permission to Dynamo db.
		$result = $db->scan(array(
			"TableName" => "EmailMessage",
			"ScanFilter" => array(
				"clubStatId" => array(            
					"ComparisonOperator" => ComparisonOperator::EQ,
					"AttributeValueList" => array(
						array(Type::NUMBER => $clubStatId)
					)
				),
				"sendResultType" => array(            
					"ComparisonOperator" => ComparisonOperator::EQ,
					"AttributeValueList" => array(
						array(Type::STRING => $type)
					)
				),
			),
		));
		$emails = $result->getPath('Items');
		
		$data = array();
		foreach($emails as $email){
			$temp = array();
			$temp['diagnosticCode'] = '';
			if(isset($email['diagnosticCode'][Type::STRING])){
				$temp['diagnosticCode'] = $email['diagnosticCode'][Type::STRING];
			}
			$temp['emailAddress'] = $email['emailAddress'][Type::STRING];

			$data[] = $temp;
		}
		$response = new Response();
		$response->setContent(json_encode($data));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	// create EmailMessage table with API
	// visit the following link
	// AMAZON_API_SERVER_DOMAIN . '/command.php?command=Mail/createTableEmailMessage'
	function createTableEmailMessage(){
		$server = Server::getInstance();
		$db = $server->getDb();
		// $db->deleteTable(array(    "TableName" =>  'EmailMessage'));
		// $db->waitUntilTableNotExists(array("TableName" => 'EmailMessage'));
		$data = array(
			'TableName' => 'EmailMessage',
			'AttributeDefinitions' => array(
				array(
					'AttributeName' => 'messageId',
					'AttributeType' => Type::STRING
				)
			),
			'KeySchema' => array(
				
				array(
					'AttributeName' => 'messageId',
					'KeyType' => KeyType::HASH
				),
				
			),
			'ProvisionedThroughput' => array(
				"ReadCapacityUnits" => 4000,
        		"WriteCapacityUnits" => 4000
			)
		);
		$r = $db->createTable($data);
		print_r($r->getPath('TableDescription'));

		return new Response('');
	}
	
	// create ClubStat table
	// visit the following link
	// AMAZON_API_SERVER_DOMAIN . '/command.php?command=Mail/createTableClubStat'
	function createTableClubStat(){
		$server = Server::getInstance();
		$db = $server->getDb();
		$data = array(
			'TableName' => 'ClubStat',
			'AttributeDefinitions' => array(
				array(
					'AttributeName' => 'clubStatId',
					'AttributeType' => Type::NUMBER
				)
			),
			'KeySchema' => array(
				
				array(
					'AttributeName' => 'clubStatId',
					'KeyType' => KeyType::HASH
				),
				
			),
			'ProvisionedThroughput' => array(
				"ReadCapacityUnits" => 4000,
        		"WriteCapacityUnits" => 4000
			)
		);
		$r = $db->createTable($data);
		print_r($r->getPath('TableDescription'));

		return new Response('');
	}

}