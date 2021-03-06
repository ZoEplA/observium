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

//  Generic System Statistics

if (is_device_mib($device, 'HOST-RESOURCES-MIB'))
{
  $oid_list = "hrSystemProcesses.0 hrSystemNumUsers.0";
  $hrSystem  = snmp_get_multi_oid($device, $oid_list, array(), "HOST-RESOURCES-MIB");

  print_cli_data_field("Collecting", 2);

  if (is_numeric($hrSystem[0]['hrSystemProcesses']))
  {
    rrdtool_update_ng($device, 'hr_processes', array('procs' => $hrSystem[0]['hrSystemProcesses']));
    $graphs['hr_processes'] = TRUE;
    echo(" Processes");
  }

  if (is_numeric($hrSystem[0]['hrSystemNumUsers']))
  {
    rrdtool_update_ng($device, 'hr_users', array('users' => $hrSystem[0]['hrSystemNumUsers']));
    $graphs['hr_users'] = TRUE;
    echo(" Users");
  }

  echo(PHP_EOL);
} # end is_device_mib()

// EOF
