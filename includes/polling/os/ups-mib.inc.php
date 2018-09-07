<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$manufacturer = snmp_get_oid($device, 'upsIdentManufacturer.0', 'UPS-MIB');

if (snmp_status())
{
  $model    = snmp_get_oid($device, 'upsIdentModel.0', 'UPS-MIB');
  $hardware = $manufacturer . ' ' . $model;

  // Clean up
  //$hardware = str_replace('Liebert Corporation Liebert', 'Liebert', $hardware);
  $hardware_replace = array(
    'Liebert Corporation Liebert' => 'Liebert',
    'CPS '                        => 'PowerWalker ',
  );
  $hardware = array_str_replace($hardware_replace, $hardware, TRUE);
}

// EOF
