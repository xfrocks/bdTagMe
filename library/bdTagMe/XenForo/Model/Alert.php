<?php

class bdTagMe_XenForo_Model_Alert extends XFCP_bdTagMe_XenForo_Model_Alert
{
	static protected $_bdTagMe_alerted = array();

	public function alertUser($alertUserId, $userId, $username, $contentType, $contentId, $action, array $extraData = null)
	{
		self::$_bdTagMe_alerted[$contentType][$contentId][$alertUserId] = $action;

		return parent::alertUser($alertUserId, $userId, $username, $contentType, $contentId, $action, $extraData);
	}

	public function bdTagMe_isAlerted($contentType, $contentId, $userId)
	{
		if (!isset(self::$_bdTagMe_alerted[$contentType]))
		{
			return false;
		}

		if (!isset(self::$_bdTagMe_alerted[$contentType][$contentId]))
		{
			return false;
		}

		return isset(self::$_bdTagMe_alerted[$contentType][$contentId][$userId]);
	}

	public function bdTagMe_getAlertedUserIds($contentType, $contentId)
	{
		if (!isset(self::$_bdTagMe_alerted[$contentType]))
		{
			return array();
		}

		if (!isset(self::$_bdTagMe_alerted[$contentType][$contentId]))
		{
			return array();
		}

		return array_keys(self::$_bdTagMe_alerted[$contentType][$contentId]);
	}

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
