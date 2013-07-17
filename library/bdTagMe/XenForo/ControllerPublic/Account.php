<?php

class bdTagMe_XenForo_ControllerPublic_Account extends XFCP_bdTagMe_XenForo_ControllerPublic_Account
{

	public function actionContactDetailsSave()
	{
		$GLOBALS['bdTagMe_XenForo_ControllerPublic_Account#actionContactDetailsSave'] = $this;

		return parent::actionContactDetailsSave();
	}

	public function bdTagMe_actionContactDetailsSave(XenForo_DataWriter_User $dw)
	{
		if (bdTagMe_Option::get('alertEmail'))
		{
			$settings = $this->_input->filter(array('bdtagme_email' => XenForo_Input::UINT, ));

			$dw->bulkSet($settings);
		}

		unset($GLOBALS['bdTagMe_XenForo_ControllerPublic_Account#actionContactDetailsSave']);
	}

}
