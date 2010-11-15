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
require_once "../phplib/cobrand.php";
require_once "../commonlib/phplib/template.php";
require_once "../commonlib/phplib/admin-phpinfo.php";
require_once "../commonlib/phplib/admin-serverinfo.php";
require_once "../commonlib/phplib/admin-configinfo.php";
require_once "../commonlib/phplib/admin.php";

$pages = array(
    new ADMIN_PAGE_PET_MAIN,
    new ADMIN_PAGE_PET_SEARCH,
    new ADMIN_PAGE_PET_OFFLINE,
    new ADMIN_PAGE_PET_STATS,
    new ADMIN_PAGE_PET_MAP,
);

if (OPTION_SITE_NAME == 'sbdc' || OPTION_SITE_NAME == 'sbdc1') {
    page_header('Admin', array('admin'=>1));
    admin_page_display(str_replace("http://", "", OPTION_BASE_URL), $pages, new ADMIN_PAGE_PET_SUMMARY, array('headfoot'=>1));
    $num = preg_replace('#44(....)#', '0\1 ', OPTION_SMS_ALERT_NUMBER_TOM);
    echo '<p style="clear:both" align="right"><em>Got any questions? Call ' . $num . '.</em></p>';
    page_footer();
} else {
    admin_header(cobrand_admin_title());
    admin_page_display(OPTION_CONTACT_NAME, $pages, new ADMIN_PAGE_PET_SUMMARY, array('headfoot'=>1));
}

// Header at start of page
function admin_header($title) {
    $style = 'pet-admin-default-look.css';
    if ($s = cobrand_admin_style()) $style = $s;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?=$title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="pet-admin.css">
<link rel="stylesheet" type="text/css" href="<?=$style?>">
<script src="/jslib/openlayers/OpenLayers.js"></script>
</head>
<body id="admin">
<div id="header"></div>
<div id="content">
<?
}

