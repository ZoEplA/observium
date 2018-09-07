<?php

/* Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

global $graphs;

$count = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_deleted` = ?;', array($device['device_id'], '0'));

print_cli_data("Status Count", $count);

if ($count > 0)
{

  $query = 'SELECT `status_oid` FROM `status` WHERE `device_id` = ? AND `poller_type` = ? AND `status_deleted` = ? ORDER BY `status_oid`';
  $oid_to_cache = dbFetchColumn($query, array($device['device_id'], 'snmp', '0'));
  usort($oid_to_cache, 'compare_numeric_oids'); // correctly sort numeric oids
  print_debug_vars($oid_to_cache);
  $oid_cache = snmp_get_multi_oid($device, $oid_to_cache, $oid_cache, NULL, NULL, OBS_SNMP_ALL_NUMERIC);

  print_debug_vars($oid_cache);

  global $table_rows;
  $table_rows = array();

  poll_status($device, $oid_cache);

  $headers = array('%WDescr%n', '%WType%n', '%WIndex%n', '%WOrigin%n', '%WValue%n', '%WStatus%n', '%WLast Changed%n');
  print_cli_table($table_rows, $headers);

}

// EOF
