<?php

class bdTagMe_XenForo_ViewPublic_Member_Post extends XFCP_bdTagMe_XenForo_ViewPublic_Member_Post {
	public function renderJson() {
		$json = parent::renderJson();
		
		$array = json_decode($json, true);
		if (isset($array['statusHtml'])) {
			// this is ugly and it's just a temporary fix until this bug is fixed
			// http://xenforo.com/community/threads/xen-helper-bodytext-is-not-called-properly-for-status-message.28533/
			$array['statusHtml'] =
				XenForo_Template_Helper_Core::callHelper('bodytext', array($this->_params['profilePost']['message'])) . ' '
				. XenForo_Template_Helper_Core::helperDateTimeHtml($this->_params['profilePost']['post_date']);
			$json = XenForo_ViewRenderer_Json::jsonEncodeForOutput($array);
		}
		
		return $json;
	}
}