<?php

namespace Api;

use Aws\Ses\SesClient;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Aws\Iam\IamClient;

class Server{
	const AMAZON_ACCESS_KEY = 'your_own_key';
	const AMAZON_ACCESS_SECRET = 'your_own_secret';
	const AMAZON_ACCESS_REGION = 'your_own_region';

	private static $_instance;

	private $iam = null;
	private $ses = null;
	private $sns = null;
	private $sqs = null;

	public static function getInstance(){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function getIam(){
		if(is_null($this->iam)){
			print_r(IamClient);
			$this->iam = IamClient::factory(array(
				'key' => self::AMAZON_ACCESS_KEY,
				'secret' => self::AMAZON_ACCESS_SECRET,
				'region' => self::AMAZON_ACCESS_REGION
			));
		}

		return $this->iam;
	}

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

}