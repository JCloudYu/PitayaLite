<?php
	// INFO: Constant Definitions
	define( 'MINUTE_SEC',	60 );
	define( 'HOUR_SEC',		60 * MINUTE_SEC );
	define( 'DAY_SEC',		24 * HOUR_SEC );
	define( 'WEEK_SEC',		  7 * DAY_SEC );
	define( 'MONTH_SEC',	 30 * DAY_SEC );
	define( 'YEAR_SEC',		365 * DAY_SEC );

	// INFO: Digital Sizes
	define( 'KB', 	   1024.0 );	// KiloByte
	define( 'MB', KB * 1024.0 );	// MegaByte
	define( 'GB', MB * 1024.0 );	// GigaByte
	define( 'TB', GB * 1024.0 );	// TeraByte
	define( 'PB', TB * 1024.0 );	// PetaByte
	define( 'EB', PB * 1024.0 );	// ExaByte
	define( 'ZB', EB * 1024.0 );	// ZetaByte
	define( 'YB', ZB * 1024.0 );	// YotaByte
	
	// INFO: Math
	s_define('DEG2RAD',	0.017453292519943, 					TRUE);
	s_define('RAD2DEG',	57.29577951412932, 					TRUE);
	s_define('PI',		3.141592653589793238462643383279, 	TRUE);