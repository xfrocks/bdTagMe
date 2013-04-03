<?php

class bdTagMe_XenForo_ControllerPublic_Member extends XFCP_bdTagMe_XenForo_ControllerPublic_Member {
	
	public function actionTagSuggestions() {
		$response = parent::actionFind();
		
		if ($response instanceof XenForo_ControllerResponse_View) {
			$users =& $response->params['users'];
			$q = utf8_strtolower($this->_input->filterSingle('q', XenForo_Input::STRING));
			$qLen = utf8_strlen($q);
			
			if ($qLen > 0) {
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