<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$oids = snmpwalk_cache_multi_oid($device, 'dpsRTUAlarmGrid', array(), "DPS-MIB-V38");

foreach($oids as $index => $entry)
{

  $descr    = $entry['dpsRTUAPntDesc'];
  $oid_name = 'dpsRTUAState';
  $oid_num  = '.1.3.6.1.4.1.2682.1.2.5.1.6.'.$index;
  $type     = 'dpsRTUAState';
  $value    = $entry[$oid_name];

  // Ignore sensors that aren't configured or named
  if ($value == "-") { continue; }
  else if (in_array($descr, array('', '00', 'Undefined', '-Undefined', 'MjO:', 'MjU:', 'MnO:', 'MnU:', 'NotDet:'))) { continue; }

  // CLEANME. Compatibility, remove in r9500, but not before CE 18.2
  rename_rrd_entity($device, 'status', array('descr' => $descr, 'index' => "dpsRTUAlarmGrid.$index", 'type' => $type),  // old
                                       array('descr' => $descr, 'index' => $oid_name.'.'.$index,     'type' => $type)); // new

  discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));

}

// EOF
