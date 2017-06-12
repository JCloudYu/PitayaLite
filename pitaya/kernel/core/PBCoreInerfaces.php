<?php
	interface PBIBootResolver {
		public function resolve( $basis, $resource, $attribute, $fragment );
	}