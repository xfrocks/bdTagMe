<?php

class bdTagMe_XenForo_Model_Alert extends XFCP_bdTagMe_XenForo_Model_Alert
{

	public function bdTagMe_getContentLink($contentType, $contentId)
	{
		switch ($contentType)
		{
			case 'post':
				return XenForo_Link::buildPublicLink('canonical:posts', array('post_id' => $contentId));
				break;
			case 'profile_post':
				return XenForo_Link::buildPublicLink('canonical:profile-posts', array('profile_post_id' => $contentId));
				break;
		}

		return false;
	}

}
