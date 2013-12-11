<?php

if ( ! class_exists('WPL_Logger') ) :

class WPL_Logger{

	var $file;
	var $strdate;
	var $level = array('debug'=>7,'info'=>6,'notice'=>5,'warn'=>4,'critical'=>3,'error'=>2);

	function __construct($file = false){

		if ( ! defined('WPLISTER_DEBUG') ) return;

		// build logfile path
		$uploads = wp_upload_dir();
		$uploaddir = $uploads['basedir'];
		$logdir = $uploaddir . '/wp-lister';
		$logfile = $logdir.'/wplister.log';

		if ( WPLISTER_DEBUG == '' ) {
			// remove logfile when logging is disabled
			if ( file_exists($logfile) ) unlink( $logfile );
		} else {

			if ( $file ) {
				$this->file = $file;
			} else {
				// make sure logfile exists
				if ( !is_dir($logdir) ) mkdir($logdir);
				if ( !file_exists($logfile) ) touch($logfile);
				$this->file = $logfile;
			}
			$this->strdate = 'Y/m/d H:i:s';
			
			$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
			if ( $action == 'heartbeat' ) return;
			if ( $action == 'wplister_tail_log' ) return;

			// $this->debug('Start Session:'.print_r($_SERVER,1));
			$this->info( 
				$_SERVER['REQUEST_METHOD'] . ': ' . 
				$_SERVER['QUERY_STRING'] . ' - ' . 
				( isset( $_POST['action'] ) ? $_POST['action'] : '' ) .' - '. 
				( isset( $_POST['do'] ) ? $_POST['do'] : '' )  
			);

		}

	} // __contruct()

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

endif;
