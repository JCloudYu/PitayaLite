<?php
	final class PBLog {
		private $_logStream = NULL;
		private $_tagName	= NULL;
		public function __construct($logPath, $tagName=NULL) {
			$this->_logStream = @fopen( $logPath, 'a+b' );
		}
		public function genMsg($message) {
			$attr = [
				date( "Y/m/d H:i:s" ),
				IS_CLI_ENV ? 'CLI' : 'HTTP'
			];
			if ( $this->_tagName ) {
				$attr[] = $this->_tagName;
			}
			$execInfo = implode( '][', $attr );
			
			
			$posInfo = "";
			if ( DEBUG_BACKTRACE_ENABLED ) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
				$posInfo = "{$backtrace[1]['file']}:{$backtrace[1]['line']}\n";
			}
			
			
			
			$msg = "[{$execInfo}] {$posInfo}{$message}";
			return $msg;
		}
		public function log($message) {
			if ( empty($this->_logStream) ) return FALSE;
			
			fwrite( $this->_logStream, $this->genMsg($message) . LF );
			fflush( $this->_logStream );
			return TRUE;
		}
		
		
		
		
		
		public static function NullLog() {
			static $_singleton = NULL;
			if ( $_singleton === NULL ) {
				$_singleton = new PBLog(FALSE);
			}
			return $_singleton;
		}
		public static function ERRLog($message) {
			$log = PBLog( 'error' );
			error_log($log->genMsg($message));
			return $log->log($message);
		}
		public static function SYSLog($message) {
			return PBLog( 'sys' )->log($message);
		}
		public static function ShareLog($message) {
			return PBLog( 'info' )->log($message);
		}
	}
	
	function PBLog($id, $logFileName=NULL, $tagName=NULL) {
		static $_cachedLog = [];
		static $_g_conf = NULL;
		
		if ($_g_conf === NULL) {
			$_g_conf = PBStaticConf( 'pitaya-env' );
		}
		
		
		
		if (func_num_args() == 0) {
			return PBLog::NullLog();
		}
		else {
			$log = @$_cachedLog[$id];
			if (func_num_args() == 1) {
				return $log ?: PBLog::NullLog();
			}
		}
		
		
		
		if ( $log !== NULL ) {
			return $log;
		}
		
		if ( empty($_g_conf['log-dir']) ) {
			$log = PBLog::NullLog();
		}
		else {
			$logFilePath = "{$_g_conf[ 'log-dir' ]}/{$logFileName}";
			$log = new PBLog($logFilePath, $tagName);
		}

		return ($_cachedLog[$id] = $log);
	}
		
	PBLog( 'info',		'info.pblog',		'INF' );
	PBLog( 'sys',		'system.pblog',		'SYS' );
	PBLog( 'error',		'error.pblog',		'ERR' );
	PBLog( 'exception',	'exception.pblog',	'ERR' );