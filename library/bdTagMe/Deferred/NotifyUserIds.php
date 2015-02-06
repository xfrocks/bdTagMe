<?php

class bdTagMe_Deferred_NotifyUserIds extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        list(
            $contentType, $contentId, $contentUserId, $contentUserName, $alertAction,
            $userIds,
            $noAlertUserIds, $noEmailUserIds,
            $options) = $data;

        /** @var XenForo_Model_User $userModel */
        $userModel = XenForo_Model::create('XenForo_Model_User');
        $engine = bdTagMe_Engine::getInstance();

        $deferredUserIds = array();
        if (count($userIds) > bdTagMe_Engine::USERS_PER_BATCH) {
            $deferredUserIds = array_slice($userIds, bdTagMe_Engine::USERS_PER_BATCH);
            $userIds = array_slice($userIds, 0, bdTagMe_Engine::USERS_PER_BATCH);
        }

        $engine->notifyUserIds(
            $contentType, $contentId, $contentUserId, $contentUserName, $alertAction,
            $userIds,
            $noAlertUserIds, $noEmailUserIds,
            $userModel, $options
        );

        if (!empty($deferredUserIds)) {
            $data[5] = $deferredUserIds;
            return $data;
        } else {
            return false;
        }
    }

}
