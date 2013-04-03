<?php

class bdTagMe_XenForo_DataWriter_User extends XFCP_bdTagMe_XenForo_DataWriter_User {
	
	protected function _postSave() {
		$changedUserGroupIds = array();
		
		if ($this->isChanged('user_group_id')) {
			if ($this->isUpdate()) {
				$changedUserGroupIds[] = $this->getExisting('user_group_id');
			}
			$changedUserGroupIds[] = $this->get('user_group_id');
		}
		
		if ($this->isChanged('secondary_group_ids')) {
			if ($this->isUpdate()) {
				$oldIds = explode(',', $this->getExisting('secondary_group_ids'));
				$newIds = explode(',', $this->get('secondary_group_ids'));
				
				foreach ($newIds as $id) {
					if (!empty($id) AND !in_array($id, $oldIds)) {
						$changedUserGroupIds[] = $id;
					}
				}
				
				foreach ($oldIds as $id) {
					if (!empty($id) AND !in_array($id, $newIds)) {
						$changedUserGroupIds[] = $id;
					}
				}
			} else {
				$ids = explode(',', $this->get('secondary_group_ids'));
				foreach ($ids as $id) {
					if (!empty($id)) {
						$changedUserGroupIds[] = $id;
					}
				}
			}
		}
		
		if (!empty($changedUserGroupIds)) {
			$changedUserGroupIds = array_unique($changedUserGroupIds);
			$engine = bdTagMe_Engine::getInstance();
			$engine->updateTaggableUserGroups($changedUserGroupIds, $this);
		}
		
		return parent::_postSave();
	}
	
}