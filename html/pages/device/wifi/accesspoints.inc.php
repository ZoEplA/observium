<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

if ($device['type'] == 'wireless')
{

  $accesspoints = dbFetchRows('SELECT * FROM `wifi_aps` WHERE `device_id` = ? ORDER BY `ap_name` ASC', array($device['device_id']));
  $accesspoints_count = count($accesspoints);

  echo generate_box_open();
  echo '<table class="table table-hover table-condensed table-striped">';
  echo '<thead><tr><th class="state-marker"></th><th>Name</th><th>Identifier</th><th>Model</th><th>Location</th><th>Serial/Fingerprint</th></tr></thead>';

  foreach ($accesspoints as $accesspoint)
  {

    if ($accesspoint['ap_status'] == "up")
    {
      $row_class = "up";
    } elseif ($accesspoint['ap_status'] == "down") {
      $row_class = "error";
    } else {
      $row_class = "ignore";
    }

    echo '<tr class="' . $row_class . '">';
    echo '<td class="state-marker"></td>';
    echo '<td class="entity"><a href="'. generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'wifi', 'view' => 'accesspoint', 'accesspoint' => $accesspoint['wifi_accesspoint_id'])).'">' . $accesspoint['ap_name'] .'</a></td>';

    echo '<td>'.$accesspoint['ap_index'].'</td><td>'.$accesspoint['ap_model'].'</td><td>'.$accesspoint['ap+location'].'</td><td>'.$accesspoint['ap_serial'].(isset($accesspoint['fingerprint']) ? ' / '.$accesspoint['fingerprint'] : '');

    echo '</td>';
    echo '</tr>';
  }

  echo('</table>');
  echo generate_box_close();
}

register_html_title('Access Points');

// EOF
