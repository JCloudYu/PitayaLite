<?php
	final class PBStdIO {
		public static function READ($msg = "", $isPassword = FALSE, $stars = FALSE) {
		
			if ( !empty($msg) ) fwrite(STDOUT, "{$msg}");

			if ( !$isPassword ) return fgets(STDIN);

			// Get current style
			$oldStyle = shell_exec('stty -g');

			if ($stars === FALSE)
			{
				shell_exec('stty -echo');
				$password = fgets(STDIN);
				fwrite(STDOUT, "\n");
			}
			else
			{
				shell_exec('stty -icanon -echo min 1 time 0');

				$password = '';
				while( TRUE )
				{
					$char = fgetc(STDIN);

					if ( $char === "\n")
					{
						fwrite(STDOUT, "\n");
						break;
					}
					else
					if ( ord($char) === 127 )
					{
						if (strlen($password) > 0)
						{
							fwrite(STDOUT, "\x08 \x08");
							$password = substr($password, 0, -1);
						}
					}
					else
					if ( ord($char) === 8 )
					{
						if (strlen($password) > 0)
						{
							fwrite(STDOUT, "\x08 \x08");
							$password = substr($password, 0, -1);
						}
					}
					else
					{
						fwrite(STDOUT, "*");
						$password .= $char;
					}
				}
			}

			// Reset old style
			shell_exec('stty ' . $oldStyle);

			// Return the password
			return rtrim($password, "\n");
		}
		public static function STDERR($msg = "", $newLine = TRUE) {
		
			static $stream = NULL;
			if ( !$stream ) $stream = PBStream::STDERR();

			self::WriteMsg($stream, $msg, $newLine);
		}
		public static function STDOUT($msg = "", $newLine = TRUE) {
		
			static $stream = NULL;
			if ( !$stream ) $stream = PBStream::STDOUT();

			self::WriteMsg($stream, $msg, $newLine);
		}
		private static function WriteMsg(PBStream $stream, $msg = "", $newLine = TRUE) {
			if ( $newLine ) $msg = "{$msg}\n";
			$stream->write($msg)->flush();
		}
	}
