<?php
/**
 * Name:
 *	Server.php
 *
 * Description:
 *	Any Amazon service instance from here.
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

use Aws\Ses\SesClient;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Aws\Iam\IamClient;
use Aws\Ec2\Ec2Client;
use Aws\DynamoDb\DynamoDbClient;

class Server{

	const AMAZON_ACCESS_KEY = '';
	const AMAZON_ACCESS_SECRET = '';
	const AMAZON_ACCESS_REGION = '';

	private static $_instance;

	private $iam = null;
	private $ses = null;
	private $sns = null;
	private $sqs = null;
	private $ec2 = null;
	private $db = null;

	public static function getInstance(){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	// get IAM instance
	public function getIam(){
		if(is_null($this->iam)){
			$this->iam = IamClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->iam;
	}

	// get SES instance
	public function getSes(){
		if(is_null($this->ses)){
			$this->ses = SesClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->ses;
	}

	// get SNS instance
	public function getSns(){
		if(is_null($this->sns)){
			$this->sns = SnsClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->sns;
	}

	// get SQS instance
	public function getSqs(){
		if(is_null($this->sqs)){
			$this->sqs = SqsClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->sqs;
	}

	// get EC2 instance
	public function getEc2(){
		if(is_null($this->ec2)){
			$this->ec2 = Ec2Client::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->ec2;
	}

	// get DynamoDB instance
	public function getDb(){
		if(is_null($this->db)){
			$this->db = DynamoDbClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->db;
	}

}