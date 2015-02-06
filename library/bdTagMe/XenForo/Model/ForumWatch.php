<?php

class bdTagMe_XenForo_Model_ForumWatch extends XFCP_bdTagMe_XenForo_Model_ForumWatch
{
    protected $_bdTagMe_notifiedUserIds = array();

    public function sendNotificationToWatchUsersOnMessage(array $post, array $thread = null, array $noAlerts = array(), array $noEmail = array())
    {
        $response = parent::sendNotificationToWatchUsersOnMessage($post, $thread, $noAlerts, $noEmail);

        $notifiedUserIds = array(
            'alerted' => $noAlerts,
            'emailed' => $noEmail,
        );

        if (!empty($response['alerted'])) {
            $notifiedUserIds['alerted'] += $response['alerted'];
        }

        if (!empty($response['emailed'])) {
            $notifiedUserIds['emailed'] += $response['emailed'];
        }

        $this->_bdTagMe_notifiedUserIds[$post['post_id']] = $notifiedUserIds;

        return $response;
    }

    public function bdTagMe_getNotifiedUserIds(array $post)
    {
        if (isset($this->_bdTagMe_notifiedUserIds[$post['post_id']])) {
            return $this->_bdTagMe_notifiedUserIds[$post['post_id']];
        } else {
            return array(
                'alerted' => array(),
                'emailed' => array(),
            );
        }
    }

}
