<?php
	/*
	 * File: PBLinkedList.php
	 * Created by Cloud.
	 * DateTime: 13/4/4 PM8:52
	 *
	 * [QUEUE] TAIL ▌▌▌▌▌▌▌▌▌▌▌▌▌▌▌▌ HEAD
	 * [STACK] TOP  ▌▌▌▌▌▌▌▌▌▌▌▌▌▌▌▌ BOT
	 */

	class PBLinkedList extends PBObject {
	
		private $_head = NULL, $_tail = NULL, $_curr = NULL;
		private $_counter = 0;
	
		private function __construct() {}
	
	
	
		public function &__get_data() {
			if ( $this->_head === NULL || $this->_tail === NULL || $this->_curr === NULL ) return NULL;
			return $this->_curr->_data;
		}
		public function __get_id() {
			if ( $this->_head === NULL || $this->_tail === NULL || $this->_curr === NULL ) return NULL;
			return $this->_curr->_id;
		}
		public function __get_length() {
			if ( $this->_head === NULL || $this->_tail === NULL || $this->_curr === NULL ) return 0;
			return $this->_counter;
		}
	
	
	
		public static function GENERATE() {
			return new PBLinkedList();
		}
		public static function NEXT(&$list) {
			if ( !is_a($list, 'PBLinkedList') || $list->_curr === NULL ) return FALSE;
			if ( empty($list->_curr->_next) ) return FALSE;
			
			$list->_curr = $list->_curr->_next;
			return TRUE;
		}
		public static function PREV(&$list) {
			if ( !is_a($list, 'PBLinkedList') || $list->_curr === NULL ) return FALSE;
			if ( empty($list->_curr->_prev) ) return FALSE;
			
			$list->_curr = $list->_curr->_prev;
			return TRUE;
		}
		public static function PUSH(&$list, $data, $identifier = NULL) {
			if ( !is_a($list, 'PBLinkedList') ) return FALSE;
			if ( !is_string($identifier) && !is_int($identifier) ) $identifier = NULL;


			$item = PBLinkedList::__genItem($data, $identifier);	
			if ( $list->_tail === NULL || $list->_head === NULL || $list->_curr === NULL )
			{
				$list->_head = $item;
				$list->_tail = $item;
				$list->_curr = $item;
			}
			else
			{
				// INFO: Buffer the tail and generate the item that carries inserted data
				$prevItem = $list->_tail;
	
				// INFO: Make linkage of relation available
				$item->_prev = $prevItem;
				$prevItem->_next = $item;
	
				// INFO: Set the tail to be the inserted item
				$list->_tail = $item;
			}
	
			$list->_counter++;
			return TRUE;
		}
		public static function POP(&$list) {
			if ( !is_a($list, 'PBLinkedList') || $list->_curr === NULL ) return FALSE;
	
	
			// INFO: Buffer current tail
			$item = $list->_tail;
			if ( $list->_head === $list->_tail )
			{
				$list->_head = NULL;
				$list->_tail = NULL;
				$list->_curr = NULL;
			}
			else
			{
				// INFO: Move the tail to the previous item
				$list->_tail = $item->_prev;
	
				// INFO: Reset the current pointer
				if($item === $list->_curr) $list->_curr = $item->_prev;
	
				// INFO: Clear the relation
				$list->_tail->_next = NULL;
				$item->_prev = NULL;	// This line is kept for concurrency of the whole idea
	
			}
	
			$list->_counter--;
	
			// INFO: Prepare the returning data and unset the popped item
			$rt = [ 'data' => $item->_data, 'id' => $item->_id ];
			unset($item);
			return $rt;
		}
		public static function ENQUEUE(&$list, $data, $identifier = NULL) {
	
			if(!is_a($list, 'PBLinkedList')) return FALSE;
			if(!is_string($identifier) && !is_int($identifier)) $identifier = NULL;
	
			$item = PBLinkedList::__genItem($data, $identifier);
	
			if($list->_tail === NULL || $list->_head === NULL || $list->_curr === NULL)
			{
				$list->_head = $item;
				$list->_tail = $item;
				$list->_curr = $item;
			}
			else
			{
				// INFO: Buffer the tail and generate the item that carries inserted data
				$nextItem = $list->_next;
	
				// INFO: Make linkage of relation available
				$item->_next = $nextItem;
				$prevItem->_prev = $item;
	
				// INFO: Set the tail to be the inserted item
				$list->_head = $item;
			}
	
			$list->_counter++;
			return TRUE;
		}
		public static function DEQUEUE(&$list) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
	
			// INFO: Buffer current tail
			$item = $list->_head;
	
			if($list->_head === $list->_tail)
			{
				$list->_head = NULL;
				$list->_tail = NULL;
				$list->_curr = NULL;
			}
			else
			{
				// INFO: Move the head to next item
				$list->_head = $item->_next;
	
				// INFO: Reset the current pointer
				if($item === $list->_curr) $list->_curr = $item->_next;
	
				// INFO: Clear the relation
				$list->_head->_prev = NULL;
				$item->_next = NULL;	// This line is kept for concurrency of the whole idea
			}
	
			$list->_counter--;
	
			// INFO: Prepare the returning data and unset the popped item
			$rt = array('data' => $item->_data, 'id' => $item->_id);
			unset($item);
			return $rt;
		}
		public static function BEFORE(&$list, $data, $identifier = NULL) {
	
			if(!is_a($list, 'PBLinkedList')) return FALSE;
			if(!is_string($identifier) && !is_int($identifier)) $identifier = NULL;
	
			$item = PBLinkedList::__genItem($data, $identifier);
	
			if($list->_head === NULL || $list->_tail === NULL || $list->_curr === NULL)
			{
				$list->_head = $item;
				$list->_tail = $item;
				$list->_curr = $item;
			}
			else
			{
				$prevItem = $list->_curr->_prev;
				$nextItem = $list->_curr;
	
				$item->_prev = $prevItem;
				if($prevItem !== NULL) $prevItem->_next = $item;
	
				$item->_next = $nextItem;
				if($nextItem !== NULL) $nextItem->_prev = $item;
	
	
				if($list->_curr === $list->_head)
					$list->_head = $item;
			}
	
			$list->_counter++;
	
			return TRUE;
		}
		public static function AFTER(&$list, $data, $identifier = NULL) {
	
			if(!is_a($list, 'PBLinkedList')) return FALSE;
			if(!is_string($identifier) && !is_int($identifier)) $identifier = NULL;
	
			$item = PBLinkedList::__genItem($data, $identifier);
	
			if($list->_head === NULL || $list->_tail === NULL || $list->_curr === NULL)
			{
				$list->_head = $item;
				$list->_tail = $item;
				$list->_curr = $item;
			}
			else
			{
				$prevItem = $list->_curr;
				$nextItem = $list->_curr->_next;
	
				$item->_prev = $prevItem;
				if($prevItem !== NULL) $prevItem->_next = $item;
	
				$item->_next = $nextItem;
				if($nextItem !== NULL) $nextItem->_prev = $item;
	
				if($list->_curr === $list->_tail)
					$list->_tail = $item;
			}
	
			$list->_counter++;
	
			return TRUE;
		}
		public static function SET(&$list, $data, $identifier = NULL) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
			if(!is_string($identifier) && !is_int($identifier)) $identifier = NULL;
	
			$item = $list->_curr;
			$item->_data = $data;
			$item->_id = $identifier;
	
			return TRUE;
		}
		public static function REMOVE(&$list) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
	
			$item = $list->_curr;
	
			if($list->_head === $list->_tail)
			{
				$list->_head = NULL;
				$list->_tail = NULL;
				$list->_curr = NULL;
			}
			else
			{
				// INFO: Move tail and head pointer first
				if($item === $list->_head)
					$list->_head = $item->_next;
				else
				if($item === $list->_tail)
					$list->_tail = $item->_prev;
	
				// INFO: Process current pointer
				$prevItem = $list->_curr->_prev;
				$nextItem = $list->_curr->_next;
	
				if($prevItem) $prevItem->_next = $nextItem;
				if($nextItem) $nextItem->_prev = $prevItem;
	
				$list->_curr = $prevItem;
				if($list->_curr === NULL) $list->_curr = $nextItem;
			}
	
			$list->_counter--;
			return TRUE;
		}
		public static function DELETE(&$list) {
			return PBLinkedList::REMOVE($list);
		}
		public static function HEAD(&$list) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
	
			$list->_curr = $list->_head;
	
			return TRUE;
		}
		public static function TAIL(&$list) {
	
			if(!is_a($list, 'PBLinkedList') ||  $list->_curr === NULL) return FALSE;
	
			$list->_curr = $list->_tail;
	
			return TRUE;
		}
	
		public static function LOCATE(&$list, $id) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
			if(!is_string($id) && !is_int($id)) return FALSE;
	
			$status = FALSE;
	
			$runner = $list->_head;
			while($runner !== NULL && !$status)
			{
				if($runner->_id === $id)
				{
					$list->_curr = $runner;
					$status = TRUE;
				}
				$runner = $runner->_next;
			}
	
			return $status;
		}
		public static function MOVE(&$list, $index) {
	
			if(!is_a($list, 'PBLinkedList') || $list->_curr === NULL) return FALSE;
			if(!is_int($index)) return FALSE;
	
			if($index > $list->_counter-1) return FALSE;
	
			$runner = $list->_head;
			for($i = 0; $i <= $index; $i++)
			{
				if($i == $index) break;
				$runner = $runner->_next;
			}
	
			$list->_curr = $runner;
	
	
			return TRUE;
		}
		
		
		
		private static function __genItem($data, $id) {
	
			$obj = stdClass();
			$obj->_data = $data;
			$obj->_id = $id;
	
			$obj->_prev = NULL;
			$obj->_next = NULL;
	
			return $obj;
		}
	}
	
	class_alias('PBLinkedList', 'PBLList');
