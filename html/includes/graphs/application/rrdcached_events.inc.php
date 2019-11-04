<?php

$rrd_filename = get_rrd_path($device, "app-rrdcached-".$app['app_id']);

$rrd_list = array(
    array (
        'ds' => 'UpdatesWritten',
        'filename' => $rrd_filename,
        'descr' => 'Updates Written',
    ),
    array (
        'ds' => 'DataSetsWritten',
        'filename' => $rrd_filename,
        'descr' => 'Data Sets Written',
    ),
    array(
        'ds' => 'UpdatesReceived',
        'filename' => $rrd_filename,
        'descr' => 'Updates Received',
    ),
    array (
        'ds' => 'FlushesReceived',
        'filename' => $rrd_filename,
        'descr' => 'Flushes Received',
    ),
);

$colours = 'mixed';
$nototal = 1;
$unit_text = 'Events';

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

?>