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

$units           = "b";
$total_units     = "B";
$colours_in      = "greens";
$colours_out     = "blues";
$multiplier      = "0.125";
$nototal         = 1;

$ds_in           = "bitrate_video";
$ds_out          = "bitrate_audio";

$graph_title    .= "::bits";

$colour_line_in  = "006600";
$colour_line_out = "000099";
$colour_area_in  = "CDEB8B";
$colour_area_out = "C3D9FF";

$streamname      = (isset($vars['streamname']) ? $vars['streamname'] : "unknown");
$rrd_filename    = get_rrd_path($device, "app-hls-".$app['app_id']."-".$streamname.".rrd");

include($config['html_dir']."/includes/graphs/common.inc.php");

$units = "bps";
$format = "bits";
$unit_text = "Bits/s";

$i = 0;
$unit_text = rrdtool_escape($unit_text, 9);

if (!$noheader)
{
  $rrd_options .= " COMMENT:'$unit_text  Last     Avg      Max     95th \\n'";
}

// Alternative style
if ($graph_style == 'mrtg')
{
  $out_scale = 1;
} else {
  $out_scale = -1;
}

if ($rrd_filename) { $rrd_filename_out = $rrd_filename; $rrd_filename_in = $rrd_filename; }
if ($inverse) { $in = 'out'; $out = 'in'; } else { $in = 'in'; $out = 'out'; }

$rrd_options .= " DEF:".$out."bits=".$rrd_filename_out.":".$ds_out.":AVERAGE";
$rrd_options .= " DEF:".$in."bits=".$rrd_filename_in.":".$ds_in.":AVERAGE";
$rrd_options .= " DEF:".$out."bits_max=".$rrd_filename_out.":".$ds_out.":MAX";
$rrd_options .= " DEF:".$in."bits_max=".$rrd_filename_in.":".$ds_in.":MAX";

$rrd_options .= " CDEF:inoctets=inbits,$multiplier,*";
$rrd_options .= " CDEF:outoctets=outbits,$multiplier,*";
$rrd_options .= " CDEF:inoctets_max=inbits_max,$multiplier,*";
$rrd_options .= " CDEF:outoctets_max=outbits_max,$multiplier,*";

// Unknown data
$rrd_options .= " CDEF:alloctets=".$out."octets,".$in."octets,+";
$rrd_options .= " CDEF:wrongin=alloctets,UN,INF,UNKN,IF";
$rrd_options .= " CDEF:wrongout=wrongin,".$out_scale.",*";

if ($vars['previous'] == "yes")
{
  $rrd_options .= " DEF:".$out."bitsX=".$rrd_filename_out.":".$ds_out.":AVERAGE:start=".$prev_from.":end=".$from;
  $rrd_options .= " DEF:".$in."bitsX=".$rrd_filename_in.":".$ds_in.":AVERAGE:start=".$prev_from.":end=".$from;
  $rrd_options .= " SHIFT:".$out."bitsX:$period";
  $rrd_options .= " SHIFT:".$in."bitsX:$period";
  $rrd_options .= " CDEF:inoctetsX=inbitsX,$multiplier,*";
  $rrd_options .= " CDEF:outoctetsX=outbitsX,$multiplier,*";

  $rrd_options .= " CDEF:octetsX=inoctetsX,outoctetsX,+";
  $rrd_options .= " CDEF:doutoctetsX=outoctetsX,".$out_scale.",*";
  $rrd_options .= " CDEF:doutbitsX=outbitsX,".$out_scale.",*";

  $rrd_options .= " VDEF:totinX=inoctetsX,TOTAL";
  $rrd_options .= " VDEF:totoutX=outoctetsX,TOTAL";
  $rrd_options .= " VDEF:totX=octetsX,TOTAL";
}

$rrd_options .= " CDEF:octets=inoctets,outoctets,+";
$rrd_options .= " CDEF:doutoctets=outoctets,".$out_scale.",*";
$rrd_options .= " CDEF:doutoctets_max=outoctets_max,".$out_scale.",*";

$rrd_options .= " CDEF:doutbits=outbits,".$out_scale.",*";
$rrd_options .= " CDEF:doutbits_max=outbits_max,".$out_scale.",*";

if ($config['rrdgraph_real_95th'])
{
  $rrd_options .= " CDEF:highbits=inbits,outbits,MAX";
  $rrd_options .= " VDEF:95thhigh=highbits,95,PERCENT";
}

$rrd_options .= " VDEF:totin=inoctets,TOTAL";
$rrd_options .= " VDEF:totout=outoctets,TOTAL";
$rrd_options .= " VDEF:tot=octets,TOTAL";

$rrd_options .= " VDEF:95thin=inbits,95,PERCENT";
$rrd_options .= " VDEF:95thout=outbits,95,PERCENT";
$rrd_options .= " CDEF:pout_tmp=doutbits,".$out_scale.",* VDEF:dpout_tmp=pout_tmp,95,PERCENT CDEF:dpout_tmp2=doutbits,doutbits,-,dpout_tmp,".$out_scale.",*,+ VDEF:d95thout=dpout_tmp2,FIRST";

$units = "bits/sec";
$format = "bits";

if ($graph_max)
{
  $rrd_options .= " AREA:in".$format."_max#B6D14B:";
}
$rrd_options .= " AREA:in".$format."#92B73F";
if ($graph_style != 'mrtg')
{
  $rrd_options .= " LINE1.25:in".$format."#4A8328";
}
$rrd_options .= ":'Video'";
$rrd_options .= " GPRINT:in".$format.":LAST:%6.2lf%s";
$rrd_options .= " GPRINT:in".$format.":AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:in".$format."_max:MAX:%6.2lf%s";
$rrd_options .= " GPRINT:95thin:%6.2lf%s\\n";

if ($graph_max)
{
  if ($graph_style == 'mrtg')
  {
    $rrd_options .= " LINE1:dout";
  } else {
    $rrd_options .= " AREA:dout";
  }
  $rrd_options .= $format."_max#A0A0E5:";
}
if ($graph_style != 'mrtg')
{
  $rrd_options .= " AREA:dout".$format."#7075B8";
}
$rrd_options .= " LINE1.25:dout".$format."#323B7C:'Audio'";
$rrd_options .= " GPRINT:out".$format.":LAST:%6.2lf%s";
$rrd_options .= " GPRINT:out".$format.":AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:out".$format."_max:MAX:%6.2lf%s";
$rrd_options .= " GPRINT:95thout:%6.2lf%s\\n";

if ($config['rrdgraph_real_95th'])
{
  $rrd_options .= " HRULE:95thhigh#FF0000:'Highest'";
  $rrd_options .= " GPRINT:95thhigh:%30.2lf%s\\n";
} else {
  $rrd_options .= " LINE1:95thin#aa0000";
  $rrd_options .= " LINE1:d95thout#bb0000";
}

$rrd_options .= " GPRINT:tot:'Total %6.2lf%s'";
$rrd_options .= " GPRINT:totin:'(Video %6.2lf%s'";
$rrd_options .= " GPRINT:totout:'Audio %6.2lf%s)\\l'";

if ($vars['previous'] == "yes")
{
  $rrd_options .= " LINE1.25:in".$format."X#009900:'Prev Video\\n'";
  $rrd_options .= " LINE1.25:dout".$format."X#000099:'Prev Audio'";
} else {
  $rrd_options .= " AREA:wrongin#FFF2F2";
  $rrd_options .= " AREA:wrongout#FFF2F2";
}

if ($vars['trend'])
{
  $rrd_options .= " VDEF:slope_in=in".$format.",LSLSLOPE ";
  $rrd_options .= " VDEF:cons_in=in".$format.",LSLINT ";
  $rrd_options .= " CDEF:lsl2_in=in".$format.",POP,slope_in,COUNT,*,cons_in,+ ";
  $rrd_options .= ' LINE1.25:lsl2_in#ff0000::dashes=2';

  $rrd_options .= " VDEF:slope_out=out".$format.",LSLSLOPE ";
  $rrd_options .= " VDEF:cons_out=out".$format.",LSLINT ";
  $rrd_options .= " CDEF:lsl2_out=out".$format.",POP,slope_out,COUNT,*,cons_out,+ ";
  $rrd_options .= " CDEF:lsl2_out_rev=lsl2_out,-1,* ";
  $rrd_options .= ' LINE1.25:lsl2_out_rev#ff0000::dashes=2';
}

//if ($graph_style == 'mrtg')
//{
//  $midnight = strtotime('today midnight');
//  for ($i = 1; $i <= 2; $i++)
//  {
//    $rrd_options .= " VRULE:${midnight}#FF0000";
//    $midnight -= 86400;
//  }
//}

// EOF
