<?php
	class PBHTML {
		public static function GenAttribute( $attrList ){
			if ( is_a( $attrList, stdClass::class ) )
				$attrList = (array)$attrList;
				
			if ( !is_array($attrList) ) return "";
		
		
		
			$buff = [];
			foreach( $attrList as $attr => $value ) {
				if ( !is_string( $attr ) ) {
					$attr  = $value;
					$value = FALSE;
				}
				
				$attr = str_replace( [ "\"", "'", "<", ">", "/", "=" ], "", $attr );
				if ( $attr == "" ) continue;
				
				if ( $value === FALSE )
					$buff[] = "{$attr}";
				else
				{
					$value = htmlspecialchars( $value );
					$buff[] = "{$attr}=\"{$value}\"";
				}
			}
			
			return implode( " ", $buff );
		}
	
	
		/*
		public static function GenAttribute( $attrList ){
			$buff = [];
			foreach( $attrList as $value ) {
				if ( is_string( $value ) )
				{
					$attr  = $value;
					$value = FALSE;
				}
				else
				{
					$value = (object)$value;
					$attr = $value->name;
					$value = $value->value;
				}
			
			

				$attr = str_replace( [ "\"", "'", "<", ">", "/", "=" ], "", $attr );
				if ( $attr == "" ) continue;
				
				if ( $value === FALSE )
					$buff[] = "{$attr}";
				else
				{
					$value = htmlspecialchars( $value );
					$buff[] = "{$attr}=\"{$value}\"";
				}
			}
			
			return implode( " ", $buff );
		}
		*/
	}
