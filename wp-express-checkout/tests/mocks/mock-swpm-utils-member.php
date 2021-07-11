<?php

/**
 * SwpmMemberUtils
 * All the utility functions related to member records should be added to this class
 */
class SwpmMemberUtils {

	public static function is_member_logged_in() {
		return true;
	}

	public static function get_logged_in_members_id() {
		return get_current_user_id();
	}
}
