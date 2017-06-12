<?php
	use \MongoDB\Driver\Query;
	use \MongoDB\Driver\BulkWrite;
	use \MongoDB\Driver\Command;
	use \MongoDB\BSON\ObjectID;
	use \MongoDB\BSON\Regex;

	final class PBMongoSource extends PBIDataSource {
		const AGGREGATION_OPRATORS = [
			'$project',
			'$match',
			'$redact',
			'$limit',
			'$skip',
			'$unwind',
			'$group',
			'$sample',
			'$sort',
			'$geoNear',
			'$lookup',
			'$out',
			'$indexStats'
		];

		private $_mongoConnection = NULL;
		private $_defaultDB = NULL;

		public function __construct( $DSURI = "//127.0.0.1:27017/db", $options = array(), $driverOpt = array() ) {
			if ( !preg_match('/^mongodb:([\/]{2}[^\/]+)(\/[^\/]*)?(\/.*)*$/', $DSURI, $matches ) ) {
				throw new PBException( "Given data source URI is incorrect!" );
			}
			
			$URI = @"mongodb:{$matches[1]}";
			$DB	 = substr( "{$matches[2]}", 1 );
			$this->_defaultDB = empty($DB) ? NULL : $DB;
			$this->_mongoConnection = new \MongoDB\Driver\Manager( $URI, $options, $driverOpt );
		}
		public function __get_source() {
			return $this->_mongoConnection;
		}



		public function get( $dataNS, $filter, $options = [] ) {
			$dataNS = $this->CastName($dataNS);
			
			if ( is_a($options, stdClass::class) ) {
				$additional = $options;
			}
			else {
				$additional = stdClass($options, TRUE);
			}
			
			

			if ( empty($additional->aggregation) ) {
				$result = $this->getQuery( $dataNS, $filter, $additional );
			}
			else {
				$result = $this->getAggregate( $dataNS, $filter, $additional );
			}
			
			
			
			return $result;
		}
		public function insert( $dataNS, $insertData, $options = [] ) {
			$dataNS = $this->CastName($dataNS);
			$additional = is_a($options, stdClass::class) ? (array)$options : $options;
			

			// INFO: Prepare write info
			$bulkWrite = new BulkWrite();
			$castId = ( !!@$additional['cast-object-id'] || !!@$additional['cast-id'] );

			if ( empty($additional['multiple']) )
			{
				$insertData = (array)$insertData;
				unset( $insertData['_id'] );
				$id = $bulkWrite->insert( $insertData );
				$sessionId = (!$castId) ? $id : "{$id}";
			}
			else
			{
				$sessionId = [];
				foreach ( $insertData as $doc )
				{
					$doc = (array)$doc;
					unset( $doc['_id'] );
					$id = $bulkWrite->insert( $doc );
					$sessionId[] = $castId ? "{$id}" : $id;
				}
			}



			// INFO: Write and collect results
			$result = $this->_mongoConnection->executeBulkWrite( $dataNS, $bulkWrite );
			return ( is_a( $result, '\MongoDB\Driver\WriteResult' ) ? $sessionId: FALSE );
		}
		public function update( $dataNS, $filter, $updatedData = [], $options = [] ) {
			$dataNS = $this->CastName($dataNS);
			$additional = is_a($options, stdClass::class) ? (array)$options : $options;
			
			$custom		= (!!@$additional[ 'customize' ] || !!@$additional[ 'compound-update' ]);
			$rawResult	= !!@$additional[ 'raw-result' ];
			$updateId	= !!@$additional[ 'update-id' ];

			unset( $additional[ 'customize' ] );
			unset( $additional[ 'compound-update' ] );
			unset( $additional[ 'raw-result' ] );
			unset( $additional[ 'update-id' ] );



			// INFO: Prepare update info
			$bulkWrite 	= new BulkWrite();
			$updatedData = (array)$updatedData;
			if ( !$updateId ) {
				unset( $updatedData['_id'] );
			}

			$updateData = $custom ? $updatedData : [ '$set' => $updatedData ];
			$bulkWrite->update( $filter, $updateData, $additional );



			// INFO: Update and collect results
			$result = $this->_mongoConnection->executeBulkWrite( $dataNS, $bulkWrite );
			if ( !is_a( $result, '\MongoDB\Driver\WriteResult' ) ) return FALSE;
			
			return (!$rawResult) ? $result->getModifiedCount() : $result;
		}
		public function delete( $dataNS, $filter, $options = [] ) {
			$dataNS = $this->CastName($dataNS);
			$additional = is_a($options, stdClass::class) ? (array)$options : $options;
			
			$deleteOne	= !!@$additional[ 'just-one' ];
			$rawResult	= !!@$additional[ 'raw-result' ];
			
			unset($additional[ 'just-one' ]);
			unset($additional[ 'raw-result' ]);

			if ( $deleteOne ) {
				$additional[ 'limit' ] = TRUE;
			}
				
			
			// INFO: Prepare delete info
			$bulkWrite = new BulkWrite();
			$bulkWrite->delete( (object)$filter, $additional );



			// INFO: Delete and collect results
			$result = $this->_mongoConnection->executeBulkWrite( $dataNS, $bulkWrite );
			if ( !is_a( $result, '\MongoDB\Driver\WriteResult' ) ) return FALSE;
			
			return (!$rawResult) ? $result->getDeletedCount() : $result;
		}
		public function bulk( $dataNS, $batchedOps, $options = [] ) {
			$dataNS = $this->CastName($dataNS);
			$additional = is_a($options, stdClass::class) ? (array)$options : $options;
			
			// INFO: Prepare delete info
			$bulkWrite = new BulkWrite( [ 'ordered' => !empty($additional[ 'ordered' ]) ] );
			foreach( $batchedOps as $bulkOp )
			foreach( $bulkOp as $op => $content )
			{
				switch( $op )
				{
					case "insert":
						$bulkWrite->insert( $content[ 'data' ] );
						break;

					case "update":
						$multiple = array_key_exists( 'multiple', $content ) ? !empty($content['multiple']) : TRUE;
						$bulkWrite->update( $content['filter'], $content['data'], [ 'multi' => $multiple ] );
						break;

					case "delete":
						$multiple = array_key_exists( 'multiple', $content ) ? !empty($content['multiple']) : TRUE;
						$bulkWrite->delete( $content['filter'], [ 'limit' => !$multiple ] );
						break;

					default: break;
				}
			}



			// INFO: Delete and collect results
			$result = $this->_mongoConnection->executeBulkWrite( $dataNS, $bulkWrite );
			return ( is_a( $result, '\MongoDB\Driver\WriteResult' ) ? $result: FALSE );
		}
		public function command( $dataNS, $commands ) {
			$dataNS = $this->CastName($dataNS);
			
			$ns = self::ResolveNameSpace( $dataNS );
			return $this->_mongoConnection->executeCommand( $ns['database'], new Command($commands) );
		}
		public function supportive() {
			static $supportive = NULL;
			if ( $supportive === NULL ) {
				$supportive = new PBMongoSourceSupportive($this->_mongoConnection);
			}
			
			return $supportive;
		}
		public function count( $dataNS, $filter ) {
			$dataNS = $this->CastName($dataNS);
			
			$ns = self::ResolveNameSpace( $dataNS );

			$cursor = $this->_mongoConnection->executeCommand(
				$ns['database'],
				new Command([ 'count' => $ns['collection'], 'query' => $filter ])
			);

			return $cursor->toArray()[0]->n;
		}
		public function range( $dataNS, $filter, $options = [], $aggregate = FALSE ) {
			$dataNS = $this->CastName($dataNS);
			
			if ( is_a($options, stdClass::class) ) {
				$additional = $options;
			}
			else {
				$additional = stdClass($options, TRUE);
			}
			


			$page 		= CAST( @$additional->page, 'int' );
			$pageSize 	= CAST( @$additional->pageSize, 'int' );
			$totalCount = empty($aggregate) ? $this->count($dataNS, $filter) : $this->countAggregate($dataNS, $filter);




			if ( empty( $pageSize ) ) {
				$totalPages = $page = 1;
				$pageSize	= $totalCount;
				$range		= stdClass(['skip' => 0, 'limit' => $totalCount ]);
			}
			else {
				$totalPages = ceil( (float)$totalCount / (float)$pageSize );
				$page		= min( max( $page, 1 ), max( $totalPages, 1 ) );
				$range		= stdClass([ 'skip' => ( $page - 1 ) * $pageSize, 'limit' => $pageSize ]);
			}



			// INFO: Write information back
			$additional->page		= $page;
			$additional->pageSize	= $pageSize;
			$additional->pageAmt	= $totalPages;
			$additional->total		= $totalCount;


			return $range;
		}
		public function aggregate( $dataNS, $aggregations = [] ) {
			$dataNS = $this->CastName($dataNS);
			$ns = self::ResolveNameSpace( $dataNS );
			
			return $this->_mongoConnection->executeCommand( $ns[ 'database' ], new Command([
				'aggregate' => $ns[ 'collection' ],
				'pipeline'	=> $aggregations,
				'cursor'	=> (object)[]
			]));
		}
		
		
		const INTERNAL_GET_OPTIONS = [ "page", "pageSize", "pageAmt", "total", 'order' ];
		private function& getQuery( $dataNS, $filter, stdClass $additional ) {
			$dataNS = $this->CastName($dataNS);
		
			$queryOpt = [];
			if ( !empty($additional->page) ) {
				$range = $this->range( $dataNS, $filter, $additional );
				$queryOpt[ 'skip' ]		= $range->skip;
				$queryOpt[ 'limit' ]	= $range->limit;
			}

			if ( !empty($additional->order) ) {
				$queryOpt[ 'sort' ] = $additional->order;
			}

			foreach( $additional as $option => $value ) {
				if ( in_array($option, self::INTERNAL_GET_OPTIONS) ) continue;
				$queryOpt[ $option ] = $value;
			}


			// INFO: Query and collect results
			$cursor = $this->_mongoConnection->executeQuery( $dataNS, new Query( (object)$filter, $queryOpt ) );
			$result = empty($additional->collect) ? $cursor : self::__COLLECT_DATA($cursor);
			return $result;
		}
		private function& getAggregate( $dataNS, $baseQuery, stdClass $additional ) {
			$dataNS = $this->CastName($dataNS);
			
			$aggregation = $queryOpt = [];
			$aggregation[] = [ '$match' => stdClass($baseQuery) ];

			if ( !empty($additional->order) ) {
				$aggregation[] = [ '$sort' => stdClass($additional->order) ];
			}

			if ( !empty($additional->projection) ) {
				$aggregation[] = [ '$project' => stdClass($additional->projection) ];
			}

			if ( !empty($additional->aggregation) ) {
				foreach( $additional->aggregation as $op ) {
					if ( !in_array(key($op), self::AGGREGATION_OPRATORS) ) continue;
					$aggregation[] = (object)$op;
				}
			}

			if ( !empty($additional->page) ) {
				$range = $this->range( $dataNS, $aggregation, $additional, TRUE );
				$aggregation[] = [ '$skip'	=> $range->skip ];
				$aggregation[] = [ '$limit' => $range->limit ];
			}


			// INFO: Query and collect results
			$ns = self::ResolveNameSpace( $dataNS );
			$cursor = $this->_mongoConnection->executeCommand( $ns[ 'database' ], new Command([
				'aggregate' => $ns['collection'],
				'pipeline'	=> $aggregation,
				'cursor'	=> (object)[]
			]));
			
			return empty($additional->collect) ? $cursor : self::__COLLECT_DATA($cursor);
		}
		private function countAggregate( $dataNS, $baseAggregation ) {
			$dataNS = $this->CastName($dataNS);
			
			$ns = self::ResolveNameSpace( $dataNS );

			$baseAggregation[] = ['$group' => ['_id' => NULL, 'count' => ['$sum' => 1]]];


			$cursor = $this->_mongoConnection->executeCommand( $ns[ 'database' ], new Command([
				'aggregate' => $ns['collection'],
				'pipeline'	=> $baseAggregation,
				'cursor'	=> (object)[]
			]));
			return $cursor->toArray()[0]->count;
		}

		public function CastName( $name ) {
			if ( substr($name, 0, 3) === "db." ) {
				return "{$this->_defaultDB}." . substr($name, 3);
			}
			
			return $name;
		}
		public static function ResolveNameSpace( $namespace ) {
			$ns = explode( '.', $namespace );
			return [ 'database' => @$ns[0], 'collection' => @$ns[1] ];
		}
		public static function MongoCollect( $document, &$idx ) {
			$idx = "{$document->_id}";
			return $document;
		}
		public static function ObjectID( $hexStr = NULL ){
			return MongoID( $hexStr );
		}
		
		private static function& __COLLECT_DATA( $iterator ) {
			$data = [];
			foreach( $iterator as $record ) {
				$data[] = $record;
			}
			return $data;
		}
	}
	
	class PBMongoSourceSupportive {
		private $_mongoConnection = NULL;
		public function __construct( $conn ) { $this->_mongoConnection = $conn; }
		public function createCollection( $dbName, $collectionName, $checkValid = TRUE ) {
			if ( $checkValid ) {
				$coll = $this->getCollection( $dbName, $collectionName );
				if ( !empty($coll) ) return FALSE;
			}
			
			return $this->_mongoConnection->executeCommand( $dbName, new Command([
				'create' => $collectionName,
			]));
		}
		public function createIndex( $dbName, $targetCollection, $indexes = [], $checkValid = TRUE ) {
			if ( !is_array($indexes) || empty($indexes) ) return FALSE;
			if ( $checkValid ) {
				$coll = $this->getCollection( $dbName, $targetCollection );
				if ( empty($coll) ) return FALSE;
			}
		
			$NS = "{$dbName}.{$targetCollection}";
			foreach( $indexes as &$discriptor ) {
				if ( is_object($discriptor) ) {
					$discriptor->ns = $NS;
				}
				else
				if ( is_array($discriptor) ) {
					$discriptor[ 'ns' ] = $NS;
				}
			}
		
			
			return $this->_mongoConnection->executeCommand( $dbName, new Command([
				'createIndexes' => $targetCollection,
				'indexes' => $indexes
			]));
		}
		public function getCollection( $dbName, $nameFilter = [] ) {
			if ( !is_array($nameFilter) ) {
				if ( empty($nameFilter) ) {
					return [];
				}
				
				$nameFilter = [ $nameFilter ];
			}
			
			
			$options = [ 'listCollections' => 1 ];
			if ( !empty($nameFilter) ) {
				$options[ 'filter' ] =  [
					'name' => [ '$in' => $nameFilter ]
				];
			}
		
			
		
		
		
			$data = [];
			$ANCHOR = $this->_mongoConnection->executeCommand( $dbName, new Command($options));
			
			foreach( $ANCHOR as $collection ) {
				$data[] = $collection;
			}
			
			
			
			return $data;
		}
		public function getIndex( $dbName, $collectionName, $nameFilter = [] ) {
			if ( !is_array($nameFilter) ) {
				if ( empty($nameFilter) ) {
					return [];
				}
				
				$nameFilter = [ $nameFilter ];
			}
		
		
		
			$data = [];
			$ANCHOR = $this->_mongoConnection->executeCommand( $dbName, new Command([
				'listIndexes' => $collectionName
			]));
			
			$fetchAll = empty($nameFilter);
			foreach( $ANCHOR as $index ) {
				if ( $fetchAll )
					$data[$index->name] = $index;
				else
				if ( in_array($index->name, $nameFilter) ) {
					$data[$index->name] = $index;
				}
			}
			
			return $data;
		}
		public function dropCollection( $dbName, $collection, $checkValid = TRUE ) {
			if ( $checkValid ) {
				$collInfo = $this->getCollection($dbName, $collection);
				if ( empty($collInfo) ) return FALSE;
			}
			
			$data = [];
			$ANCHOR = $this->_mongoConnection->executeCommand( $dbName, new Command([
				'drop' => $collection,
			]));
			
			return TRUE;
		}
	}
	
	function MongoRecursiveQuery($item) {
		if ( !is_a($item, stdClass::class) && !is_assoc($item) ) {
			return FALSE;
		}
		
		$query = [];
		foreach( $item as $prop => $value ) {
			$resolved = MongoRecursiveQuery($value);
			if ( $resolved === FALSE || $prop[0] == '$' ) {
				$query[$prop] = $value;
				continue;
			}
			
			foreach( $resolved as $field => $update ) {
				if ( $field[0] == "$" )
					$query[ "{$prop}" ][ $field ] = $update;
				else
					$query[ "{$prop}.{$field}" ] = $update;
			}
		}
		
		return $query;
	}
	function PBMongoID( $hexStr = NULL ) {
		try {
			if ( func_num_args() > 0 ) {
				$objId = new ObjectID("{$hexStr}");
			}
			else {
				$objId = new ObjectID();
			}
			
			return $objId;
		}
		catch(Exception $e) {
			return NULL;
		}
	}
	function MongoID( $hexStr = NULL ) {
		return call_user_func_array( 'PBMongoID', func_get_args() );
	}
	function MongoRegex( $pattern, $flag = "" ) {
		return new Regex( $pattern, $flag );
	}
