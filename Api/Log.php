<?php
/**
 * Name:
 *	Log.php
 *
 * Description:
 *	Some actions for log
 *
 * Log:
 *  June Peng       01/10/2014
 *   - 
 */
namespace Api;

class Log{

	protected $name;
	protected $fileHandle;

	public function open($name = ''){
		$this->name = $name;
		$this->fileHandle = fopen(ROOT_DIRECTORY . 'Log/' . $this->name, 'w') or die("Log File Cannot Be Opened.");
	}

	public function write($content = ''){
		fwrite($this->fileHandle, $content);
	}

	public function close(){
		fclose($this->fileHandle);
	}

}