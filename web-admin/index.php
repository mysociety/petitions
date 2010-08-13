<?
/*
 * index.php:
 * Admin pages for ePetitions.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.5 2010-03-12 19:11:17 matthew Exp $
 * 
 */

require_once "../conf/general";
require_once "../phplib/admin-pet.php";
require_once "../commonlib/phplib/template.php";
require_once "../commonlib/phplib/admin-phpinfo.php";
require_once "../commonlib/phplib/admin-serverinfo.php";
require_once "../commonlib/phplib/admin-configinfo.php";
require_once "../commonlib/phplib/admin.php";

$pages = array(
    new ADMIN_PAGE_PET_MAIN,
    new ADMIN_PAGE_PET_SEARCH,
    new ADMIN_PAGE_PET_STATS,
);
if (!OPTION_ADMIN_PUBLIC) {
    array_push($pages,
    null,
    new ADMIN_PAGE_SERVERINFO,
    new ADMIN_PAGE_CONFIGINFO,
    new ADMIN_PAGE_PHPINFO
    );
}

if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
    ob_start(); # As footer wants to output total length
    page_header('Admin', array('admin'=>1));
    admin_page_display(str_replace("http://", "", OPTION_BASE_URL), $pages, new ADMIN_PAGE_PET_SUMMARY, array('headfoot'=>1));
    ob_end_flush(); # There's an ob_start in admin.php
    $num = preg_replace('#44(....)#', '0\1 ', OPTION_SMS_ALERT_NUMBER_TOM);
    echo '<p align="right"><em>Got any questions? Call ' . $num . '.</em></p>';
    page_footer();
} else {
    admin_page_display(str_replace("http://", "", OPTION_BASE_URL), $pages, new ADMIN_PAGE_PET_SUMMARY);
}

