<?php
	final class PBMySQLSource extends PBIDataSource {
	
		private $_pdoConnection = NULL;

		public function __construct( $DSURI = "//user:pass@127.0.0.1:3306/db", $options = array(), $driverOpt = array() ) {

			$URI = is_array($DSURI) ? $DSURI : PBIDataSource::ParseURI( $DSURI );

			$host	= CAST( @$URI[ 'host' ], 'string' );
			$db		= CAST( @$URI[ 'path' ][ 0 ], 'string' );
			$port	= CAST( @$URI[ 'port' ], 'int strict', 3306 );
			$user	= CAST( @$URI[ 'user' ], 'string', '' );
			$pass	= CAST( @$URI[ 'pass' ], 'string', '' );



			if ( !empty($db) ) $options[] = 'CREATE_VAR';

			$this->_pdoConnection = new ExtPDO(
				ExtPDO::DSN( $host, $db, $port, 'mysql' ),
				empty($user) ? NULL : $user,
				empty($pass) ? NULL : $pass,
				array_merge( $options, $driverOpt )
			);
		}
		public function __get_source(){
			return $this->_pdoConnection;
		}



		public function get( $dataNS, $filter, $additional = [] ) {
			
		}
		public function insert( $dataNS, $insertData, $additional = [] ) {

		}
		public function update( $dataNS, $filter, $updatedData = [], $additional = [] ) {

		}
		public function delete( $dataNS, $filter, $additional = [] ) {

		}
		public function bulk( $dataNS, $batchedOps ) {

		}
		public function command( $dataNS, $commands ) {
		
		}
		public function supportive() {
			
		}


		public function count( $dataNS, $filter ) {

		}
		public function range( $dataNS, $filter, $additional = [] ) {

		}
		
		public function aggregate($dataNS, $aggregations = []) {
		
		}
	}
