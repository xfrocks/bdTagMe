<?php

class bdTagMe_UserCallback
{
    public static function XenForo_DataWriter_DiscussionMessage_Post(XenForo_Model_User $userModel, array $user, array $options)
    {
        if (empty($user['node_permission_cache'])) {
            return false;
        }

        if (empty($options['post']) OR empty($options['thread']) OR empty($options['forum'])) {
            return false;
        }

        $permissions = XenForo_Permission::unserializePermissions($user['node_permission_cache']);

        /** @var XenForo_Model_Post $postModel */
        $postModel = $userModel->getModelFromCache('XenForo_Model_Post');

        return $postModel->canViewPostAndContainer($options['post'], $options['thread'], $options['forum'], $null, $permissions, $user);
    }
}