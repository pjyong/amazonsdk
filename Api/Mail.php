<?php

namespace Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Api\Server;

class Mail{

	public function sendMail(Request $request){

		// necessary parameters
		// fromEmail,to,subject,body
		$fromEmail = $request->request->get('fromEmail');
		$to = (array)$request->request->get('to');
		$subject = $request->request->get('subject');
		$body = $request->request->get('body');

		$server = Server::getInstance();
		$ses = $server->getSes();
		$sns = $server->getSns();
		$sqs = $server->getSqs();

		$topicName = 'lc_email_topic';
		$topicArn = $sns->createTopic(array('Name' => $topicName))->getAll()['TopicArn'];

		$queueName = 'lc_email_queue';
		$queueUrl = $sqs->createQueue(array('QueueName' => $queueName))->getAll()['QueueUrl'];
		$queueArn = $sqs->getQueueArn($queueUrl);
		// add policy to the queue
		$sqs->setQueueAttributes(array(
			'QueueUrl' => $queueUrl,
			'Attributes' => array(
				'Policy' => '{"Version":"2008-10-17","Id":"' . $queueArn . '/SQSDefaultPolicy","Statement":[{"Sid":"' . $queueName . '_Policy","Effect":"Allow","Principal":{"AWS":"*"},"Action":"SQS:SendMessage","Resource":"' . $queueArn . '","Condition":{"ArnEquals":{"aws:SourceArn":"' . $topicArn . '"}}}]}'
			)
		));

		// bind SQS to the topic
		$sns->subscribe(array(
			'TopicArn' => $topicArn,
			'Protocol' => 'sqs',
			'Endpoint' => $queueArn
		));

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
				'ToAddresses' => $to,
			),
			'Message' => array(
				'Subject' => array(
					'Data' => stripslashes($subject)
				),
				'Body' => array(
					'Text' => array(
						'Data' => stripslashes($body)
					)
				)
			)
		);

		$messageId = $ses->sendEmail($args)->getAll()['MessageId'];

		$response = new Response();
		$response->setContent(json_encode(array('messageId' => $messageId, 'error' => 0)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function updateStat(){
		
		$server = Server::getInstance();
		$sqs = $server->getSqs();

		$queueName = 'lc_email_queue';
		$queueUrl = $sqs->createQueue(array('QueueName' => $queueName))->getAll()['QueueUrl'];

		$messages = $sqs->receiveMessage(array(
			'QueueUrl' => $queueUrl,
			'MaxNumberOfMessages' => 10,
			'VisibilityTimeout' => 300,
			'WaitTimeSeconds' => 10
		))->getAll()['Messages'];

		// save the messages into database
		foreach($messages as $message){
			$notification = json_decode($message['Body']);
			$msg = json_decode($notification->Message);
			if(isset($msg->mail)){
				$numberOfComplaints = 0;
				$numberOfBounce = 0;
				if(isset($msg->notificationType) && $msg->notificationType == 'Complaint'){
					$complaint = $msg->complaint;
					// get the number of complaint emails
					$numberOfComplaints = count($complaint->complainedRecipients);
				}
				if(isset($msg->notificationType) && $msg->notificationType == 'Bounce'){
					$bounce = $msg->bounce;
					// get the number of bounce emails
					$numberOfBounce = count($complaint->bouncedRecipients);
				}
				// save the number into database

				$messageId = $msg->mail->messageId;
				// update the ClubEmailStat where messageId = 

				print_r($msg);
			}

			
		}
		// $res = json_encode($messages);
		// $response = new Response();
		// $response->setContent($res);
		// $response->headers->set('Content-Type', 'application/json');
		// return $response;
	}

}