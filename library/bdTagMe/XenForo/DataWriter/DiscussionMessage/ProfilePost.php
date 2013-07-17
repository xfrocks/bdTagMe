<?php

class bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost extends XFCP_bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost
{
	protected $_bdTagMe_taggedUsers = array();

	protected function _messagePreSave()
	{
		/* @var $taggingModel XenForo_Model_UserTagging */
		$taggingModel = $this->getModelFromCache('XenForo_Model_UserTagging');

		$this->_bdTagMe_taggedUsers = $taggingModel->getTaggedUsersInMessage($this->get('message'), $newMessage, '');
		$this->set('message', $newMessage);

		return parent::_messagePreSave();
	}

	protected function _postSaveAfterTransaction()
	{
		$response = parent::_postSaveAfterTransaction();

		$engine = bdTagMe_Engine::getInstance();
		$profilePost = $this->getMergedData();

		$noAlertUserIds = array($this->get('profile_user_id'));
		$noEmailUserIds = array();

		$engine->notifyTaggedUsers3('profile_post', $profilePost['profile_post_id'], $profilePost['user_id'], $profilePost['username'], 'tagged', $this->_bdTagMe_taggedUsers, $noAlertUserIds, $noEmailUserIds, $this->_getProfilePostModel());

		return $response;
	}

}
