<?php

class WPL_Logger{

	var $file;
	var $strdate;
	var $level = array('debug'=>7,'info'=>6,'notice'=>5,'warn'=>4,'critical'=>3,'error'=>2);

	function __construct($file = false){
		if($file){
			$this->file = $file;
		} else {
			// get WP uploads folder
			$uploads = wp_upload_dir();
			$uploaddir = $uploads['basedir'];
			$logdir = $uploaddir . '/wp-lister';
			if ( !is_dir($logdir) ) mkdir($logdir);
			$logfile = $logdir.'/wplister.log';
			if ( !file_exists($logfile) ) touch($logfile);
			$this->file = $logfile;
		}
		$this->strdate = 'Y/m/d H:i:s';
		$this->debug('Start Session:'.print_r($_SERVER,1));
		$this->info( $_SERVER['REQUEST_METHOD'].': '.$_SERVER['QUERY_STRING'] . @$_POST['action'] .' - '. @$_POST['do'] );
	}

	function log($level=debug,$msg=false){
		//If debug is not on, then don't log
		if(defined('WPLISTER_DEBUG')){
			if(WPLISTER_DEBUG >= $this->level[$level]){
				return error_log('['.date($this->strdate).'] '.strtoupper($level).' '.$msg."\n",3,$this->file);
			}
		}
	}

	function debug($msg=false){
		return $this->log('debug',$msg);
	}

	function info($msg=false){
		return $this->log('info',$msg);
	}

	function notice($msg=false){
		return $this->log('notice',$msg);
	}

	function warn($msg=false){
		return $this->log('warn',$msg);
	}

	function critical($msg=false){
		return $this->log('critical',$msg);
	}

	function error($msg=false){
		return $this->log('error',$msg);
	}

	// custom call stack trace
	// usage: $this->logger->callStack( debug_backtrace() );
    function callStack($stacktrace) {
        $this->info( str_repeat("=", 50) );
        $i = 1;
        foreach($stacktrace as $node) {
            $this->info( "$i. ".basename($node['file']) .":" .$node['function'] ."(" .$node['line'].")" );
            $i++;
        }
        $this->info( str_repeat("=", 50) );
    } 

}


