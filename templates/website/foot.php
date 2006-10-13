<?php

$site_stats = '';
if (!OPTION_PET_STAGING) {
    $site_stats = file_get_contents('../templates/website/site-stats.html');
    $site_stats = str_replace("PARAM_STAT_CODE", $stat_code, $site_stats);
}

$contents = file_get_contents("../templates/website/foot.html");
$contents = str_replace("PARAM_SITE_STATS", $site_stats, $contents);
print $contents;

?>
