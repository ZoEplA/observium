<?php

if(!empty($agent_data['app']['rrdcached']))
{
  $app_id = discover_app($device, 'rrdcached');

  $fields = explode("\n", $agent_data['app']['rrdcached']);

  array_shift($fields);

  $data = array();

  foreach ($fields as $item => $field) {
    $line = explode(': ', $field);

    $data[$line[0]] = $line[1];
  }

  rrdtool_update_ng($device, 'rrdcached', $data, $app_id);

  update_application($app_id, $data);
}

// EOF