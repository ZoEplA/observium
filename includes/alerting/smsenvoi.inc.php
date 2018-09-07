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

$url = 'http://www.smsenvoi.com/httpapi/sendsms/';

$postdata = http_build_query(
    array(
        "email" => $endpoint['originator'],
        "apikey" => $endpoint['apiid'],
        "message[type]" => $endpoint['type'],
        "message[subtype]" => $endpoint['subtype'],
        "message[senderlabel]" => $endpoint['senderlabel'], 
        "message[recipients]" => $endpoint['recipient'],
        "message[content]" => $message)
);

$context_data = array(
  'method'  => 'POST',
  'content' => $postdata
);

// Send out API call and parse response into an associative array
$response = get_http_request($url, $context_data);

$notify_status['success'] = FALSE;
if ($response !== FALSE)
{
  $response = json_decode($response, TRUE);
  //var_dump($response);
  if (isset($response['success']) && $response['success'] == 1) { $notify_status['success'] = TRUE; }
}

unset($url, $send, $message, $response, $postdata, $context_data);

// EOF
