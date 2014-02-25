<?php

class bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost extends XFCP_bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _alertUser()
	{
		// reset _taggedUsers so the default routine will not resend alerts
		// of course, we kept our copy in local variable $taggedUsers
		$taggedUsers = $this->_taggedUsers;
		$this->_taggedUsers = array();

		$response = parent::_alertUser();

		// TODO: check for profile post validity before sending alerts?
		// currently it is not needed because _alertUser is called only when the post is
		// valid, maybe it will be required at a later time
		$engine = bdTagMe_Engine::getInstance();
		$profilePost = $this->getMergedData();
		$noAlertUserIds = $this->getModelFromCache('XenForo_Model_Alert')->bdTagMe_getAlertedUserIds('profile_post', $profilePost['profile_post_id']);
		$noEmailUserIds = array();

		$options = array(bdTagMe_Engine::OPTION_MAX_TAGGED_USERS => $this->getOption(self::OPTION_MAX_TAGGED_USERS));
		$engine->notifyTaggedUsers3('profile_post', $profilePost['profile_post_id'], $profilePost['user_id'], $profilePost['username'], 'tag', $taggedUsers, $noAlertUserIds, $noEmailUserIds, $this->_getProfilePostModel(), $options);

		return $response;
	}

}
