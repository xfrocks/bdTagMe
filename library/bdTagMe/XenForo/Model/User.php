<?php

class bdTagMe_XenForo_Model_User extends XFCP_bdTagMe_XenForo_Model_User
{
    protected static $_bdTagMe_orderByMemberActivity = false;

    public function bdTagMe_setOrderByMemberActivity($enabled)
    {
        self::$_bdTagMe_orderByMemberActivity = $enabled;
    }

    public function bdTagMe_getUserIdsByUserGroupId($userGroupId)
    {
        return $this->_getDb()->fetchPairs('
			SELECT user_id, username
			FROM xf_user
			WHERE user_group_id = ? OR FIND_IN_SET(?, secondary_group_ids)
		', array(
            $userGroupId,
            $userGroupId
        ));
    }

    public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
    {
        if (self::$_bdTagMe_orderByMemberActivity) {
            return 'ORDER BY user.message_count DESC, user.last_activity DESC, user.username ASC';
        } else {
            return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
        }
    }

}
