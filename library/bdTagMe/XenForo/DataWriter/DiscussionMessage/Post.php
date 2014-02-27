<?php

class bdTagMe_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_bdTagMe_XenForo_DataWriter_DiscussionMessage_Post
{

	protected function _postSaveAfterTransaction()
	{
		// reset _taggedUsers so the default routine will not resend alerts
		// of course, we kept our copy in local variable $taggedUsers
		$taggedUsers = $this->_taggedUsers;
		$this->_taggedUsers = array();

		$response = parent::_postSaveAfterTransaction();

		if ($this->get('message_state') == 'visible')
		{
			if ($this->isInsert() || $this->getExisting('message_state') == 'moderated')
			{
				$engine = bdTagMe_Engine::getInstance();
				$post = $this->getMergedData();

				/* @var $forumWatchModel XenForo_Model_ForumWatch */
				$forumWatchModel = $this->getModelFromCache('XenForo_Model_ForumWatch');
				$notifiedUserIds = $forumWatchModel->bdTagMe_getNotifiedUserIds($post);

				$options = array(bdTagMe_Engine::OPTION_MAX_TAGGED_USERS => $this->getOption(self::OPTION_MAX_TAGGED_USERS));
				$engine->notifyTaggedUsers3('post', $post['post_id'], $post['user_id'], $post['username'], 'tag', $taggedUsers, $notifiedUserIds['alerted'], $notifiedUserIds['emailed'], $forumWatchModel, $options);
			}
		}

		return $response;
	}

}
