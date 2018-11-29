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

$rrddir       = $config['rrd_dir']."/".$device['hostname'];
$files        = array();

if ($handle = opendir($rrddir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != "..")
    {
      if (preg_match("/app-smart-".$app['app_id'].'/i', $file))
      {
        array_push($files, $file);
      }
    }
  }
}

asort($files);

foreach ($files as $id => $file)
{
  $drivename        = preg_replace('/app-smart-'.$app['app_id'].'-/i', '', $file);
  $drivename        = preg_replace('/.rrd/i', '', $drivename);

  $graphs            = array(
                             'smart_stats' => 'Smart Attributes - '.$drivename,
                            );

  foreach ($graphs as $key => $text)
  {
    $graph_type              = $key;
    $graph_array['height']   = "100";
    $graph_array['width']    = "215";
    $graph_array['to']       = $config['time']['now'];
    $graph_array['id']       = $app['app_id'];
    $graph_array['type']     = "application_".$key;
    $graph_array['drivename'] = $drivename;
    echo('<h3>'.$text.'</h3>');
    echo("<tr bgcolor='$row_colour'><td colspan=5>");

    print_graph_row($graph_array);

    echo("</td></tr>");
  }
}
