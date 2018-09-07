<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 */

// FIXME - INSTANCES

if (!empty($agent_data['app']['hls']))
{
  $app_id = discover_app($device, 'hls');

  // Polls hls statistics from agent script
  $streams = explode("\n", $agent_data['app']['hls']);
  rsort($streams);
  $data = array();

  foreach ($streams as $item => $stream)
  {
    $stream = trim($stream);

    if (!empty($stream))
    {
      $data              = explode(" ", $stream, 3);

      $stats[$data[0]] = array(
        'bitrate_video'  => $data[1],
        'bitrate_audio'  => $data[2],
      );

      rrdtool_update_ng($device, 'hls', $stats[$data[0]], "$app_id-".$data[0]);

    }
  }

  update_application($app_id, $stats);

  unset($app_id, $host, $port, $data, $streams, $stream, $item);
}

// EOF
