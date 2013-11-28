<?php
require_once 'init.php';
// load our custom commands
use Api\Command;
$response = Command::excute();
$response->send();

// now I'm using a command via this parameter. command=Mail/sendMail
// everybody needs to follow the rule, 1, create one class named "Mail" under "Api" directory; 2, create one method named "sendMail"




