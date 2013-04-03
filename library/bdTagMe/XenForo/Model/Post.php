<?php

class bdTagMe_XenForo_Model_Post extends XFCP_bdTagMe_XenForo_Model_Post {
	
	protected static $_quotedUserIdsCache = array();
	
	public function alertQuotedMembers(array $post) {
		$hash = md5($post['message']);
		
		self::$_quotedUserIdsCache[$hash] = parent::alertQuotedMembers($post);
		
		return self::$_quotedUserIdsCache[$hash];
	}
	
	public function bdTagMe_getQuotedUserIds(array $post) {
		$hash = md5($post['message']);
		
		if (isset(self::$_quotedUserIdsCache[$hash])) {
			return self::$_quotedUserIdsCache[$hash];
		} else {
			return array();
		}
	}
}