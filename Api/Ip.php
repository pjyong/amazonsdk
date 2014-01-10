<?php
/**
 * Name:
 *	Ip.php
 *
 * Description:
 *	Add a public IP to security group of Amazon server
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Api\Server;

class Ip{

	public function allowPublicIp(Request $request){

		$ip = $request->query->get('ip');

		$server = Server::getInstance();
		// allow 'describeSecurityGroups' action for current server
		// we need use passRole in order to use the following valid actions of ec2
		// but our server doesn't have right Role
		$iam = $server->getIam();


		// list security group of instance
		$ec2 = $server->getEc2();



		// get default security group
		$securityGroups = $ec2->describeSecurityGroups(array('GroupName' => 'default'));
		$temp = $securityGroups->getAll();
		$securityGroups = $temp['SecurityGroups'];
		$securityGroup = $securityGroups[0];

		$result = $ec2->AuthorizeSecurityGroupIngress(
			array(
				'GroupId' => $securityGroup['GroupId'],
				'IpPermissions' => array(
					array(
						'IpProtocol' => 'tcp',
			            'FromPort' => '80',
			            'ToPort' => '80',
			            'IpRanges' => array(
			            	array('CidrIp' => $ip . '/32'),
			        	)
			    	)
				) 
		));
		var_dump($result->getAll());
	}

}