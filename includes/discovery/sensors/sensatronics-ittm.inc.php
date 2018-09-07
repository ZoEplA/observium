<?php

//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.configData.measurementSystem.unitMode.0 = Wrong Type (should be INTEGER): STRING: "C"
//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.sensorInfo.sensor1.sensor1Name.0 = STRING: Probe 1
//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.sensorInfo.sensor1.sensor1DataStr.0 = STRING: 15.9
//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.sensorInfo.sensor1.sensor1DataInt.0 = INTEGER: 16
//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.sensorInfo.sensor1.sensor1SwitchInt.0 = INTEGER: 0
//enterprises.sensatronics.consumerProducts.envMonitors.productITTM.sensorInfo.sensor1.sensor1SwitchStr.0 = STRING: OPEN

$mib  = 'SENSATRONICS-ITTM';
$oids = snmpwalk_cache_oid($device, 'sensorInfo', array(), 'SENSATRONICS-ITTM');

$i = 1;
while ($i <= 17)
{

  $name = $oids[0]['sensor'.$i.'Name'];
  if(is_numeric($oids[0]['sensor'.$i.'DataStr']) && $oids[0]['sensor'.$i.'DataStr'] != '-99.9')
  {

    // Output Current
    $oid_name   = 'sensor'.$i.'DataStr';
    $oid        = '.1.3.6.1.4.1.16174.1.1.1.3.'.$i.'.2.0';
    $type       = $mib . '-DataStr';
    $descr      = $oids[0]['sensor'.$i.'Name'];
    $value      = $oids[0]['sensor'.$i.'DataStr'];

    discover_sensor($valid['sensor'], 'temperature', $device, $oid, $i, $type, $descr, 1, $value);

  }

  $i++; // Increment counter
}
