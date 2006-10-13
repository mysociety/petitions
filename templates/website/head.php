<?php

$stat_js = '';
if (!OPTION_PET_STAGING) {
    $stat_js = '<script type="text/javascript" src="http://www.number10.gov.uk/include/js/nedstat.js"></script>';
}

global $devwarning;
$contents = file_get_contents("../templates/website/head.html");
$contents = str_replace("PARAM_DC_IDENTIFIER", $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $contents);
$contents = str_replace("PARAM_TITLE", $title, $contents);
$contents = str_replace("PARAM_DEV_WARNING", $devwarning, $contents);
$contents = str_replace("PARAM_STAT_JS", $stat_js, $contents);
print $contents;

?>
