<?php

class bdTagMe_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_bdTagMe_XenForo_DataWriter_DiscussionMessage_Post {
	
	const BDTAGME_UNIQUE_ID = 'post-new'; 
	
	public function set($field, $value, $tableName = '', array $options = null) {
		if ($field == 'message') {
			$engine = bdTagMe_Engine::getInstance();
			$options = array(
				'max'           => bdTagMe_Option::get('max'),
				'mode'          => bdTagMe_Option::get('mode'),
				'modeCustomTag' => bdTagMe_Option::get('modeCustomTag'),
				'removePrefix'  => bdTagMe_Option::get('removePrefix'),
			);
			$errorInfo = false;
			
			if (!$engine->searchTextForTagged(self::BDTAGME_UNIQUE_ID, $value, $options, $errorInfo)) {
				$engine->issueDwError($this, 'message', $errorInfo);
			}
		}
		
		parent::set($field,$value,$tableName,$options);
	}
	
	protected function _postSaveAfterTransaction() {
		parent::_postSaveAfterTransaction();
		
		// TODO: think about additional check for message_state or something like that?
		// it's probably useful to just send out notification in those cases
		// the post may get approved soon enough, who knows?
		
		$engine = bdTagMe_Engine::getInstance();
		$post = $this->getMergedData();
		
		/* @var $postModel XenForo_Model_Post */
		$postModel = $this->_getPostModel();
		
		$quotedUserIds = $postModel->bdTagMe_getQuotedUserIds($post);
		
		$engine->notifyTaggedUsers2(
			self::BDTAGME_UNIQUE_ID,
			'post', $post['post_id'], $post['user_id'], $post['username'],
			'tagged',
			$quotedUserIds,
			$postModel
		);
	}
}