<?php

class bdTagMe_XenForo_ControllerPublic_Account extends XFCP_bdTagMe_XenForo_ControllerPublic_Account {
	
	public function actionAlertsTagged() {
		$contentType = $this->_input->filterSingle('content', XenForo_Input::STRING);
		// use `string` type to filter the content id for better compatibility support
		$contentId = $this->_input->filterSingle('id', XenForo_Input::STRING);
		
		$contentLink = $this->getModelFromCache('XenForo_Model_Alert')->bdTagMe_getContentLink($contentType, $contentId);
		
		if (!empty($contentLink)) {
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				$contentLink
			);
		} else {
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				XenForo_Link::buildPublicLink('account/alerts')
			);
		}
	}
	
	public function actionContactDetailsSave() {
		$GLOBALS['bdTagMe_XenForo_ControllerPublic_Account#actionContactDetailsSave'] = $this;
		
		return parent::actionContactDetailsSave();
	}
	
	public function bdTagMe_actionContactDetailsSave(XenForo_DataWriter_User $dw) {
		$settings = $this->_input->filter(array(
			'bdtagme_email' => XenForo_Input::UINT,
		));
		
		$dw->bulkSet($settings);
		
		unset($GLOBALS['bdTagMe_XenForo_ControllerPublic_Account#actionContactDetailsSave']);
	}
	
}