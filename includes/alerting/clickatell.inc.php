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

$message = $message_tags['TITLE'] . PHP_EOL;
$message .= str_replace("             ", "", $message_tags['METRICS']);

// Clickatell Config

$url = sprintf('https://platform.clickatell.com/messages/http/send?apiKey=%s&to=%s&content=%s',
  trim($endpoint['apiid']), urlencode($endpoint['recipient']), urlencode($message));
        
$context_data = array(
  'method'  => 'GET',
  "Content-Type: application/json\r\n",
);

// Send out API call and parse response into an associative array
$result = json_decode(get_http_request($url, $context_data), TRUE);

if ($result['messages'][0]['accepted'] == 1)
{
  $notify_status['success'] = TRUE;
} else {
  $notify_status['success'] = FALSE;
}

unset($url, $message, $result, $context_data);

// EOF
