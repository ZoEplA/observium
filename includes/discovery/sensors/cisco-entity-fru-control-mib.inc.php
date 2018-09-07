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

echo("CISCO-ENTITY-FRU-CONTROL-MIB ");

// Skip statuses if we have any status from CISCO-ENVMON-MIB (for exclude duplicates)
$skip_status = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ?;', array($device['device_id'], 'cisco-envmon-state')) > 0;

// Walk CISCO-ENTITY-FRU-CONTROL-MIB oids
$entity_array = array();
$oids = array('cefcFRUPowerStatusEntry', 'cefcFanTrayStatusEntry');
foreach ($oids as $oid)
{
  $entity_array = snmpwalk_cache_multi_oid($device, $oid, $entity_array, 'CISCO-ENTITY-FRU-CONTROL-MIB');
}
// split PowerSupplyGroup from common walk array
$cefcFRUPowerSupplyGroupEntry = snmpwalk_cache_multi_oid($device, 'cefcFRUPowerSupplyGroupEntry', array(), 'CISCO-ENTITY-FRU-CONTROL-MIB');

if (count($entity_array))
{
  // Pre-cache entity mib (if not cached in inventory module)
  if (is_array($GLOBALS['cache']['entity-mib']))
  {
    $entity_mib = $GLOBALS['cache']['entity-mib'];
    print_debug("ENTITY-MIB already cached");
  } else {
    $entity_mib = array();
    $oids       = array('entPhysicalDescr', 'entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos');
    foreach ($oids as $oid)
    {
      $entity_mib = snmpwalk_cache_multi_oid($device, $oid, $entity_mib, 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
      if (!$GLOBALS['snmp_status']) { break; }
    }
    //$entity_mib = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_mib, 'ENTITY-MIB:IF-MIB');
  }

  // Merge with ENTITY-MIB
  if (count($entity_mib))
  {
    // Power & Fan
    foreach ($entity_array as $index => $entry)
    {
      if (isset($entity_mib[$index]))
      {
        $entity_array[$index] = array_merge($entity_mib[$index], $entry);
      }
    }
    // PowerSupplyGroup
    foreach ($cefcFRUPowerSupplyGroupEntry as $index => $entry)
    {
      if (isset($entity_mib[$index]))
      {
        $cefcFRUPowerSupplyGroupEntry[$index] = array_merge($entity_mib[$index], $entry);
      }
    }
  }
  unset($entity_mib);

  print_debug_vars($cefcFRUPowerSupplyGroupEntry);
  print_debug_vars($entity_array);

  foreach ($cefcFRUPowerSupplyGroupEntry as $index => $entry)
  {
    $descr = $entry['entPhysicalDescr'];

    $oid_name = 'cefcTotalDrawnCurrent';
    $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.1.1.4.'.$index;
    $type     = $mib . '-' . $oid_name;

    if (str_istarts($entry['cefcPowerUnits'], 'centi'))
    {
      $scale  = 0.01; // cefcPowerUnits.100000470 = STRING: CentiAmps @ 12V
    }
    else if (str_istarts($entry['cefcPowerUnits'], 'milli'))
    {
      $scale  = 0.001; // cefcPowerUnits.18 = STRING: milliAmps12v
    } else {
      // FIXME. Other?
      $scale  = 1;
    }
    $value    = $entry[$oid_name];
    if ($value > 0)
    {
      // Limits
      $options  = array();
      if ($entry['cefcTotalAvailableCurrent'] > 0)
      {
        if (substr($entry['cefcTotalAvailableCurrent'], -2) === '00')
        {
          // Cisco 4900M:
          // cefcPowerUnits.9 = centiAmpsAt12V
          // cefcTotalAvailableCurrent.9 = 8000
          // cefcTotalDrawnCurrent.9 = 4883
          $options['limit_high']    = $entry['cefcTotalAvailableCurrent'] * $scale;
        } else {
          // Cisco 2901:
          // cefcPowerUnits.18 = milliAmps12v
          // cefcTotalAvailableCurrent.18 = 1659
          // cefcTotalDrawnCurrent.18 = 9641
          $options['limit_high']    = ($value + $entry['cefcTotalAvailableCurrent']) * $scale;
        }
        $options['limit_high_warn'] = $options['limit_high'] * 0.8; // 80%
      }

      discover_sensor($valid['sensor'], 'current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
    }

    $oid_name = 'cefcPowerRedundancyOperMode';
    $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.1.1.5.'.$index;
    $type     = 'cefcPowerRedundancyType';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'powersupply'));
  }

  foreach ($entity_array as $index => $entry)
  {
    if (!is_numeric($index)) { continue; }

    $descr = $entry['entPhysicalDescr'];

    // Power Supplies
    if (!$skip_status && $entry['entPhysicalClass'] == 'powerSupply' && $entry['cefcFRUPowerAdminStatus'] != 'off')
    {
      $oid_name = 'cefcFRUPowerOperStatus';
      $oid_num  = '.1.3.6.1.4.1.9.9.117.1.1.2.1.2.'.$index;
      $type     = 'PowerOperType';
      $value    = $entry[$oid_name];

      discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'powersupply'));
    }

    // Fans
    if (!$skip_status && $entry['entPhysicalClass'] == 'fan')
    {
      $oid_name = 'cefcFanTrayOperStatus';
      $oid_num  = '.1.3.6.1.4.1.9.9.117.1.4.1.1.1.'.$index;
      $type     = 'cefcFanTrayOperStatus';
      $value    = $entry[$oid_name];

      discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'fan'));
    }
  }
}

unset($cefcFRUPowerSupplyGroupEntry, $entity_array, $skip_status);

// EOF
