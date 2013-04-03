<?php

class bdTagMe_XenForo_Model_Post extends XFCP_bdTagMe_XenForo_Model_Post {
	
	protected static $_quotedUserIdsCache = array();
	
	public function alertQuotedMembers(array $post) {
		$hash = md5($post['message']);
		
		if (!empty($post['bdTagMe_alertQuotedMembers_useCache'])) {
			if (isset(self::$_quotedUserIdsCache[$hash])) {
				// good
			} else {
				// skip running the original code
				self::$_quotedUserIdsCache[$hash] = array();
			}
		} else {
			// runs the original code
			self::$_quotedUserIdsCache[$hash] = parent::alertQuotedMembers($post);
		}
		
		return self::$_quotedUserIdsCache[$hash];
	}
}