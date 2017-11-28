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

$graph_title     = "Traffic Statistic";

$colour_line_in  = "006600";
$colour_line_out = "000099";
$colour_area_in  = "CDEB8B";
$colour_area_out = "C3D9FF";

// FIXME Not compatible this way with get_rrd_path; as long as no advanced storage is used this will work
// Call get_rrd_path below instead of using $rrddir.
$rrddir          = $config['rrd_dir']."/".$device['hostname'];
$files           = array();
$i               = 0;

if ($handle = opendir($rrddir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != "..")
    {
      if (preg_match("/app-hls-".$app['app_id']."/i", $file))
      {
        array_push($files, $file);
      }
    }
  }
}

rsort($files);

foreach ($files as $id => $file)
{
  $streamname               = preg_replace('/app-hls-'.$app['app_id'].'-/i', '', $file);
  $streamname               = preg_replace('/.rrd/i', '', $streamname);
  list($stream, $nolines)   = explode("-", $streamname, 2);
  $rrd_filenames[]          = $rrddir."/".$file;
  $rrd_list[$i]['filename'] = $rrddir."/".$file;
  $rrd_list[$i]['descr']    = $nolines;
  $rrd_list[$i]['ds_in']    = $ds_in;
  $rrd_list[$i]['ds_out']   = $ds_out;
  $i++;
}

include($config['html_dir']."/includes/graphs/common.inc.php");

#$graph_return['valid_options'][] = "previous";
#$graph_return['valid_options'][] = "total";
#$graph_return['valid_options'][] = "trend";
$graph_return['valid_options'][] = "line_graph";

$i = 0;
if ($width > "500")
{
  $descr_len=18;
} else {
  $descr_len=5;
  $descr_len += round(($width - 215) / 9.5);
}

$unit_text = "bps";

if (!$noheader)
{
  if ($width > "500")
  {
    $rrd_options .= " COMMENT:'".substr(str_pad($unit_text, $descr_len+5),0,$descr_len+5)."     Current    Average   Maximum      '";
    if (!$nototal) { $rrd_options .= " COMMENT:'Total      '"; }
    $rrd_options .= " COMMENT:'\l'";
  } else {
    $rrd_options .= " COMMENT:'".substr(str_pad($unit_text, $descr_len+5),0,$descr_len+5)."        Now       Avg       Max\l'";
  }
}

$rrd_multi = array();
$stack = '';
foreach ($rrd_list as $rrd)
{
  if (!$config['graph_colours'][$colours_in][$iter] || !$config['graph_colours'][$colours_out][$iter]) { $iter = 0; }

  if (strlen($rrd['colour_in']))  { $colour_in  = $rrd['colour_in'];  } else { $colour_in  = $config['graph_colours'][$colours_in][$iter]; }
  if (strlen($rrd['colour_out'])) { $colour_out = $rrd['colour_out']; } else { $colour_out = $config['graph_colours'][$colours_out][$iter]; }

  if (isset($rrd['descr_in']))
  {
    $descr     = rrdtool_escape($rrd['descr_in'], $descr_len) . "Video";
  } else {
    $descr     = rrdtool_escape($rrd['descr'], $descr_len) . "Video";
  }
  $descr_out = rrdtool_escape($rrd['descr_out'], $descr_len) . "Audio";

  $descr     = str_replace("'", "", $descr); // FIXME does this mean ' should be filtered in rrdtool_escape? probably...
  $descr_out = str_replace("'", "", $descr_out);

  // bitrate is provided in bit/s
  $rrd_options .= " DEF:".$in.$i."=".$rrd['filename'].":".$ds_in.":AVERAGE ";
  $rrd_options .= " DEF:".$out.$i."=".$rrd['filename'].":".$ds_out.":AVERAGE ";
  $rrd_options .= " CDEF:out".$i."_neg=out".$i.",-1,*";
  $rrd_options .= " CDEF:inB".$i."=in".$i.",$multiplier,* ";
  $rrd_options .= " CDEF:outB".$i."=out".$i.",$multiplier,*";
  $rrd_options .= " CDEF:outB".$i."_neg=outB".$i.",-1,*";
  $rrd_options .= " CDEF:octets".$i."=inB".$i.",outB".$i.",+";

  $rrd_multi['in_thing'][]  = $in.$i  . ",UN,0," . $in.$i  . ",IF";
  $rrd_multi['out_thing'][] = $out.$i . ",UN,0," . $out.$i . ",IF";

  $rrd_options .= " VDEF:totin".$i."=inB".$i.",TOTAL";
  $rrd_options .= " VDEF:totout".$i."=outB".$i.",TOTAL";
  $rrd_options .= " VDEF:tot".$i."=octets".$i.",TOTAL";

  if ($i) { $stack = ":STACK"; }

  if ($vars['line_graph'])
  {
    $rrd_options .= " LINE1.25:in".$i."#" . $colour_in . ":'" . $descr . "'";
  } else {
    $rrd_options .= " AREA:in".$i."#" . $colour_in . ":'" . $descr . "'$stack";
  }
  $rrd_options .= " GPRINT:in".$i.":LAST:%6.2lf%s$units";
  $rrd_options .= " GPRINT:in".$i.":AVERAGE:%6.2lf%s$units";
  $rrd_options .= " GPRINT:in".$i.":MAX:%6.2lf%s$units";

  if (!$nototal && $width > "500") { $rrd_options .= " GPRINT:totin".$i.":%6.2lf%s$total_units"; }

  $rrd_options .= " 'COMMENT:\\n'";

  if ($vars['line_graph'])
  {
    $rrd_options .= " 'LINE1.25:out".$i."_neg#" . $colour_out . ":" . $descr_out . "'";
  } else {
    $rrd_options  .= " 'HRULE:0#" . $colour_out.":".$descr_out."'";
    $rrd_optionsb .= " 'AREA:out".$i."_neg#" . $colour_out . ":$stack'";
  }
  $rrd_options  .= " GPRINT:out".$i.":LAST:%6.2lf%s$units";
  $rrd_options  .= " GPRINT:out".$i.":AVERAGE:%6.2lf%s$units";
  $rrd_options  .= " GPRINT:out".$i.":MAX:%6.2lf%s$units";

  if (!$nototal && $width > "500") { $rrd_options .= " GPRINT:totout".$i.":%6.2lf%s$total_units"; }
  $rrd_options .= " 'COMMENT:\\n'";

  $i++; $iter++;
}

$in_thing  = implode(',', $rrd_multi['in_thing']);
$out_thing = implode(',', $rrd_multi['out_thing']);
$pluses    = str_repeat(',+', count($rrd_multi['in_thing']) - 1);
$rrd_options .= " CDEF:".$in."bits=" . $in_thing . $pluses;
$rrd_options .= " CDEF:".$out."bits=" . $out_thing . $pluses;
$rrd_options .= " CDEF:doutbits=outbits,-1,*";
$rrd_options .= " CDEF:inoctets=inbits,$multiplier,*";
$rrd_options .= " CDEF:outoctets=outbits,$multiplier,*";
$rrd_options .= " VDEF:95thin=inbits,95,PERCENT";
$rrd_options .= " VDEF:95thout=outbits,95,PERCENT";
$rrd_options .= " CDEF:pout_tmp=doutbits,-1,* VDEF:dpout_tmp=pout_tmp,95,PERCENT CDEF:dpout_tmp2=doutbits,doutbits,-,dpout_tmp,-1,*,+ VDEF:d95thout=dpout_tmp2,FIRST";

$rrd_options .= " VDEF:totin=inoctets,TOTAL";
$rrd_options .= " VDEF:totout=outoctets,TOTAL";

$rrd_options .= " 'COMMENT: \\n'";
$rrd_options .= " 'COMMENT:Aggregate Totals\\n'";

$rrd_options .= " GPRINT:totin:'%6.2lf%s$total_units'";
$rrd_options .= " 'COMMENT:".str_pad("", $descr_len-8)."Video'";
$rrd_options .= " GPRINT:inbits:LAST:%6.2lf%s$units";
$rrd_options .= " GPRINT:inbits:AVERAGE:%6.2lf%s$units";
$rrd_options .= " GPRINT:inbits:MAX:%6.2lf%s$units\\n";
#  $rrd_options .= " GPRINT:95thin:%6.2lf%s\\n";

$rrd_options .= " GPRINT:totout:'%6.2lf%s$total_units'";
$rrd_options .= " 'COMMENT:".str_pad("", $descr_len-8)."Audio'";
$rrd_options .= " GPRINT:outbits:LAST:%6.2lf%s$units";
$rrd_options .= " GPRINT:outbits:AVERAGE:%6.2lf%s$units";
$rrd_options .= " GPRINT:outbits:MAX:%6.2lf%s$units\\n";

#  $rrd_options .= " GPRINT:95thout:%6.2lf%s\\n";

if ($custom_graph) { $rrd_options .= $custom_graph; }

$rrd_options .= $rrd_optionsb;
$rrd_options .= " HRULE:0#999999";

// Clean
unset($rrd_multi, $in_thing, $out_thing, $pluses, $stack);

// EOF
