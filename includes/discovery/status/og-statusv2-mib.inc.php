<?php

echo(" OG-STATUSv2-MIB ");

$cellmodems = snmpwalk_cache_multi_oid($device, 'ogCellModemTable', array(), $mib);

foreach ($cellmodems as $index => $modem) {
  if ($modem['ogCellModemEnabled'] == 'enabled')
  {
    $descr = 'Cellular Modem ' . $modem['ogCellModemModel'];
    $value = $modem['ogCellModemConnected'];
    $oid = '1.3.6.1.4.1.25049.17.17.1.5.' . $index;

    discover_status($device, $oid, 'CellModemEntry.' . $index, 'ogCellModemRegistered', $descr, $value, array('name' => $value,
													   'entPhysicalClass' => 'other')
													  );
  }
}

unset($cellmodems);

// EOF
