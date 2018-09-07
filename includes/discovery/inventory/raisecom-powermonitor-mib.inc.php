<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$mib = 'RAISECOM-POWERMONITOR-MIB';
$cache = array();
$cache = snmpwalk_cache_oid($device, 'raisecomPowerSerialNumber', $cache, $mib);
$cache = snmpwalk_cache_oid($device, 'raisecomPowerType', $cache, $mib);

$system_index = 201;
foreach ($cache as $id => $entry)
{
  $psu_type = strtoupper($entry['raisecomPowerType']);
  if ($psu_type == 'AC' || $psu_type == 'DC')
  {
    $inventory[$system_index] = array(
      'entPhysicalDescr'        => "{$psu_type} Power Supply Unit",
      'entPhysicalClass'        => 'module',
      'entPhysicalName'         => "PSU $id",
      'entPhysicalSerialNum'    => $entry['raisecomPowerSerialNumber'],
      'entPhysicalAssetID'      => '',
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 1, // ENTITY-MIB exposes the chassis with index 1.
      'entPhysicalParentRelPos' => -1,
      'entPhysicalMfgName'      => 'Raisecom'
    );
    discover_inventory($valid['inventory'], $device, $system_index, $inventory[$system_index], $mib);
  }
  $system_index++;
}

unset($mib, $cache, $system_index, $id, $entry, $psu_type);

// EOF
