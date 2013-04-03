<?php

class bdTagMe_XenForo_Model_ProfilePost extends XFCP_bdTagMe_XenForo_Model_ProfilePost {

	protected static $_commentUserIdsCache = array();
	
	public function getProfilePostCommentUserIds($profilePostId)
	{
		if (!isset(self::$_commentUserIdsCache[$profilePostId])) {
			// if the cache has no info, let the parent do its work
			self::$_commentUserIdsCache[$profilePostId] = parent::getProfilePostCommentUserIds($profilePostId);
		}
		
		return self::$_commentUserIdsCache[$profilePostId];
	}
}