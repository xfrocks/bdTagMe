<?php

class bdTagMe_Installer
{
    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected static $_tables = array();
    protected static $_patches = array(array(
        'table' => 'xf_user_option',
        'field' => 'bdtagme_email',
        'showTablesQuery' => 'SHOW TABLES LIKE \'xf_user_option\'',
        'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_user_option` LIKE \'bdtagme_email\'',
        'alterTableAddColumnQuery' => 'ALTER TABLE `xf_user_option` ADD COLUMN `bdtagme_email` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'',
        'alterTableDropColumnQuery' => 'ALTER TABLE `xf_user_option` DROP COLUMN `bdtagme_email`',
    ),);

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (empty($existed)) {
                $db->query($patch['alterTableAddColumnQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (!empty($existed)) {
                $db->query($patch['alterTableDropColumnQuery']);
            }
        }

        foreach (self::$_tables as $table) {
            $db->query($table['dropQuery']);
        }

        self::uninstallCustomized();
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    private static function installCustomized($existingAddOn, $addOnData)
    {
        if (XenForo_Application::$versionId < 1030000) {
            throw new XenForo_Exception('[bd] Tag Me v3 requires XenForo 1.3.0+.');
        }

        $db = XenForo_Application::getDb();
        $effectiveVersionId = 0;

        if (!empty($existingAddOn)) {
            $effectiveVersionId = $existingAddOn['version_id'];
        }

        if ($effectiveVersionId == 0) {
            $db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, 'general', 'bdtagme_groupTag', permission_value, 0
				FROM xf_permission_entry
				WHERE permission_group_id = 'general' AND permission_id = 'cleanSpam'
			");
        }

        if ($effectiveVersionId > 0 AND $effectiveVersionId < 3062) {
            $db->query("UPDATE IGNORE `xf_user_alert` SET action = 'tag' WHERE content_type = 'post' AND action = 'tagged'");
            $db->query("UPDATE IGNORE `xf_user_alert` SET action = 'tag' WHERE content_type = 'profile_post' AND action = 'tagged'");
            $db->query("UPDATE IGNORE `xf_user_alert` SET action = 'tag_comment' WHERE content_type = 'profile_post' AND action = 'comment_tagged'");

            $db->query("UPDATE IGNORE `xf_user_alert_optout` SET alert = 'post_tag' WHERE alert = 'post_tagged'");
            $db->query("UPDATE IGNORE `xf_user_alert_optout` SET alert = 'profile_post_tag' WHERE alert = 'profile_post_tagged'");
            $db->query("UPDATE IGNORE `xf_user_alert_optout` SET alert = 'profile_post_tag_comment' WHERE alert = 'profile_post_comment_tagged'");

            $db->query("DELETE FROM `xf_user_alert_optout` WHERE alert = 'post_tagged'");
            $db->query("DELETE FROM `xf_user_alert_optout` WHERE alert = 'profile_post_tagged'");
            $db->query("DELETE FROM `xf_user_alert_optout` WHERE alert = 'profile_post_comment_tagged'");
        }
    }

    private static function uninstallCustomized()
    {
        bdTagMe_ShippableHelper_Updater::onUninstall(bdTagMe_Option::UPDATER_URL);
    }

}
