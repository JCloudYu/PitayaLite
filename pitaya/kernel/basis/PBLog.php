<?php
	final class PBLog {
		const LOG_INFO_TIME		= 1;
		const LOG_INFO_CATE		= 2;
		const LOG_INFO_BASIS	= 4;
		const LOG_INFO_ROUTE	= 8;
		const LOG_INFO_ALL		= 0xFFFFFFFF;
	
		private $_logStream = NULL;
		public function __construct($logPath) {
			$this->_logStream = self::ObtainStream($logPath);
		}
		public function genLogMsg( $message, $logCate = '', $options = [] ) {
			if ( !is_array($options) ) $options = [];
			
			$LOG_INFO = array_key_exists( 'info-level', $options ) ? $options['info-level'] | 0 : self::LOG_INFO_ALL;
			if ( !is_string($message) ) $message = print_r( $message, TRUE );
			if ( !is_array(@$options['tags']) ) $options['tags'] = array();
			$info = self::PrepLogInfo( $logCate );



			// INFO: Process other tags
			$tags = implode('', array_map(function($item) {
				return "[{$item}]";
			}, array_unique($options['tags'])));



			// INFO: Write file stream
			$timeInfo = '';
			if ( $LOG_INFO & self::LOG_INFO_TIME )
			{
				$timeInfo = in_array( 'UNIX_TIMESTAMP', $options ) ? $info['time'] : $info['timestamp'];
				$timeInfo = "[{$timeInfo}]";
			}
			
			$cateInfo	= ( $LOG_INFO & self::LOG_INFO_CATE ) ? "[{$info['cate']}]" : '';
			$basisInfo	= ( $LOG_INFO & self::LOG_INFO_BASIS ) ? "[{$info['service']}]" : '';
			$routeInfo	= ( $LOG_INFO & self::LOG_INFO_ROUTE) ? "[{$info['route']}]" : '';
			$posInfo	= " ";
			if ( DEBUG_BACKTRACE_ENABLED ) {
				$backtrace = debug_backtrace();
				$posInfo = " {$backtrace[2]['file']}:{$backtrace[2]['line']}\n";
			}
			
			
			
			$msg = "{$timeInfo}{$cateInfo}{$basisInfo}{$routeInfo}{$tags}{$posInfo}{$message}";
			return $msg;
		}
		public function logMsg( $message, $logCate = '', $options = [] ) {
			if ( empty($this->_logStream) ) return FALSE;


			$newline = array_key_exists("newline", $options) ? !!$options[ 'newline' ] : TRUE;
			$msg = ( !!@$options[ 'nowrap' ] ) ? $message : $this->genLogMsg( $message, $logCate, $options );
			
			fwrite( $this->_logStream, $msg . (!empty($newline) ? "\n" : "") );
			fflush( $this->_logStream );
			return $msg;
		}

		public static function Log($message, $logFileName = '', $options = array())
		{
			$logPath = SYS_LOG_DIR . "/" . (empty($logFileName) ? "service.pblog" : $logFileName);
			$log	 = self::ObtainLog($logPath);

			return $log->logMsg($message, '', $options);
		}
		public static function ERRLog($message, $logFileName = '', $options = array())
		{
			$logPath = SYS_LOG_DIR . "/" . (empty($logFileName) ? "error.pblog" : $logFileName);
			$log	 = self::ObtainLog($logPath);

			error_log( $msg = $log->genLogMsg( $message, 'ERROR', array_merge($options, [ 'info-level' => self::LOG_INFO_ALL & ~self::LOG_INFO_TIME ]) ) );
			return $log->logMsg( $message, 'ERROR', $options );
		}
		public static function SYSLog($message, $logFileName = '', $options = array())
		{
			$logPath = SYS_LOG_DIR . "/" . (empty($logFileName) ? "system.pblog" : $logFileName);
			$log	 = self::ObtainLog($logPath);

			return $log->logMsg($message, 'SYS', $options);
		}
		public static function ShareLog($message, $logFileName = '', $options = array())
		{
			$logPath = SYS_LOG_DIR . "/" . (empty($logFileName) ? "share.pblog" : $logFileName);
			$log	 = self::ObtainLog($logPath);

			return $log->logMsg($message, 'SHARE', $options);
		}
		public static function CustomLog($message, $cate = 'CUSTOM', $logFileName = '', $options = array())
		{
			$logPath = SYS_LOG_DIR . "/" . (empty($logFileName) ? "custom.pblog" : $logFileName);
			$log	 = self::ObtainLog($logPath);

			return $log->logMsg($message, empty($cate) ? 'CUSTOM' : "{$cate}", $options);
		}



		public static function ObtainLog($logFilePath)
		{
			static $_cachedLog = array();

			$pathKey = md5($logFilePath);
			if (empty($_cachedLog[$pathKey]))
				$_cachedLog[$pathKey] = new PBLog($logFilePath);

			return $_cachedLog[$pathKey];
		}
		private static function PrepLogInfo( $logCate = '' ) {
			$curTime = time();
			return [
				'cate'		=> (empty($logCate) || !is_string($logCate)) ? 'INFO' : "{$logCate}",
				'time'		=> $curTime,
				'timestamp' => date("Y-m-d G:i:s", $curTime),
				'service'	=> (!defined('SESSION_BASIS') ? 'Pitaya' : SESSION_BASIS),
				'route'		=> IS_CLI_ENV ? 'CLI' : 'NET'
			];
		}
		private static function ObtainStream($logFilePath)
		{
			static $_fileStream = array();

			$pathKey = md5($logFilePath);

			if (empty($_fileStream[$pathKey]))
			{
				if (is_dir($logFilePath))
					return NULL;

				$logPath = dirname($logFilePath);
				if (!is_dir($logPath)) @mkdir($logPath);



				if (is_file($logFilePath))
				{
					$today = strtotime(date('Y-m-d'));
					$fileTime = filemtime($logFilePath);

					if ($fileTime <= $today)
					{
						$fileTime = date('Ymd', filemtime($logFilePath));
						@rename( $logFilePath, "{$logFilePath}-{$fileTime}" );
					}
				}


				touch( $logFilePath ); 
				chmod( $logFilePath, 0644 );
				$hLog = @fopen($logFilePath, 'a+b');
				if ( empty( $hLog ) ) return NULL;



				$_fileStream[$pathKey] = $hLog;
			}

			return $_fileStream[$pathKey];
		}
	}
