<?php

class SLM_API_Utility {

	public static $lics = [];

	public static function insert_license_data_internal( $fields ) {
		self::$lics[ $fields['txn_id'] ] = $fields;
	}

}
