<?php

$rrd_filename = get_rrd_path($device, "app-rrdcached-".$app['app_id']);

$rrd_list = array(
    array(
        'ds' => 'JournalRotate',
        'filename' => $rrd_filename,
        'descr' => 'Journal Rotated',
    ),
    array(
        'ds' => 'JournalBytes',
        'filename' => $rrd_filename,
        'descr' => 'Journal Bytes Written',
    )
);

$colours = 'pinks';

$scale_min = "0";
$nototal = 1;

include($config['html_dir']."/includes/graphs/generic_multi.inc.php");

?>