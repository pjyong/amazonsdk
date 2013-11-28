<?php
// load AWS SDK and define our custom namespace
$loader = require_once './AWSSDK/aws-autoloader.php';
$loader->registerNamespaces(array(
	'Api'	=> __DIR__
));
$loader->register();

// for this server, I extra loaded Request&Response symfony components.