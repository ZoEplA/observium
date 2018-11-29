<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrddir          = $config['rrd_dir']."/".$device['hostname'];
$files           = array();
$i               = 0;

if ($handle = opendir($rrddir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != "..")
    {
      if (preg_match("/app-smart-".$app['app_id']."/i", $file))
      {
        array_push($files, $file);
      }
    }
  }
}

rsort($files);

foreach ($files as $id => $file)
{
  $drivename  = preg_replace('/app-smart-'.$app['app_id'].'-/i', '', $file);
  $drivename  = preg_replace('/.rrd/i', '', $drivename);
}

$rrd_filename = get_rrd_path($device, 'app-smart-'.$app["app_id"].'-'.$drivename.'.rrd');

$array = array('5'  => array('descr' => 'Reallocated Sectors Count', 'colour' => '22FF22'),
               '10'  => array('descr' => 'Spin Retry Count', 'colour' => '0022FF'),
               '184' => array('descr' => 'End-to-End error', 'colour' => 'FF0000'),
               '187'  => array('descr' => 'Reported Uncorrectable Errors', 'colour' => '00AAAA'),
               '188'  => array('descr' => 'Command Timeout', 'colour' => 'FF00FF'),
               '194' => array('descr' => 'Temperature Celsius', 'colour' => 'FFA500'),
               '196' => array('descr' => 'Reallocation Event Count', 'colour' => 'CC0000'),
               '197'  => array('descr' => 'Current Pending Sector Count', 'colour' => '0000CC'),
               '198'  => array('descr' => 'Uncorrectable Sector Count', 'colour' => '0080C0'),
               '201' => array('descr' => 'Soft Read Error Rate', 'colour' => '00C000'),
);

$i = 0;
if (is_file($rrd_filename))
{
  foreach ($array as $ds => $data)
  {
    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr'] = $data['descr'];
    $rrd_list[$i]['ds'] = $ds;
#    $rrd_list[$i]['colour'] = $data['colour'];
    $i++;
  }
} else { echo("file missing: $file");  }

$colours   = "mixed-10";
$nototal   = 1;
$unit_text = "Attribute";

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
