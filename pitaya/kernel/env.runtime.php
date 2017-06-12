<?php
	define( 'DEBUG_BACKTRACE_ENABLED', function_exists( "debug_backtrace" ) );
	
	if ( IS_CLI_ENV ) {
		define( 'REQUESTING_METHOD', '' );
	}
	else {
		define( 'REQUESTING_METHOD', strtoupper($_SERVER['REQUEST_METHOD']) );
		$_SERVER['argv'] = []; $_SERVER['argc'] = 0;
	}
	


	final class DEBUG {
		public static function VarDump(...$args) {
			if ( !DEBUG_MODE ) return;
		
		
		
			$width = intval(DEBUG_VIEWPORT_WIDTH);

			ob_start();
			if (IS_HTTP_ENV) {
				echo "<div class='debugOpt' style='background-color:#fefe00; z-index:9999; border:solid red; margin-bottom:10px; padding:5px; word-break:break-all; width:{$width}px; color:#000; position:relative;'>";
			}
			
			if (IS_CLI_ENV) {
				$indentSpace = "\t";
				$newLine = LF;
			}
			else {
				$indentSpace = "&nbsp;&nbsp;&nbsp;&nbsp;";
				$newLine = BR;
			}



			if ( DEBUG_BACKTRACE_ENABLED ) {
				$info = self::BackTrace();
				if ( (array_key_exists('class', $info[1]) && $info[1]['class'] == __CLASS__) && (preg_match('/^VarDump.*/', $info[1]['function']) > 0) ) {
					$locator = 2;
				}
				else {
					$locator = 1;
				}
				
				$info = @$info[$locator];
				if ( $locator >= count($info) ) {
					$info['file'] = 'PHP System Call';
					$info['line'] = 'Unavailable';
				}
	
	
	
				if ( IS_HTTP_ENV ) echo '<div>';
				echo "{$info['file']}:{$info['line']}";
				if ( IS_HTTP_ENV ) echo '</div>';
				echo $newLine;
			}
			


			$indent = -1;
			foreach ( $args as $arg ) {
				if ( $indent >= 0 ) {
					echo $newLine;
				}

				$indent = 0;
				foreach(explode("\n", var_export($arg, TRUE)) as $chunk) {
					$chunk = trim($chunk);

					if ( preg_match('/.*\($/', $chunk) ) {
						$tmp = explode(' ', $chunk);

						foreach($tmp as $tmpItem) {
							for($i=0; $i<$indent; $i++) echo $indentSpace;

							echo $tmpItem.$newLine;
						}
						$indent++;
					}
					else {
						if(preg_match('/^\).*/', $chunk)) {
							$indent--;
						}

						for($i=0; $i<$indent; $i++) echo $indentSpace;
						echo $chunk.$newLine;
					}
				}
			}

			if (IS_HTTP_ENV) echo '</div>';
			$content = ob_get_clean();
			echo $content;
		}
		
		private static function BackTrace($args = 0) {
			$info = debug_backtrace($args);
			$depth = count($info);

			$adjusted = array();
			for( $i=1; $i<$depth; $i++)
			{
				$adjusted[$i-1] = array();

				$tmp = $info[$i];

				@$adjusted[$i-1]['file'] = @$info[$i-1]['file'];
				@$adjusted[$i-1]['line'] = @$info[$i-1]['line'];

				@$adjusted[$i-1]['function'] = @$tmp['function'];

				if(array_key_exists('class',  $tmp)) $adjusted[$i-1]['class']  = $tmp['class'];
				if(array_key_exists('object', $tmp)) $adjusted[$i-1]['object'] = $tmp['object'];
				if(array_key_exists('type',	  $tmp)) $adjusted[$i-1]['type']   = $tmp['type'];
				if(array_key_exists('args',	  $tmp)) $adjusted[$i-1]['args']   = $tmp['args'];
			}

			$item = array_pop($info);
			unset($item['class']);
			unset($item['object']);
			unset($item['type']);
			unset($item['args']);
			array_push($adjusted,$item);

			return $adjusted;
		}
	}
	
	
	
	function pb_metric(){
		static $_prevTime	= 0;
		static $_prevMemory = 0;
		
		$now = microtime(TRUE);
		$memoryUsage = memory_get_usage();
		$result = (object)[
			'memory' => (object)[
				'current' => $memoryUsage,
				'peak'	  => memory_get_peak_usage(),
				'diff'	  => $memoryUsage - $_prevMemory
			],
			'time' => (object)[
				'now' => $now,
				'dur' => $now - PITAYA_METRIC_BOOT_TIME
			],
			'diff' => $now - $_prevTime
		];
		
		$_prevTime = $now;
		$_prevMemory = $memoryUsage;
		return $result;
	}