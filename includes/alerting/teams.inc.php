<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage alerting
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

// We only get <a href="..." as link, but we only need the actual http link!
// FIXME UGLY - need extra field? ENTITY_LINK_RAW ?
$linkparts = explode('"', $message_tags['ENTITY_LINK']); $entity_link = $linkparts[1];
$linkparts = explode('"', $message_tags['DEVICE_LINK']); $device_link = $linkparts[1];

// JSON data
$data_string = json_encode(array(
  'title' => $message_tags['TITLE'],
  'text' => ' ',
  'sections' => array(
    array(
      'facts' => array(
        array('name' => 'Entity', 'value' => '[' . $message_tags['TITLE'] . '](' . $entity_link . ')'),
        array('name' => 'Description', 'value' => $message_tags['ENTITY_DESCRIPTION']),
        array('name' => 'Conditions', 'value' => $message_tags['CONDITIONS']),
        array('name' => 'Metrics', 'value' => $message_tags['METRICS']),
        array('name' => 'Duration', 'value' => $message_tags['DURATION']),
        array('name' => 'Device', 'value' => '[' . $message_tags['DEVICE_HOSTNAME'] . '](' . $device_link . ')'),
        array('name' => 'Hardware', 'value' => $message_tags['DEVICE_HARDWARE']),
        array('name' => 'Operating System', 'value' => $message_tags['DEVICE_OS']),
        array('name' => 'Location', 'value' => $message_tags['DEVICE_LOCATION']),
        array('name' => 'Uptime', 'value' => $message_tags['DEVICE_UPTIME']),
  )))));

// JSON data + HTTP headers
$context_data = array(
  'method' => 'POST',
  'header' =>
    "Connection: close\r\n".
    "Content-Type: application/json\r\n".
    "Content-Length: ".strlen($data_string)."\r\n",
  'content' => $data_string);

// Send out API call
$response = get_http_request($endpoint['webhook_address'], $context_data);

if ($response == 1)
{
  $notify_status['success'] = TRUE;
} else {
  $notify_status['success'] = FALSE;
} 
unset($device_link, $entity_link, $context_data);

// EOF
