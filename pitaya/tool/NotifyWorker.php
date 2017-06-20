<?php
	/**
	 ** 1024.QueueCounter - Notifier.php
	 ** Created by JCloudYu on 2015/11/02 19:40
	 **/
	using( 'kernel.basis.PBObject' );
	using( 'tool.NotifyKernel.INotifyKernel' );

	class NotifyWorker extends PBObject {
		/**
		 * @var INotifyKernel Notification Kernel Interface
		 */
		private $_kernel = NULL;

		public function __set_kernel( INotifyKernel $value )
		{
			$this->_kernel = $value;
		}
		public function& __get_kernel()
		{
			return $this->_kernel;
		}

		public function send( $msg )
		{
			if ( $this->_kernel === NULL )
				return NULL;

			if ( empty( $msg ) ) return FALSE;
			return $this->_kernel->send( $msg );
		}

		public function batch( $packages )
		{
			if ( $this->_kernel === NULL )
				return NULL;


			$status = array();
			foreach ( $packages as $key => $msgContent ) {
				if ( empty( $msgContent ) ) {
					$status[ $key ] = FALSE;
					continue;
				}

				$status[ $key ] = $this->_kernel->send( $msgContent );
			}

			return $status;
		}
	}
