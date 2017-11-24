<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 */

$rrdfile = get_rrd_path($device, "wifi-radio-". $radio['serial'] . '-' . $radio['radio_number'].".rrd");

$rrd_list[0]['filename'] = $rrdfile;
$rrd_list[0]['descr'] = "Refused connections";
$rrd_list[0]['ds'] = "RefusedConnectionCo";

$unit_text = "connections";

$units='';
$total_units='';
$colours='mixed';

$scale_min = "0";

$nototal = 1;

if ($rrd_list)
{
  include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");
}

?>
