<?php


class Logger(){

	private $logfolder = "logs/"

	$module = null;
	$logfile = null;

	function __construct($module_param){

		//Each module needs its own logfile, so the parameter passed in decides
		//which file the Logger object can write to.
		switch ($module_param) {
		    case 'installer':
		    	$this->module = $module_param; 
		        $this->logfile = $log_folder . "installer_log.txt";
		        break;
		}


	}

	function logger($message) {
	    if (WP_DEBUG === true) {
	        if (is_array($message) || is_object($message)) {
	            error_log(print_r($message, true));
	        } else {
	    		file_put_contents($logfile, $message."\n\n" ,FILE_APPEND);
	        }
	    }
	}


}



?>