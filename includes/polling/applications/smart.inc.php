<?php

if (!empty($agent_data['app']['smart']))
{
  $app_id = discover_app($device, 'smart');

  $drives = explode("\n", $agent_data['app']['smart']);
  rsort($drives);

  foreach ($drives as $item => $drive)
  {
    $data = array();

    if (!empty($drive))
    {
        foreach (explode(" ", $drive) as $str)
        {
            list($key, $value) = explode(":", $str);
            $data[$key] = trim($value);
        }

        $drive_name = $data['0'];
        unset($data['0']);

        $stats[$data['0']] = $data;

        rrdtool_update_ng($device, 'smart', $stats[$data['0']], "$app_id-".$drive_name);
    }
  }

  update_application($app_id, $stats);
}

// EOF