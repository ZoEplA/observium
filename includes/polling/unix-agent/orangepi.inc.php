<?php

/**
 * Observium
 *
 *   This files is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @subpackage orangepi
 * @author     Dennis de Houx <info@all-in-one.be>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 * @version    1.0.1
 *
 */

global $agent_sensors;

if ($agent_data['orangepi'] != ':')
{
  echo "orangepi: ";

  $rpi_sensors = explode("\n", $agent_data['orangepi']);

  foreach ($rpi_sensors as $item => $rpi_sensor)
  {
    $rpi_sensor = trim($rpi_sensor);

    if (!empty($rpi_sensor))
    {
      $data             = explode(":", $rpi_sensor);
      list($type,$info) = explode("-", $data[0], 2);

      switch ($type)
      {
        case 'clock':
          $sensorindex = ++$clockcount;
          $sensortype = 'frequency';
          $scale = 1000;
          break;
        case 'volts':
          $sensorindex = ++$voltcount;
          $sensortype = 'voltage';
          $scale = 0.001;
          break;
        case 'temp':
          $sensorindex = ++$tempcount;
          $sensortype = 'temperature';
          $scale = 0.001;
          break;
        case 'hddtemp':
          $sensorindex = ++$tempcount;
          $sensortype = 'temperature';
          $scale = 1;
          break;
        default:
          unset($sensortype);
          break;
      }

      if (isset($sensortype))
      {
        $value = trim($data[1]);
        discover_sensor($valid['sensor'], $sensortype, $device, '', $sensorindex, 'orangepi', $info, $scale, $value, array(), 'agent');
        $agent_sensors[$sensortype]['orangepi'][$sensorindex] = array('description' => $info, 'current' => $value, 'index' => $sensorindex);
      }
    }
  }

  echo(PHP_EOL);
  unset($voltcount);
  unset($tempcount);
  unset($clockcount);
  unset($sensorindex);
  unset($value);
  unset($rpi_sensors);
  unset($rpi_sensor);
}

// EOF
