<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$mib = 'RAISECOM-SYSTEM-MIB';
if (is_device_mib($device, $mib))
{
  $num_proc = snmp_get_oid($device, 'raisecomCpuTotalProcNum.0', $mib);
  if (is_numeric($num_proc))
  {
    rrdtool_update_ng($device, 'hr_processes', array('procs' => $num_proc));
    $graphs['hr_processes'] = TRUE;
  }
}

unset ($mib, $num_proc);

// EOF
