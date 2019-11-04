<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$scale_min = 0;

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-fail2ban-".$app['app_id']);

$array = array('sshd' => array('descr' => 'ssh', 'colour' => '750F7DFF'),
               'dropbear' => array('descr' => 'dropbear', 'colour' => '00FF00FF'),
               'nginx' => array('descr' => 'nginx', 'colour' => '4444FFFF'),
);

$i = 0;
if (is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $data['descr'];
    $rrd_list[$i]['ds'] = $ds;
    $rrd_list[$i]['colour'] = $data['colour'];
    $i++;
  }
} else { echo("file missing: $rrd_filename");  }

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Jails";

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
