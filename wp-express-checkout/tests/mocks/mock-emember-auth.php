<?php

class Emember_Auth {

	public static function getInstance() {
		return new self();
	}

	public function getUserInfo( $key ) {
		return 42;
	}

}
