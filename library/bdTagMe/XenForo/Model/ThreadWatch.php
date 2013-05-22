<?php

class bdTagMe_XenForo_Model_ThreadWatch extends XFCP_bdTagMe_XenForo_Model_ThreadWatch
{
	public function bdTagMe_getNotifiedUserIds($threadId)
	{
		$userIds = array();

		if (!empty(XenForo_Model_ThreadWatch::$_preventDoubleNotify))
		{
			if (isset(XenForo_Model_ThreadWatch::$_preventDoubleNotify[$threadId]))
			{
				$userIds = array_keys(XenForo_Model_ThreadWatch::$_preventDoubleNotify[$threadId]);
			}
		}

		return $userIds;
	}
}