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

$scale_min = '0';

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "ucd_cpu.rrd");
$rrd_options .= " DEF:user=$rrd_filename:user:AVERAGE";
$rrd_options .= " DEF:nice=$rrd_filename:nice:AVERAGE";
$rrd_options .= " DEF:system=$rrd_filename:system:AVERAGE";
$rrd_options .= " DEF:idle=$rrd_filename:idle:AVERAGE";
$rrd_options .= " DEF:steal=$rrd_filename:steal:AVERAGE";
$rrd_options .= " CDEF:total=user,nice,system,steal,idle,+,+,+,+";
$rrd_options .= " CDEF:user_perc=user,total,/,100,*";
$rrd_options .= " CDEF:nice_perc=nice,total,/,100,*";
$rrd_options .= " CDEF:system_perc=system,total,/,100,*";
$rrd_options .= " CDEF:idle_perc=idle,total,/,100,*";
$rrd_options .= " CDEF:steal_perc=steal,total,/,100,*";
$rrd_options .= " COMMENT:'Usage       Current     Average    Maximum\\n'";
$rrd_options .= " AREA:user_perc#c02020:user";
$rrd_options .= " GPRINT:user_perc:LAST:'     %5.2lf%%'";
$rrd_options .= " GPRINT:user_perc:AVERAGE:'   %5.2lf%%'";
$rrd_options .= " GPRINT:user_perc:MAX:'   %5.2lf%%\\n'";
$rrd_options .= " AREA:nice_perc#008f00:nice:STACK";
$rrd_options .= " GPRINT:nice_perc:LAST:'     %5.2lf%%'";
$rrd_options .= " GPRINT:nice_perc:AVERAGE:'   %5.2lf%%'";
$rrd_options .= " GPRINT:nice_perc:MAX:'   %5.2lf%%\\n'";
$rrd_options .= " AREA:system_perc#ea8f00:system:STACK";
$rrd_options .= " GPRINT:system_perc:LAST:'   %5.2lf%%'";
$rrd_options .= " GPRINT:system_perc:AVERAGE:'   %5.2lf%%'";
$rrd_options .= " GPRINT:system_perc:MAX:'   %5.2lf%%\\n'";
$rrd_options .= " AREA:steal_perc#000000:steal:STACK";
$rrd_options .= " GPRINT:steal_perc:LAST:'    %5.2lf%%'";
$rrd_options .= " GPRINT:steal_perc:AVERAGE:'   %5.2lf%%'";
$rrd_options .= " GPRINT:steal_perc:MAX:'   %5.2lf%%\\n'";
$rrd_options .= " AREA:idle_perc#f5f5e5:idle:STACK";
$rrd_options .= " GPRINT:idle_perc:LAST:'     %5.2lf%%'";
$rrd_options .= " GPRINT:idle_perc:AVERAGE:'   %5.2lf%%'";
$rrd_options .= " GPRINT:idle_perc:MAX:'   %5.2lf%%\\n'";

// EOF
