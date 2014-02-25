<?php

class bdTagMe_XenForo_DataWriter_ProfilePostComment extends XFCP_bdTagMe_XenForo_DataWriter_ProfilePostComment
{
	protected function _postSave()
	{
		// reset _taggedUsers so the default routine will not resend alerts
		// of course, we kept our copy in local variable $taggedUsers
		$taggedUsers = $this->_taggedUsers;
		$this->_taggedUsers = array();

		$response = parent::_postSave();

		if ($this->isInsert())
		{
			$engine = bdTagMe_Engine::getInstance();
			$profilePost = $this->getExtraData(self::DATA_PROFILE_POST);
			if (!empty($profilePost))
			{
				$noAlertUserIds = $this->getModelFromCache('XenForo_Model_Alert')->bdTagMe_getAlertedUserIds('profile_post', $profilePost['profile_post_id']);
				$noEmailUserIds = array();

				$options = array(bdTagMe_Engine::OPTION_MAX_TAGGED_USERS => $this->getOption(self::OPTION_MAX_TAGGED_USERS));
				$engine->notifyTaggedUsers3('profile_post', $profilePost['profile_post_id'], $profilePost['user_id'], $profilePost['username'], 'tag_comment', $taggedUsers, $noAlertUserIds, $noEmailUserIds, $this->_getProfilePostModel(), $options);
			}
		}

		return $response;
	}

}
