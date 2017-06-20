<?php
	class PBPagination extends PBObject
	{
		const DEFAULT_VISIBLE_SIZE = 10;
		
		public function __construct( $current = 0, $total = 0, $visibleSize = PBPagination::DEFAULT_VISIBLE_SIZE )
		{
			$this->_total		= $total;
			$this->_current		= $current;
			$this->_visibleSize	= $visibleSize;
		}

		private $_visibleSize = self::DEFAULT_VISIBLE_SIZE;
		public function __set_visibleSize( $value )	{ $this->_visibleSize = ( $value < 1 ) ? 1 : $value; }
		public function __get_visibleSize()			{ $this->_visibleSize; }

		private $_total = 0;
		public function __set_totalPages( $value ) { $this->_total = ( $value < 0 ) ? 0 : $value; }
		public function __get_totalPages()		   { $this->_total; }

		private $_current = 0;
		public function __set_currentPage( $value ) { $this->_current = ( $value < 0 ) ? 0 : $value; }
		public function __get_currentPage()			{ $this->_current; }

		public $_sliding = FALSE;
		public function __set_sliding( $value )	{ $this->_sliding = ( $value ? TRUE: FALSE); }
		public function __get_sliding()			{ return $this->_sliding; }

		public $_boundaryJumpers = FALSE;
		public function __set_boundaryJumpers( $value )	{ $this->_boundaryJumpers = ( $value ? TRUE: FALSE ); }
		public function __get_boundaryJumpers()			{ return $this->_boundaryJumpers; }

		public $_shiftJumpers = FALSE;
		public function __set_shiftJumpers( $value )	{ $this->_shiftJumpers = ( $value ? TRUE: FALSE ); }
		public function __get_shiftJumpers()			{ return $this->_shiftJumpers; }

		public $_itemGenerator = NULL;
		public function __set_generator( $delegate ) { $this->_itemGenerator = is_callable( $delegate ) ? $delegate : NULL; }
		public function __get_hasGenerator() { return $this->_itemGenerator !== NULL; }



		public function __get_html() { return $this->render(); }



		private $_renderedCache = NULL;
		public function render( $force = FALSE )
		{
			if ( $this->_renderedCache !== NULL && !$force ) return $this->_renderedCache;

			if ( $this->_total < 0 ) return NULL;



			$total = $this->_total;
			$callable = ( $this->_itemGenerator ) ? $this->_itemGenerator : function( $page, $isActive = FALSE, $type = 'normal' ) use( $total ) {
				$digits = 0;
				while ( $total > 0 ) { $total = floor($total / 10); $digits++; }

				$activeClass = $isActive ? "active" : "";
				$pageText = sprintf( "%0{$digits}d", $page );
				return "<a class='{$activeClass}' href='#{$page}'>{$pageText}</a>";
			};


			$from = $to = 0;
			$this->calcRange( $from, $to );

			$pageItems = array();
			for ( $pNum = $from; $pNum <= $to; $pNum++ )
				$pageItems[] = @$callable( $pNum, $pNum == $this->_current );



			if ( $this->_shiftJumpers )
			{
				if ( $this->_current > 1 )				array_unshift( $pageItems, @$callable( $this->_current - 1, FALSE, 'prev' ) );
				if ( $this->_current < $this->_total )	array_push( $pageItems, @$callable( $this->_current + 1, FALSE, 'next' ) );
			}

			if ( $this->_boundaryJumpers )
			{
				if ( $from != 1 )			 array_unshift( $pageItems, @$callable( 1, FALSE, 'begin' ) );
				if ( $to != $this->_total )  array_push( $pageItems, @$callable( $this->_total, FALSE, 'end' ) );
			}


			return $this->_renderedCache = implode( '', $pageItems );
		}

		private function calcRange( &$from, &$to )
		{
			// INFO: Sliding Window
			if ( $this->_sliding )
			{
				$visibleSize = $this->_visibleSize < 3 ? 3 : $this->_visibleSize;

				$lowCount = floor( ( $visibleSize - 1.0 ) / 2.0 );
				$upCount  = $visibleSize - $lowCount;

				// Bounday conditions
				if ( $this->_current <= $lowCount )
				{
					$from = 1;
					$to	  = $visibleSize;
				}
				else
				if ( $this->_current > ( $this->_total - $upCount ) )
				{
					$to	  = $this->_total;
					$from = $to - $visibleSize + 1;
				}
				else
				{
					$from = $this->_current - $lowCount;
					$to	  = $from + $visibleSize - 1;
				}
			}
			// INFO: Section Based Window
			else
			{
				// Boundary conditions
				if ( $this->_current <= $this->_visibleSize )
				{
					$from = 1;
					$to	  = $this->_visibleSize;
				}
				else
				if ( $this->_current > ( $this->_total - $this->_visibleSize ) )
				{
					$from = $this->_total - $this->_visibleSize + 1;
					$to	  = $this->_total;
				}
				else
				{
					$section = ceil( $this->_current / $this->_visibleSize );
					$from	 = ( $section - 1 ) * $this->_visibleSize + 1;
					$to		 = $from + $this->_visibleSize - 1;
				}
			}

			$from = max( $from, 1 );
			$to	  = min( $to, $this->_total );
		}
	}
