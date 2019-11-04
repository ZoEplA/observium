<?php

$rrd_filename = get_rrd_path($device, "app-phpfpm-".$app['app_id']);

$rrd_list = array(
    array (
        'ds' => 'lq',
        'filename' => $rrd_filename,
        'descr' => 'Listen Queue',
        'colour' => '582A72',
    ),
    array (
        'ds' => 'mlq',
        'filename' => $rrd_filename,
        'descr' => 'Max Listen Queue',
        'colour' => '28774F',
    ),
    array(
        'ds' => 'ip',
        'filename' => $rrd_filename,
        'descr' => 'Idle Processes',
        'colour' => '88CC88',
    ),
    array (
        'ds' => 'ap',
        'filename' => $rrd_filename,
        'descr' => 'Active Processes',
        'colour' => 'D46A6A',
    ),
    array (
        'ds' => 'tp',
        'filename' => $rrd_filename,
        'descr' => 'Total Processes',
        'colour' => 'FFD1AA',
    ),
    array (
        'ds' => 'map',
        'filename' => $rrd_filename,
        'descr' => 'Max Active Processes',
        'colour' => '582A72',
    ),
    array(
        'ds' => 'mcr',
        'filename' => $rrd_filename,
        'descr' => 'Max Children Reached',
        'colour' => 'AA5439',
    ),
    array (
        'ds' => 'sr',
        'filename' => $rrd_filename,
        'descr' => 'Slow Requests',
        'colour' => '28536C',
    ),

);

$colours = 'mixed';
$nototal = 1;
$scale_min = 0;
$unit_text='';

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

?>