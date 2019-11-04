<?php

$scale_min = 0;

include($config['html_dir']."/includes/graphs/common.inc.php");

$colour_area = 'F37900';
$colour_line = 'FFA700';
$colour_area_max = 'F78800';

$rrd_filename = get_rrd_path($device, "app-rrdcached-".$app['app_id']);

$ds = "QueueLength";

$unit_text = 'Queue Length';

include($config['html_dir']."/includes/graphs/generic_simplex.inc.php");

?>