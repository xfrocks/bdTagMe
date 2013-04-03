<?php

class bdTagMe_XenForo_ControllerPublic_Member extends XFCP_bdTagMe_XenForo_ControllerPublic_Member {
	
	public function actionIndex() {
		$tagged = $this->_input->filterSingle('tagged', XenForo_Input::STRING);
		
		if (empty($tagged)) {
			// this is not our request, let the parent handle it
			return parent::actionIndex();
		}
		
		
	}
	
	public function actionTagged() {
		$entityId = $this->_input->filterSingle('entity_id', XenForo_Input::STRING);
		
		if (is_numeric($entityId)) {
			// numeric entity id is reserved for users
			// perform the redirect here
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				XenForo_Link::buildPublicLink('members', array('user_id' => $entityId))
			);
		}
		
		$parts = explode(',', $entityId);
		if (count($parts) != 2) {
			return $this->responseNoPermission();
		}
		
		switch ($parts[0]) {
			case 'user_group':
				$userGroupId = intval($parts[1]);
				$engine = bdTagMe_Engine::getInstance();
				$taggableUserGroups = $engine->getTaggableUserGroups();
				
				if (!isset($taggableUserGroups[$userGroupId])) {
					// hmm, the requested user group is not taggable...
					return $this->responseNoPermission();
				}
				$userGroup = $taggableUserGroups[$userGroupId];

				if (isset($userGroup['userIds'])) {
					$users = $this->getModelFromCache('XenForo_Model_User')->getUsersByIds($userGroup['userIds']);
				} else {
					$users = array();
				}
				
				$viewParams = array(
					'users' => $users,
					'userGroup' => $userGroup,
				);
		
				return $this->responseView('bdTagMe_ViewPublic_Member_Tagged_UserGroup', 'bdtagme_members_tagged_user_group', $viewParams);
			default:
				// unknown entity type
				return $this->responseNoPermission();
		}
	}
	
	public function actionTagSuggestions() {
		$response = parent::actionFind();
		
		if ($response instanceof XenForo_ControllerResponse_View) {
			$users =& $response->params['users'];
			$q = utf8_strtolower($this->_input->filterSingle('q', XenForo_Input::STRING));
			$qLen = utf8_strlen($q);
			
			if ($qLen > 0 AND bdTagMe_Option::get('groupTag')) {
				$userGroups = bdTagMe_Engine::getInstance()->getTaggableUserGroups();
				
				foreach ($userGroups as $userGroup) {
					if (utf8_strtolower(utf8_substr($userGroup['title'], 0, $qLen)) == $q) {
						// run extra check to eliminate users with matching username with this user group
						foreach (array_keys($users) as $userId) {
							if (utf8_strtolower($users[$userId]['username']) == utf8_strtolower($userGroup['title'])) {
								unset($users[$userId]);
							}
						}
						
						array_unshift($users, array(
							'user_id' => -1,
							'username' => $userGroup['title'],
							'gravatar' => bdTagMe_Option::get('userGroupGravatar'),
						));
					}
				}
			}
		}
		
		return $response;
	}
	
}