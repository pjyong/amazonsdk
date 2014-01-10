<?
/**
 * Name:
 *	insertEmailMessage.php
 *
 * Description:
 *	this script is used to respond to the Amazon SNS
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
define('ROOT_DIRECTORY', dirname(__FILE__) . '/');
require_once ROOT_DIRECTORY . 'config.php';

// load our custom commands
use Api\Command;
$response = Command::excute('Stat/updateStat');
$response->send();
