<?php

class bdTagMe_bdAlerts_Model_Mail extends XFCP_bdTagMe_bdAlerts_Model_Mail
{
	public function getPossibleLinks(array $alert, $emailTitle, array $mailParams)
	{
		$links = parent::getPossibleLinks($alert, $emailTitle, $mailParams);

		if ($alert['content_type'] === 'post' AND $emailTitle === 'bdtagme_tagged' AND isset($mailParams['viewLink']))
		{
			$links[] = $mailParams['viewLink'];
		}

		return $links;
	}
}