<?php
/**
 * Name:
 *	command.php
 *
 * Description:
 *	command entrance file
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
define('ROOT_DIRECTORY', dirname(__FILE__) . '/');
require_once ROOT_DIRECTORY . 'config.php';
// load our custom commands
use Api\Command;
$response = Command::excute();
// for this server, I extra loaded Request&Response symfony components.
// so every request must return Response object
$response->send();




