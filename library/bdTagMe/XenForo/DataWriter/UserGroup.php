<?php

class bdTagMe_XenForo_DataWriter_UserGroup extends XFCP_bdTagMe_XenForo_DataWriter_UserGroup {
	
	protected function _postSave() {
		if (isset($GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave'])) {
			$GLOBALS['bdTagMe_XenForo_ControllerAdmin_UserGroup#actionSave']->bdTagMe_actionSave($this);
		}
		
		return parent::_postSave();
	}
	
	protected function _postDelete() {
		$engine = bdTagMe_Engine::getInstance();
		$engine->setTaggableUserGroup($this->getMergedData(), false, $this); // unregister itself
		
		return parent::_postDelete();
	}
	
}