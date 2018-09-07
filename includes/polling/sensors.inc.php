<?php

/* Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

global $graphs;

$query  = 'SELECT DISTINCT `sensor_class` FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?';
$query .= generate_query_values(array_keys($config['sensor_types']), 'sensor_class'); // Limit by known classes

$sensor_classes = dbFetchColumn($query, array($device['device_id'], '0'));
//$count = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?;', array($device['device_id'], '0'));

//print_cli_data("Sensor Count", $count);

if (count($sensor_classes) > 0)
{
  // Cache device sensors attribs (currently need for get sensor_addition attrib)
  get_device_entities_attribs($device['device_id'], 'sensor');
  //print_vars($GLOBALS['cache']['entity_attribs']);

  if ($device['os'] != 'ironware') // Temporarily workaround this breaking ironware systems
  {
    $query = 'SELECT `sensor_oid` FROM `sensors` WHERE `device_id` = ? AND `poller_type` = ? AND `sensor_deleted` = ? ORDER BY `sensor_oid`';
    $oid_to_cache = dbFetchColumn($query, array($device['device_id'], 'snmp', '0'));
    usort($oid_to_cache, 'compare_numeric_oids'); // correctly sort numeric oids
    print_debug_vars($oid_to_cache);
    $oid_cache = snmp_get_multi_oid($device, $oid_to_cache, $oid_cache, NULL, NULL, OBS_SNMP_ALL_NUMERIC);
  }

  print_debug_vars($oid_cache);

  global $table_rows;
  $table_rows = array();

  // Call poll_sensor for each sensor type that we support.
  foreach ($sensor_classes as $sensor_class)
  {
    $sensor_class_data = $config['sensor_types'][$sensor_class];
    poll_sensor($device, $sensor_class, $sensor_class_data['symbol'], $oid_cache);
  }

  $headers = array('Descr', 'Class', 'Type', 'Origin', 'Value', 'Event', 'Last Changed');
  print_cli_table($table_rows, $headers);
}

// EOF
