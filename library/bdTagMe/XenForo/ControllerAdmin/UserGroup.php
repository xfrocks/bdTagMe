<?php

class bdTagMe_XenForo_ControllerAdmin_UserGroup extends XFCP_bdTagMe_XenForo_ControllerAdmin_UserGroup {
	
	protected function _getUserGroupAddEditResponse(array $userGroup) {
		$response = parent::_getUserGroupAddEditResponse($userGroup);
		
		if ($response instanceof XenForo_ControllerResponse_View) {
			$engine = bdTagMe_Engine::getInstance();
			$taggableUserGroups = $engine->getTaggableUserGroups();
			$isTaggable = false;
			$usersCount = 0;
			
			foreach ($taggableUserGroups as $taggableUserGroup) {
				if ($taggableUserGroup['user_group_id'] == $userGroup['user_group_id']) {
					$isTaggable = true;
					if (isset($taggableUserGroup['userIds'])) {
						$usersCount = count($taggableUserGroup['userIds']);
					}
					break;
				}
			}
			
			$response->params['bdTagMe_isTaggable'] = $isTaggable;
			$response->params['bdTagMe_usersCount'] = $usersCount;
		}
		
		return $response;
	}
	
	public function actionSave() {
		$GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave'] = $this;
		
		return parent::actionSave();
	}
	
	public function bdTagMe_actionSave(XenForo_DataWriter_UserGroup $dw) {
		$isTaggable = $this->_input->filterSingle('bdTagMe_isTaggable', XenForo_Input::BINARY);
		$userGroup = $dw->getMergedData();
		
		$engine = bdTagMe_Engine::getInstance();
		$engine->setTaggableUserGroup($userGroup, $isTaggable, $dw);
		
		unset($GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave']);
	}
	
}