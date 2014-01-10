<?php
/**
 * Name:
 *	config.php
 *
 * Description:
 *	
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
// load AWS SDK and define our custom namespace
$loader = require_once  ROOT_DIRECTORY . 'AWSSDK/aws-autoloader.php';
$loader->registerNamespaces(array(
	'Api'	=> __DIR__
));
$loader->register();