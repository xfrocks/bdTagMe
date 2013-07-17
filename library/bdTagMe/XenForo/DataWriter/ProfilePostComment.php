<?php

class bdTagMe_XenForo_DataWriter_ProfilePostComment extends XFCP_bdTagMe_XenForo_DataWriter_ProfilePostComment
{

	protected $_bdTagMe_taggedUsers = array();

	protected function _preSave()
	{
		/* @var $taggingModel XenForo_Model_UserTagging */
		$taggingModel = $this->getModelFromCache('XenForo_Model_UserTagging');

		$this->_bdTagMe_taggedUsers = $taggingModel->getTaggedUsersInMessage($this->get('message'), $newMessage, '');
		$this->set('message', $newMessage);

		return parent::_preSave();
	}

	protected function _postSaveAfterTransaction()
	{
		$response = parent::_postSaveAfterTransaction();

		$engine = bdTagMe_Engine::getInstance();
		$comment = $this->getMergedData();

		/* @var $profilePostModel XenForo_Model_ProfilePost */
		$profilePostModel = $this->_getProfilePostModel();

		$otherCommenterIds = $profilePostModel->getProfilePostCommentUserIds($comment['profile_post_id']);
		$noAlertUserIds = $otherCommenterIds;

		$profileUser = $this->getExtraData(self::DATA_PROFILE_USER);
		if (!empty($profileUser))
		{
			$noAlertUserIds[] = $profileUser['user_id'];
		}

		$profilePost = $this->getExtraData(self::DATA_PROFILE_POST);
		if (!empty($profilePost))
		{
			$noAlertUserIds[] = $profilePost['user_id'];
		}

		$noEmailUserIds = array();

		$engine->notifyTaggedUsers3('profile_post', $comment['profile_post_id'], $comment['user_id'], $comment['username'], 'comment_tagged', $this->_bdTagMe_taggedUsers, $noAlertUserIds, $noEmailUserIds, $this->_getProfilePostModel());

		return $response;
	}

}
