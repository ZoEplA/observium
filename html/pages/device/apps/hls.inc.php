<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage applications
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 */

## FIXME -- THIS IS MESSY AND NEEDS TO BE FIXED.

global $config;

$total        = true;

$rrddir       = $config['rrd_dir']."/".$device['hostname'];
$files        = array();

if ($handle = opendir($rrddir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != "..")
    {
      if (preg_match("/app-hls-".$app['app_id'].'/i', $file))
      {
        array_push($files, $file);
      }
    }
  }
}

rsort($files);

if (isset($total) && $total == true)
{
  $graphs = array(
                  'hls_multi_video'  => 'Traffic Statistics - All Streams',
                 );

  foreach ($graphs as $key => $text)
  {
    $graph_type             = $key;
    $graph_array['to']      = $config['time']['now'];
    $graph_array['id']      = $app['app_id'];
    $graph_array['type']    = "application_".$key;
    echo('<h3>'.$text.'</h3>');
    echo("<tr bgcolor='$row_colour'><td colspan=5>");

    print_graph_row($graph_array);

    echo("</td></tr>");
  }
}

foreach ($files as $id => $file)
{
  $streamname        = preg_replace('/app-hls-'.$app['app_id'].'-/i', '', $file);
  $streamname        = preg_replace('/.rrd/i', '', $streamname);

  $graphs            = array(
                             'hls_video' => 'Traffic Statistics - '.$streamname,
                            );

  foreach ($graphs as $key => $text)
  {
    $graph_type              = $key;
    $graph_array['height']   = "100";
    $graph_array['width']    = "215";
    $graph_array['to']       = $config['time']['now'];
    $graph_array['id']       = $app['app_id'];
    $graph_array['type']     = "application_".$key;
    $graph_array['streamname'] = $streamname;
    echo('<h3>'.$text.'</h3>');
    echo("<tr bgcolor='$row_colour'><td colspan=5>");

    print_graph_row($graph_array);

    echo("</td></tr>");
  }
}

// EOF
