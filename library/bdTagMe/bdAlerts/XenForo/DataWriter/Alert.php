<?php

class bdTagMe_bdAlerts_XenForo_DataWriter_Alert extends XFCP_bdTagMe_bdAlerts_XenForo_DataWriter_Alert
{
	public function bdAlerts_getSavedAlerts()
	{
		$alerts = parent::bdAlerts_getSavedAlerts();
		
		$alerts += $this->getModelFromCache('XenForo_Model_Alert')->bdTagMe_getSavedAlerts();
		
		return $alerts;
	}
}