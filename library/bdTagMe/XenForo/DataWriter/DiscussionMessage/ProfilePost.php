<?php

class bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost extends XFCP_bdTagMe_XenForo_DataWriter_DiscussionMessage_ProfilePost {
	
	const BDTAGME_UNIQUE_ID = 'profile-post-new';
	
	protected $_messagePreSaveCalled = false;
	
	public function set($field, $value, $tableName = '', array $options = null) {
		if ($field == 'message'
			AND $this->_messagePreSaveCalled !== false
		) {
			$engine = bdTagMe_Engine::getInstance();
			$options = array(
				'max'          => bdTagMe_Option::get('max'),
				'mode'         => 'facebookAlike',
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

		$engine = bdTagMe_Engine::getInstance();
		$data = $this->getMergedData();
		$isStatus = $this->isStatus();
		
		/* @var $profilePostModel XenForo_Model_ProfilePost */
		$profilePostModel = $this->_getProfilePostModel();
		
		$engine->notifyTaggedUsers2(
			self::BDTAGME_UNIQUE_ID,
			'profile_post', $data['profile_post_id'], $data['user_id'], $data['username'],
			'tagged',
			array(
				$this->get('profile_user_id'), // obviously the target profile shouldn't be notified again
			),
			$profilePostModel
		);
	}
	
	protected function _messagePreSave() {
		$this->_messagePreSaveCalled = true;
		
		return parent::_messagePreSave();
	}
}