<?php

class bdTagMe_XenForo_DataWriter_ProfilePostComment extends XFCP_bdTagMe_XenForo_DataWriter_ProfilePostComment {
	
	const BDTAGME_UNIQUE_ID = 'profile-post-comment-new'; 
	
	public function set($field, $value, $tableName = '', array $options = null) {
		if ($field == 'message') {
			$engine = bdTagMe_Engine::getInstance();
			$options = array(
				'max'           => bdTagMe_Option::get('max'),
				'mode'          => 'facebookAlike',
			);
			$errorInfo = false;
			
			if (!$engine->searchTextForTagged(self::BDTAGME_UNIQUE_ID, $value, $options, $errorInfo)) {
				switch ($errorInfo[0]) {
					case bdTagMe_Engine::ERROR_TOO_MANY_TAGGED:
						$this->error(
							new XenForo_Phrase('bdtagme_you_can_only_tag_x_people', $errorInfo[1]),
							'message'
						);
						break;
				}
			}
		}
		
		parent::set($field,$value,$tableName,$options);
	}
	
	protected function _postSaveAfterTransaction() {
		parent::_postSaveAfterTransaction();
		
		$engine = bdTagMe_Engine::getInstance();
		$comment = $this->getMergedData();
		
		/* @var $profilePostModel XenForo_Model_ProfilePost */
		$profilePostModel = $this->_getProfilePostModel();
		
		$profileUser = $this->getExtraData(self::DATA_PROFILE_USER);
		$profilePost = $this->getExtraData(self::DATA_PROFILE_POST);
		$otherCommenterIds = $profilePostModel->getProfilePostCommentUserIds($comment['profile_post_id']); // this method is cached
		
		$ignoredUserIds   = $otherCommenterIds;      // ignore the commenters
		$ignoredUserIds[] = $profileUser['user_id']; // ignore the profile owner
		$ignoredUserIds[] = $profilePost['user_id']; // ignore the profile post's poster
		
		$engine->notifyTaggedUsers2(
			self::BDTAGME_UNIQUE_ID,
			'profile_post', $comment['profile_post_id'], $comment['user_id'], $comment['username'],
			'comment_tagged',
			$ignoredUserIds,
			$profilePostModel
		);
	}
}