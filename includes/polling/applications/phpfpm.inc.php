<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (!empty($agent_data['app']['phpfpm']))
{
  $phpfpm = $agent_data['app']['phpfpm'];

  $app_id = discover_app($device, 'phpfpm');

  list($pool,$start_time,$start_since,$accepted_conn,$listen_queue,$max_listen_queue,$listen_queue_len,$idle_processes,
     $active_processes,$total_processes,$max_active_processes,$max_children_reached,$slow_requests) = explode("\n", $phpfpm);

  $data = array(
    'lq'  => $listen_queue,
    'mlq' => $max_listen_queue,
    'ip'  => $idle_processes,
    'ap'  => $active_processes,
    'tp'  => $total_processes,
    'map' => $max_active_processes,
    'mcr' => $max_children_reached,
    'sr'  => $slow_requests,
  );

  rrdtool_update_ng($device, 'phpfpm', $data, $app_id);

  update_application($app_id, $data);
}

// EOF
