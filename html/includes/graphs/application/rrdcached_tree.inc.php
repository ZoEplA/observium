<?php

$rrd_filename = get_rrd_path($device, "app-rrdcached-".$app['app_id']);

$rrd_list = array(
    array(
        'ds' => 'TreeDepth',
        'filename' => $rrd_filename,
        'descr' => 'Tree Depth',
    ),
    array(
        'ds' => 'TreeNodesNumber',
        'filename' => $rrd_filename,
        'descr' => 'Tree Nodes',
    )
);

$colours = 'blues';

include($config['html_dir']."/includes/graphs/generic_multi.inc.php");

?>