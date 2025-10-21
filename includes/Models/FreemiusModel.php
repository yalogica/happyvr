<?php

namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;
class FreemiusModel {
    public static function isLicensed() {
        global $happyvr_fs;
        return false;
    }

    public static function getUpgradeUrl() {
        return admin_url( 'admin.php?page=happyvr-pricing' );
    }

    public static function getAccountUrl() {
        global $happyvr_fs;
        return $happyvr_fs->get_account_url();
    }

    public static function isAnonymous() {
        global $happyvr_fs;
        return $happyvr_fs->is_anonymous();
    }

}
