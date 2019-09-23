<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 *
 */

// New Definition Discovery

function discover_sensor_definition($device, $mib, $entry)
{

  echo($entry['oid']. ' [');

  // Check that types listed in skip_if_valid_exist have already been found
  if (discovery_check_if_type_exist($GLOBALS['valid'], $entry, 'sensor')) { echo '!]'; return; }

  // Check array requirements list
  if (discovery_check_requires_pre($device, $entry, 'sensor')) { echo '!]'; return; }

  // Fetch table or Oids
  $table_oids = array('oid', 'oid_descr', 'oid_scale', 'oid_unit', 'oid_class',
                      'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high',
                      'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale',
                      'oid_extra', 'oid_entPhysicalIndex');
  $sensor_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

  if (empty($entry['oid_num']))
  {
    // Use snmptranslate if oid_num not set
    $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
  } else {
    $entry['oid_num'] = rtrim($entry['oid_num'], '.');
  }

  $entry['type'] = $mib . '-' . $entry['oid'];

  if (!isset($entry['scale'])) { $options['scale'] = 1; } else { $options['scale'] = $entry['scale']; }
  if (!isset($entry['scale'])) { $scale            = 1; } else { $scale            = $entry['scale']; }


  $counters = array(); // Reset per-class counters for each MIB

  $sensors_count = count($sensor_array);
  foreach ($sensor_array as $index => $sensor)
  {
    $options = array();

    if (isset($entry['class']))
    {
      // Hardcoded sensor class
      $class = $entry['class'];
    } else {
      // If no 'class' hardcoded, see if we can get class from the map_class via oid_class
      if (isset($entry['oid_class']) && isset($sensor[$entry['oid_class']]))
      {
        if (isset($entry['map_class'][$sensor[$entry['oid_class']]]))
        {
          $class = $entry['map_class'][$sensor[$entry['oid_class']]];
        } else {
          print_debug('Value from oid_class (' . $sensor[$entry['oid_class']] . ') does not match any configured values in map_class!');
          continue; // Break foreach. Next sensor!
        }
      } else {
        print_debug('No class hardcoded, but no oid_class (' . $entry['oid_class'] . ') found in table walk!');
        continue; // Break foreach. Next sensor!
      }
    }

    $dot_index = '.' . $index;
    $oid_num   = $entry['oid_num'] . $dot_index;

    // echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($sensor); echo PHP_EOL; print_vars($descr); echo PHP_EOL;

    // %i% can be used in description, a counter is kept per sensor class
    $counters[$class]++;

    // Generate specific keys used during rewrites

    $sensor['class'] = nicecase($class);  // Class in descr
    $sensor['index'] = $index;            // Index in descr
    $sensor['i']     = $counters[$class]; // i++ counter in descr (per sensor class)

    // Check array requirements list
    if (discovery_check_requires($device, $entry, $sensor, 'sensor')) { continue; }

    $value = snmp_fix_numeric($sensor[$entry['oid']]);
    if (!is_numeric($value))
    {
      print_debug("Excluded by current value ($value) is not numeric.");
      continue;
    }

    // Check for min/max values, when sensors report invalid data as sensor does not exist
    if (isset($entry['min']) && $value <= $entry['min'])
    {
      print_debug("Excluded by current value ($value) is equals or below min (".$entry['min'].").");
      continue;
    }
    elseif (isset($entry['max']) && $value >= $entry['max'])
    {
      print_debug("Excluded by current value ($value) is equals or above max (".$entry['max'].").");
      continue;
    }
    elseif (isset($entry['invalid']) && in_array($value, (array)$entry['invalid']))
    {
      print_debug("Excluded by current value ($value) in invalid range [".implode(', ', (array)$entry['invalid'])."].");
      continue;
    }

    // Check limits oids if set
    foreach (array('limit_low', 'limit_low_warn', 'limit_high_warn', 'limit_high') as $limit)
    {
      $oid_limit = 'oid_'   . $limit;
      if (isset($entry[$oid_limit]))
      {
        if (isset($sensor[$entry[$oid_limit]])) { $options[$limit] = $sensor[$entry[$oid_limit]]; } // Named oid, exist in table
        else                                    { $options[$limit] = snmp_get_oid($device, $entry[$oid_limit] . $dot_index, $mib); } // Numeric oid
        $options[$limit] = snmp_fix_numeric($options[$limit]);
        // Scale limit
        if (isset($entry['limit_scale']) && is_numeric($entry['limit_scale']) && $entry['limit_scale'] != 0)
        {
          $options[$limit] *= $entry['limit_scale'];
        }
      }
      elseif (isset($entry[$limit]) && is_numeric($entry[$limit]))
      {
        $options[$limit] = $entry[$limit]; // Limit from definition
      }
    }

    // Limits based on nominal +- delta oids (see TPT-HEALTH-MIB)
    if (isset($entry['oid_limit_nominal']) && (isset($entry['oid_limit_delta']) || isset($entry['oid_limit_delta_warn'])))
    {
      $oid_limit = 'oid_limit_nominal';
      if (isset($sensor[$entry[$oid_limit]])) { $limit_nominal = $sensor[$entry[$oid_limit]]; } // Named oid, exist in table
      else                                    { $limit_nominal = snmp_get_oid($device, $entry[$oid_limit] . $dot_index, $mib); } // Numeric oid

      if (is_numeric($limit_nominal) && isset($entry['oid_limit_delta_warn']))
      {
        $oid_limit = 'oid_limit_delta_warn';
        if (isset($sensor[$entry[$oid_limit]])) { $limit_delta_warn = $sensor[$entry[$oid_limit]]; } // Named oid, exist in table
        else                                    { $limit_delta_warn = snmp_get_oid($device, $entry[$oid_limit] . $dot_index, $mib); } // Numeric oid
        $options['limit_low_warn']  = $limit_nominal - $limit_delta_warn; //$entry['limit_scale'];
        $options['limit_high_warn'] = $limit_nominal + $limit_delta_warn; //$entry['limit_scale'];
        if (isset($entry['limit_scale']) && is_numeric($entry['limit_scale']) && $entry['limit_scale'] != 0)
        {
          $options['limit_low_warn']  *= $entry['limit_scale'];
          $options['limit_high_warn'] *= $entry['limit_scale'];
        }
      }
      if (is_numeric($limit_nominal) && isset($entry['oid_limit_delta']))
      {
        $oid_limit = 'oid_limit_delta';
        if (isset($sensor[$entry[$oid_limit]])) { $limit_delta = $sensor[$entry[$oid_limit]]; } // Named oid, exist in table
        else                                    { $limit_delta = snmp_get_oid($device, $entry[$oid_limit] . $dot_index, $mib); } // Numeric oid
        $options['limit_low']  = $limit_nominal - $limit_delta;
        $options['limit_high'] = $limit_nominal + $limit_delta;
        if (isset($entry['limit_scale']) && is_numeric($entry['limit_scale']) && $entry['limit_scale'] != 0)
        {
          $options['limit_low']  *= $entry['limit_scale'];
          $options['limit_high'] *= $entry['limit_scale'];
        }
      }
    }
    // One last step for convert limit unit, when value and limits use different units
    if (isset($entry['limit_unit']))
    {
      foreach (array('limit_low', 'limit_low_warn', 'limit_high_warn', 'limit_high') as $limit)
      {
        if (isset($options[$limit]))
        {
          $options[$limit] = value_to_si($options[$limit], $entry['limit_unit'], $entry['class']);
        }
      }
    }

    // Allow disable auto limits by definitions
    if (isset($entry['limit_auto']))
    {
      $options['limit_auto'] = $entry['limit_auto'];
    }

    // Unit
    if (isset($entry['unit']))
    {
      // Static unit definition
      $options['sensor_unit'] = $entry['unit'];
    }
    if (isset($entry['oid_unit']))
    {
      if (isset($sensor[$entry['oid_unit']]))
      {
        // Unit in same table
        $unit = $sensor[$entry['oid_unit']];
      }
      elseif (str_contains($entry['oid_unit'], '.'))
      {
        // Unit is outside from table with single index, see VERTIV-V5-MIB
        $unit = snmp_cache_oid($device, $entry['oid_unit'], $mib);
      }
      // Translate unit from specific Oid
      if (isset($entry['map_unit'][$unit]))
      {
        $options['sensor_unit'] = $entry['map_unit'][$unit];
      }
    }

    // Rule-based entity linking.
    if ($measured = entity_measured_match_definition($device, $entry, $sensor, 'sensor'))
    {
      $options = array_merge($options, $measured);
      $sensor  = array_merge($sensor, $measured); // append to $sensor for %descr% tags, ie %port_label%
    }
    // End rule-based entity linking
    elseif (isset($entry['entPhysicalIndex']))
    {
      // Just set physical index
      $options['entPhysicalIndex'] = array_tag_replace($sensor, $entry['entPhysicalIndex']);
    }

    // Generate Description
    $descr = entity_descr_definition('sensor', $entry, $sensor, $sensors_count);

    // Rename old (converted) RRDs to definition format
    if (isset($entry['rename_rrd']))
    {
      $options['rename_rrd'] = $entry['rename_rrd'];
    }
    elseif (isset($entry['rename_rrd_full']))
    {
      $options['rename_rrd_full'] = $entry['rename_rrd_full'];
    }

    discover_sensor_ng($device, $class, $mib, $entry['oid'], $oid_num, $index, $entry['type'], $descr, $scale, $value, $options);
  }

  echo '] ';

}


// Compatibility wrapper!
function discover_sensor($class, $device, $numeric_oid, $index, $type, $sensor_descr, $scale = 1, $value = NULL, $options = array(), $poller_type = NULL)
{

  if (isset($poller_type)) { $options['poller_type'] = $poller_type; }

  return discover_sensor_ng($device, $class, '', '', $numeric_oid, $index, $type, $sensor_descr, $scale, $value, $options);
}


// TESTME needs unit testing
/**
 * Discover a new sensor on a device
 *
 * This function adds a status sensor to a device, if it does not already exist.
 * Data on the sensor is updated if it has changed, and an event is logged with regards to the changes.
 *
 * Status sensors are handed off to discover_status().
 * Current sensor values are rectified in case they are broken (added spaces, etc).
 *
 * @param array $device        Device array sensor is being discovered on
 * @param string $class        Class of sensor (voltage, temperature, etc.)
 * @param string $mib          SNMP MIB name
 * @param string $object       SNMP Named Oid of sensor (without index)
 * @param string $oid          SNMP Numeric Oid of sensor (without index)
 * @param string $index        SNMP index of sensor
 * @param string $type         Type of sensor
 * @param string $sensor_descr Description of sensor
 * @param int $scale           Scale of sensor (0.1 for 1:10 scale, 10 for 10:1 scale, etc)
 * @param string $value        Current sensor value
 * @param array $options       Options (sensor_unit, limit_auto, limit*, poller_type, scale, measured_*)
 * @return bool
 */
function discover_sensor_ng($device, $class, $mib, $object, $oid, $index, $type, $sensor_descr, $scale = 1, $value = NULL, $options = array())
{
  global $config;

  //echo 'MIB:'; print_vars($mib);

  $poller_type   = (isset($options['poller_type']) ? $options['poller_type'] : 'snmp');

  $sensor_deleted = 0;

  // If this is actually a status indicator, pass it off to discover_status() then return.
  if ($class == 'state' || $class == 'status')
  {
    print_debug("Redirecting call to discover_status().");
    return discover_status_ng($device, $mib, $object, $oid, $index, $type, $sensor_descr, $value, $options);
  }
  elseif (isset($config['counter_types'][$class]))
  {
    print_debug("Redirecting call to discover_counter().");
    return discover_counter($device, $class, $mib, $object, $oid, $index, $sensor_descr, $scale, $value, $options);
  }
  elseif ($class == 'power' && $options['measured_class'] == 'port' &&            // Power sensor with measured port entity
    $config['sensors']['port']['power_to_dbm'] &&                           // Convert option set to TRUE
    $options['sensor_unit'] != 'W' && !str_icontains($sensor_descr, 'PoE')) // Not forced W unit, not PoE
  {
    // DOM Power sensors convert to dBm
    print_debug("DOM power sensor forced to dBm sensor.");
    $options['sensor_unit'] = 'W';
    return discover_sensor_ng($device, 'dbm', $mib, $object, $oid, $index, $type, $sensor_descr, $scale, $value, $options);
  }

  // Init main
  $param_main = array('oid' => 'sensor_oid', 'sensor_descr' => 'sensor_descr', 'scale' => 'sensor_multiplier', 'sensor_deleted' => 'sensor_deleted', 'mib' => 'sensor_mib', 'object' => 'sensor_object');

  // Init numeric values
  if (!is_numeric($scale) || $scale == 0) { $scale = 1; }

  // Generate type if it's not provided
  if (!strlen($type))
  {
    $type = $mib . '-' . $object;
  }

  // Skip discovery sensor if value not numeric or null (default)
  if (strlen($value))
  {
    // Some retarded devices report data with spaces and commas
    // STRING: "  20,4"
    $value = snmp_fix_numeric($value);
  }

  if (is_numeric($value))
  {
    $attrib_type = 'sensor_addition';
    if (isset($options[$attrib_type]) && is_numeric($options[$attrib_type]))
    {
      // This option currently required and used only in FOUNDRY-POE-MIB
      $value += $options[$attrib_type];
    }
    $value = scale_value($value, $scale);
    // $value *= $scale; // Scale before unit conversion
    $value = value_to_si($value, $options['sensor_unit'], $class); // Convert if not SI unit
  } else {
    print_debug("Sensor skipped by not numeric value: '$value', '$sensor_descr'");
    if (strlen($value))
    {
      print_debug("Perhaps this is named sensor, use discover_status() instead.");
    }
    return FALSE;
  }

  $param_limits = array('limit_high' => 'sensor_limit',     'limit_high_warn' => 'sensor_limit_warn',
                        'limit_low'  => 'sensor_limit_low', 'limit_low_warn'  => 'sensor_limit_low_warn');
  foreach ($param_limits as $key => $column)
  {
    // Set limits vars and unit convert if required
    $$key = (is_numeric($options[$key]) ? value_to_si($options[$key], $options['sensor_unit'], $class) : NULL);
  }
  // Auto calculate high/low limits if not passed
  $limit_auto = !isset($options['limit_auto']) || (bool)$options['limit_auto'];

  // Init optional
  $param_opt = array('entPhysicalIndex', 'entPhysicalClass', 'entPhysicalIndex_measured', 'measured_class', 'measured_entity', 'sensor_unit');
  foreach ($param_opt as $key)
  {
    $$key = ($options[$key] ? $options[$key] : NULL);
  }

  print_debug("Discover sensor: [class: $class, device: ".$device['hostname'].", oid: $oid, index: $index, type: $type, descr: $sensor_descr, scale: $scale, limits: ($limit_low, $limit_low_warn, $limit_high_warn, $limit_high), CURRENT: $value, $entPhysicalIndex, $entPhysicalClass");

  // Check sensor ignore filters
  foreach ($config['ignore_sensor'] as $bi)        { if (strcasecmp($bi, $sensor_descr) == 0)   { print_debug("Skipped by equals: $bi, $sensor_descr "); return FALSE; } }
  foreach ($config['ignore_sensor_string'] as $bi) { if (stripos($sensor_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $sensor_descr "); return FALSE; } }
  foreach ($config['ignore_sensor_regexp'] as $bi) { if (preg_match($bi, $sensor_descr) > 0)    { print_debug("Skipped by regexp: $bi, $sensor_descr "); return FALSE; } }

  if (!is_null($limit_low_warn) && !is_null($limit_high_warn) && ($limit_low_warn > $limit_high_warn))
  {
    // Fix high/low thresholds (i.e. on negative numbers)
    list($limit_high_warn, $limit_low_warn) = array($limit_low_warn, $limit_high_warn);
  }
  print_debug_vars($limit_high);
  print_debug_vars($limit_high_warn);
  print_debug_vars($limit_low_warn);
  print_debug_vars($limit_low);

  //if (dbFetchCell('SELECT COUNT(`sensor_id`) FROM `sensors`
  //                 WHERE `poller_type`= ? AND `sensor_class` = ? AND `device_id` = ? AND `sensor_type` = ? AND `sensor_index` = ?',
  if (!dbExist('sensors', '`poller_type`= ? AND `sensor_class` = ? AND `device_id` = ? AND `sensor_type` = ? AND `sensor_index` = ?',
               array($poller_type, $class, $device['device_id'], $type, $index)))
  {
    if (!is_numeric($limit_high)) { $limit_high = sensor_limit_high($class, $value, $limit_auto); }
    if (!is_numeric($limit_low))  { $limit_low  = sensor_limit_low($class, $value, $limit_auto); }

    if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high))
    {
      // Fix high/low thresholds (i.e. on negative numbers)
      list($limit_high, $limit_low) = array($limit_low, $limit_high);
      print_debug("High/low limits swapped.");
    }

    $sensor_insert = array('poller_type' => $poller_type, 'sensor_class' => $class, 'device_id' => $device['device_id'],
                           'sensor_index' => $index, 'sensor_type' => $type);

    foreach ($param_main as $key => $column)
    {
      $sensor_insert[$column] = $$key;
    }

    foreach ($param_limits as $key => $column)
    {
      // Convert strings/numbers to (float) or to array('NULL')
      $$key = is_numeric($$key) ? (float)$$key : array('NULL');
      $sensor_insert[$column] = $$key;
    }
    foreach ($param_opt as $key)
    {
      if (is_null($$key)) { $$key = array('NULL'); }
      $sensor_insert[$key] = $$key;
    }

    $sensor_insert['sensor_value'] = $value;
    $sensor_insert['sensor_polled'] = time(); // array('NOW()'); // this field is INT(11)

    $sensor_id = dbInsert($sensor_insert, 'sensors');

    $attrib_type = 'sensor_addition';
    if (isset($options[$attrib_type]) && is_numeric($options[$attrib_type]))
    {
      // Add sensor attrib for use in poller
      set_entity_attrib('sensor', $sensor_id, $attrib_type, $options[$attrib_type]);
    }

    print_debug("( $sensor_id inserted )");
    echo('+');

    log_event("Sensor added: $class $type $index $sensor_descr", $device, 'sensor', $sensor_id);
  } else {
    $sensor_entry = dbFetchRow("SELECT * FROM `sensors` WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_type` = ? AND `sensor_index` = ?", array($class, $device['device_id'], $type, $index));
    $sensor_id = $sensor_entry['sensor_id'];

    // Limits
    if (!$sensor_entry['sensor_custom_limit'])
    {
      if (!is_numeric($limit_high))
      {
        if ($sensor_entry['sensor_limit'] !== '')
        {
          // Calculate a reasonable limit
          $limit_high = sensor_limit_high($class, $value, $limit_auto);
        } else {
          // Use existing limit. (this is wrong! --mike)
          $limit_high = $sensor_entry['sensor_limit'];
        }
      }

      if (!is_numeric($limit_low))
      {
        if ($sensor_entry['sensor_limit_low'] !== '')
        {
          // Calculate a reasonable limit
          $limit_low = sensor_limit_low($class, $value, $limit_auto);
        } else {
          // Use existing limit. (this is wrong! --mike)
          $limit_low = $sensor_entry['sensor_limit_low'];
        }
      }

      // Fix high/low thresholds (i.e. on negative numbers)
      if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high))
      {
        list($limit_high, $limit_low) = array($limit_low, $limit_high);
        print_debug("High/low limits swapped.");
      }

      // Update limits
      $update = array();
      $update_msg = array();
      $debug_msg = 'Current sensor value: "'.$value.'", scale: "'.$scale.'"'.PHP_EOL;
      foreach ($param_limits as $key => $column)
      {
        // $key - param name, $$key - param value, $column - column name in DB for $key
        $debug_msg .= '  '.$key.': "'.$sensor_entry[$column].'" -> "'.$$key.'"'.PHP_EOL;
        //convert strings/numbers to identical type (float) or to array('NULL') for correct comparison
        $$key = is_numeric($$key) ? (float)$$key : array('NULL');
        $sensor_entry[$column] = is_numeric($sensor_entry[$column]) ? (float)$sensor_entry[$column] : array('NULL');
        if (float_cmp($$key, $sensor_entry[$column], 0.01) !== 0) // FIXME, need compare autogenerated and hard passed limits by different ways
        {
          $update[$column] = $$key;
          $update_msg[] = $key.' -> "'.(is_array($$key) ? 'NULL' : $$key).'"';
        }
      }
      if (count($update))
      {
        echo("L");
        print_debug($debug_msg);
        log_event('Sensor updated (limits): '.implode(', ', $update_msg), $device, 'sensor', $sensor_entry['sensor_id']);
        $updated = dbUpdate($update, 'sensors', '`sensor_id` = ?', array($sensor_entry['sensor_id']));
      }
    }

    $update = array();
    foreach ($param_main as $key => $column)
    {
      if (float_cmp($$key, $sensor_entry[$column]) !== 0)
      {
        $update[$column] = $$key;
      }
    }

    foreach ($param_opt as $key)
    {
      if ($$key != $sensor_entry[$key])
      {
        $update[$key] = $$key;
      }
    }

    $attrib_type = 'sensor_addition';
    $sensor_addition = get_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type);
    if (is_numeric($sensor_addition))
    {
      if (!is_numeric($options[$attrib_type]))
      {
        del_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type);
      }
      elseif ($options[$attrib_type] != $sensor_addition)
      {
        set_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type, $options[$attrib_type]);
      }
    }
    elseif (is_numeric($options[$attrib_type]))
    {
      set_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type, $options[$attrib_type]);
    }

    if (count($update))
    {
      $updated = dbUpdate($update, 'sensors', '`sensor_id` = ?', array($sensor_entry['sensor_id']));
      echo('U');
      log_event("Sensor updated: $class $type $index $sensor_descr", $device, 'sensor', $sensor_entry['sensor_id']);
    } else {
      echo('.');
    }
  }

  // Rename old (converted) RRDs to definition format
  // Allow with changing class or without
  if (isset($options['rename_rrd']) || isset($options['rename_rrd_full']))
  {
    $rrd_tags = array('index' => $index, 'type' => $type, 'mib' => $mib, 'object' => $object, 'oid' => $object);
    if (isset($options['rename_rrd']))
    {
      $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);
      $old_rrd               = 'sensor-' . $class . '-' . $options['rename_rrd'];
    }
    elseif (isset($options['rename_rrd_full']))
    {
      $options['rename_rrd_full'] = array_tag_replace($rrd_tags, $options['rename_rrd_full']);
      $old_rrd               = 'sensor-' . $options['rename_rrd_full'];
    }
    $new_rrd = 'sensor-'.$class.'-'.$type.'-'.$index;
    rename_rrd($device, $old_rrd, $new_rrd);
  }

  $GLOBALS['valid']['sensor'][$class][$type][$index] = 1;

  return $sensor_id;
  //return TRUE;

}

// TESTME needs unit testing
/**
 * Calculate lower limit on a sensor
 *
 * @param string $class Sensor class (voltage, temperature, ...)
 * @param string $value Current sensor value to use as base
 * @param bool $auto    Set to false to not set an automatic limit
 * @return string
 */
function sensor_limit_low($class, $value, $auto = TRUE)
{
  $limit = NULL;

  if (!$auto || $value == 0) { return $limit; } // Do not calculate limit

  switch($class)
  {
    case 'temperature':
      if ($value > 0)
      {
        $limit = 0; // Freezing cold should be enough of a lower limit.
      }
      break;
    case 'voltage':
      if ($value < 0)
      {
        $limit = $value * (1 + (sgn($value) * 0.15));
      } else {
        $limit = $value * (1 - (sgn($value) * 0.15));
      }
      break;
    case 'humidity':
      $limit = 20;
      break;
    case 'frequency':
      $limit = $value * 0.95;
      break;
    case 'current':
      $limit = NULL;
      break;
    case 'fanspeed':
      $limit = $value * 0.80;
      break;
    case 'power':
      $limit = NULL;
      break;
  }

  return $limit;
}

// TESTME needs unit testing
/**
 * Calculate upper limit on a sensor
 *
 * @param string $class Sensor class (voltage, temperature, ...)
 * @param string $value Current sensor value to use as base
 * @param bool $auto    Set to false to not set an automatic limit
 * @return string
 */
function sensor_limit_high($class, $value, $auto = TRUE)
{
  $limit = NULL;

  if (!$auto || $value == 0) { return $limit; } // Do not calculate limit

  switch($class)
  {
    case 'temperature':
      if ($value < 0)
      {
        // Negative temperatures are usually used for "Thermal margins",
        // indicating how far from the critical point we are.
        $limit = 0;
      } else {
        $limit = $value * 1.60;
      }
      break;
    case 'voltage':
      if ($value < 0)
      {
        $limit = $value * (1 - (sgn($value) * 0.15));
      } else {
        $limit = $value * (1 + (sgn($value) * 0.15));
      }
      break;
    case 'humidity':
      $limit = 70;
      break;
    case 'frequency':
      $limit = $value * 1.05;
      break;
    case 'current':
      $limit = $value * 1.50;
      break;
    case 'fanspeed':
      $limit = $value * 1.80;
      break;
    case 'power':
      $limit = $value * 1.50;
      break;
  }

  return $limit;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_sensors($device, $class, $valid, $poller_type = 'snmp')
{
  $entries = dbFetchRows("SELECT * FROM `sensors` WHERE `device_id` = ? AND `sensor_class` = ? AND `poller_type` = ? AND `sensor_deleted` = '0'", array($device['device_id'], $class, $poller_type));

  if (is_array($entries) && count($entries))
  {
    foreach ($entries as $entry)
    {
      $index = $entry['sensor_index'];
      $type  = $entry['sensor_type'];
      if (!$valid[$class][$type][$index])
      {
        echo("-");
        print_debug("Sensor deleted: $index -> $type");
        //dbDelete('sensors',       "`sensor_id` = ?", array($entry['sensor_id']));

        dbUpdate(array('sensor_deleted' => '1'), 'sensors', '`sensor_id` = ?', array($entry['sensor_id']));
        //dbDelete('sensors-state', "`sensor_id` = ?", array($entry['sensor_id']));

        foreach (get_entity_attribs('sensor', $entry['sensor_id']) as $attrib_type => $value)
        {
          del_entity_attrib('sensor', $entry['sensor_id'], $attrib_type);
        }
        log_event("Sensor deleted: ".$entry['sensor_class']." ".$entry['sensor_type']." ". $entry['sensor_index']." ".$entry['sensor_descr'], $device, 'sensor', $entry['sensor_id']);
      }
    }
  }
}

// Poll a sensor
function poll_sensor($device, $class, $unit, &$oid_cache)
{
  global $config, $agent_sensors, $ipmi_sensors, $graphs, $table_rows;

  $sql  = "SELECT * FROM `sensors`";
  //$sql .= " LEFT JOIN `sensors-state` USING(`sensor_id`)";
  $sql .= " WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_deleted` = ?";
  $sql .= ' ORDER BY `sensor_oid`'; // This fix polling some OIDs (when not ordered)

  //print_vars($GLOBALS['cache']['entity_attribs']);
  foreach (dbFetchRows($sql, array($class, $device['device_id'], '0')) as $sensor_db)
  {
    $sensor_poll = array();

    //print_cli_heading("Sensor: ".$sensor_db['sensor_descr'], 3);

    // Sensor attribs, by first must be cached
    if (isset($GLOBALS['cache']['entity_attribs']['sensor'][$sensor_db['sensor_id']]))
    {
      $attribs = $GLOBALS['cache']['entity_attribs']['sensor'][$sensor_db['sensor_id']];
      //print_vars($attribs);
    } else {
      $attribs = array();
    }

    if (OBS_DEBUG)
    {
      echo("Checking (" . $sensor_db['poller_type'] . ") $class " . $sensor_db['sensor_descr'] . " ");
      print_debug_vars($sensor_db, 1);
    }

    if ($sensor_db['poller_type'] == "snmp")
    {
      $sensor_db['sensor_oid'] = '.' . ltrim($sensor_db['sensor_oid'], '.'); // Fix first dot in oid for caching

      // Why all temperature?
      if ($class == "temperature")
      {
        for ($i = 0; $i < 5; $i++) // Try 5 times to get a valid temp reading
        {
          // Take value from $oid_cache if we have it, else snmp_get it
          if (isset($oid_cache[$sensor_db['sensor_oid']]))
          {
            $oid_cache[$sensor_db['sensor_oid']] = snmp_fix_numeric($oid_cache[$sensor_db['sensor_oid']]);
          }
          if (is_numeric($oid_cache[$sensor_db['sensor_oid']]))
          {
            print_debug("value taken from oid_cache");
            $sensor_poll['sensor_value'] = $oid_cache[$sensor_db['sensor_oid']];
          } else {
            $sensor_poll['sensor_value'] = snmp_get_oid($device, $sensor_db['sensor_oid'], 'SNMPv2-MIB');
            $sensor_poll['sensor_value'] = snmp_fix_numeric($sensor_poll['sensor_value']);
          }

          if (is_numeric($sensor_poll['sensor_value']) && $sensor_poll['sensor_value'] != 9999)
          {
            break;
          } // Papouch TME sometimes sends 999.9 when it is right in the middle of an update;
          sleep(1); // Give the TME some time to reset
        }
        // If we received 999.9 degrees still, reset to Unknown.
        if ($sensor_poll['sensor_value'] == 9999)
        {
          $sensor_poll['sensor_value'] = "U";
        }
      }
      elseif ($class == "runtime")
      {
        if (isset($oid_cache[$sensor_db['sensor_oid']]))
        {
          print_debug("value taken from oid_cache");
          $sensor_poll['sensor_value'] = $oid_cache[$sensor_db['sensor_oid']];
        } else {
          $sensor_poll['sensor_value'] = snmp_get_oid($device, $sensor_db['sensor_oid'], 'SNMPv2-MIB');
        }
        if (strpos($sensor_poll['sensor_value'], ':') !== FALSE)
        {
          // Use timetick conversion only when snmpdata is formatted as timetick 0:0:21:00.00
          $sensor_poll['sensor_value'] = timeticks_to_sec($sensor_poll['sensor_value']);
        } else {
          $sensor_poll['sensor_value'] = snmp_fix_numeric($sensor_poll['sensor_value']);
        }
      } else {
        // Take value from $oid_cache if we have it, else snmp_get it
        if (isset($oid_cache[$sensor_db['sensor_oid']]))
        {
          $oid_cache[$sensor_db['sensor_oid']] = snmp_fix_numeric($oid_cache[$sensor_db['sensor_oid']]);
        }
        if (is_numeric($oid_cache[$sensor_db['sensor_oid']]))
        {
          print_debug("value taken from oid_cache");
          $sensor_poll['sensor_value'] = $oid_cache[$sensor_db['sensor_oid']];
        } else {
          $sensor_poll['sensor_value'] = snmp_get_oid($device, $sensor_db['sensor_oid'], 'SNMPv2-MIB');
          $sensor_poll['sensor_value'] = snmp_fix_numeric($sensor_poll['sensor_value']);
        }
      }
    }
    elseif ($sensor_db['poller_type'] == "agent")
    {
      if (isset($agent_sensors))
      {
        $sensor_poll['sensor_value'] = $agent_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['current'];
      } else {
        print_warning("No agent sensor data available.");
        continue;
      }
    }
    elseif ($sensor_db['poller_type'] == "ipmi")
    {
      if (isset($ipmi_sensors))
      {
        $sensor_poll['sensor_value'] = snmp_fix_numeric($ipmi_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['current']);
        $unit = $ipmi_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['unit'];
      } else {
        print_warning("No IPMI sensor data available.");
        continue;
      }
    } else {
      print_warning("Unknown sensor poller type.");
      continue;
    }

    $sensor_polled_time = time(); // Store polled time for current sensor

    print_debug_vars($sensor_poll, 1);

    if ($sensor_poll['sensor_value'] == -32768)
    {
      print_debug("Invalid (-32768) ");
      $sensor_poll['sensor_value'] = 0;
    }

    // Addition & Scale
    if (isset($attribs['sensor_addition']) && is_numeric($attribs['sensor_addition']))
    {
      $sensor_poll['sensor_value'] += $attribs['sensor_addition'];
    }

    //For Bluecoat multiplier may change very often - poll current data
    // FIXME. Convert to MIB definition
    if ($sensor_db['sensor_type'] == 'bluecoat-sg-proxy-mib')
    {
      //poll only once - using snmpwalk
      if (!isset($deviceSensorScale))
      {
        $deviceSensorScale = snmpwalk_cache_multi_oid($device, 'deviceSensorScale', array(), 'BLUECOAT-SG-SENSOR-MIB');
      }
      if (isset($deviceSensorScale[$sensor_db['sensor_index']]['deviceSensorScale']))
      {
        $sensor_db['sensor_multiplier'] = si_to_scale($deviceSensorScale[$sensor_db['sensor_index']]['deviceSensorScale']);
      }
    }

    if (isset($sensor_db['sensor_multiplier']) && $sensor_db['sensor_multiplier'] != 0)
    {
      //$sensor_poll['sensor_value'] *= $sensor_db['sensor_multiplier'];
      $sensor_poll['sensor_value'] = scale_value($sensor_poll['sensor_value'], $sensor_db['sensor_multiplier']);
    }

    // Unit conversion to SI (if required)
    $sensor_poll['sensor_value'] = value_to_si($sensor_poll['sensor_value'], $sensor_db['sensor_unit'], $class);

    //print_cli_data("Value", $sensor_poll['sensor_value'] . "$unit ", 3);

    // FIXME this block and the other block below it are kinda retarded. They should be merged and simplified.

    if ($sensor_db['sensor_disable'])
    {
      $sensor_poll['sensor_event'] = 'ignore';
      $sensor_poll['sensor_status'] = 'Sensor disabled.';
    } else {
      $sensor_poll['sensor_event'] = check_thresholds($sensor_db['sensor_limit_low'],  $sensor_db['sensor_limit_low_warn'],
                                                      $sensor_db['sensor_limit_warn'], $sensor_db['sensor_limit'],
                                                      $sensor_poll['sensor_value']);
      if ($sensor_poll['sensor_event'] == 'alert')
      {
        $sensor_poll['sensor_status'] = 'Sensor critical thresholds exceeded.';
      }
      elseif ($sensor_poll['sensor_event'] == 'warning')
      {
        $sensor_poll['sensor_status'] = 'Sensor warning thresholds exceeded.';
      } else {
        $sensor_poll['sensor_status'] = '';
      }

      // Reset Alert if sensor ignored
      if ($sensor_poll['sensor_event'] != 'ok' && $sensor_db['sensor_ignore'])
      {
        $sensor_poll['sensor_event'] = 'ignore';
        $sensor_poll['sensor_status'] = 'Sensor thresholds exceeded, but ignored.';
      }
    }

    // If last change never set, use current time
    if (empty($sensor_db['sensor_last_change']))
    {
      $sensor_db['sensor_last_change'] = $sensor_polled_time;
    }

    if ($sensor_poll['sensor_event'] != $sensor_db['sensor_event'])
    {
      // Sensor event changed, log and set sensor_last_change
      $sensor_poll['sensor_last_change'] = $sensor_polled_time;

      if ($sensor_db['sensor_event'] == 'ignore')
      {
        print_message("[%ySensor Ignored%n]", 'color');
      }
      elseif (is_numeric($sensor_db['sensor_limit_low']) &&
        $sensor_db['sensor_value'] >= $sensor_db['sensor_limit_low'] &&
        $sensor_poll['sensor_value'] < $sensor_db['sensor_limit_low'])
      {
        // If old value greater than low limit and new value less than low limit
        $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $sensor_db['sensor_descr'] . " is under threshold: " . $sensor_poll['sensor_value'] . "$unit (< " . $sensor_db['sensor_limit_low'] . "$unit)";
        log_event(ucfirst($class) . ' ' . $sensor_db['sensor_descr'] . " under threshold: " . $sensor_poll['sensor_value'] . " $unit (< " . $sensor_db['sensor_limit_low'] . " $unit)", $device, 'sensor', $sensor_db['sensor_id'], 'warning');
      }
      elseif (is_numeric($sensor_db['sensor_limit']) &&
        $sensor_db['sensor_value'] <= $sensor_db['sensor_limit'] &&
        $sensor_poll['sensor_value'] > $sensor_db['sensor_limit'])
      {
        // If old value less than high limit and new value greater than high limit
        $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $sensor_db['sensor_descr'] . " is over threshold: " . $sensor_poll['sensor_value'] . "$unit (> " . $sensor_db['sensor_limit'] . "$unit)";
        log_event(ucfirst($class) . ' ' . $sensor_db['sensor_descr'] . " above threshold: " . $sensor_poll['sensor_value'] . " $unit (> " . $sensor_db['sensor_limit'] . " $unit)", $device, 'sensor', $sensor_db['sensor_id'], 'warning');
      }
    } else {
      // If sensor not changed, leave old last_change
      $sensor_poll['sensor_last_change'] = $sensor_db['sensor_last_change'];
    }

    // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
    if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['sensors'])
    {
      $json_data = array('value' => $sensor_poll['sensor_value']);
      messagebus_send(array('attribs' => array('t'      => time(), 'device' => $device['hostname'], 'device_id' => $device['device_id'],
                                               'e_type' => 'sensor', 'e_class' => $sensor_db['sensor_class'], 'e_type' => $sensor_db['sensor_type'], 'e_index' => $sensor_db['sensor_index']), 'data' => $json_data));
    }

    // Add table row

    $table_rows[] = array($sensor_db['sensor_descr'], $sensor_db['sensor_class'], $sensor_db['sensor_type'], $sensor_db['poller_type'],
                          $sensor_poll['sensor_value'] . $unit, $sensor_poll['sensor_event'], format_unixtime($sensor_poll['sensor_last_change']));

    // Update StatsD/Carbon
    if ($config['statsd']['enable'] == TRUE)
    {
      StatsD::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'sensor' . '.' . $sensor_db['sensor_class'] . '.' . $sensor_db['sensor_type'] . '.' . $sensor_db['sensor_index'], $sensor_poll['sensor_value']);
    }

    // Update RRD - FIXME - can't convert to NG because filename is dynamic! new function should return index instead of filename.
    $rrd_file = get_sensor_rrd($device, $sensor_db);
    rrdtool_create($device, $rrd_file, "DS:sensor:GAUGE:600:-20000:U");
    rrdtool_update($device, $rrd_file, "N:" . $sensor_poll['sensor_value']);

    // Enable graph
    $graphs[$sensor_db['sensor_class']] = TRUE;

    // Check alerts
    $metrics = array();

    $metrics['sensor_value']  = $sensor_poll['sensor_value'];
    $metrics['sensor_event']  = $sensor_poll['sensor_event'];
    $metrics['sensor_event_uptime'] = $sensor_polled_time - $sensor_poll['sensor_last_change'];
    $metrics['sensor_status'] = $sensor_poll['sensor_status'];

    check_entity('sensor', $sensor_db, $metrics);

    // Add to MultiUpdate SQL State

    $GLOBALS['multi_update_db'][] = array(
      'sensor_id'          => $sensor_db['sensor_id'], // UNIQUE index
      'sensor_value'       => $sensor_poll['sensor_value'],
      'sensor_event'       => $sensor_poll['sensor_event'],
      'sensor_status'      => $sensor_poll['sensor_status'],
      'sensor_last_change' => $sensor_poll['sensor_last_change'],
      'sensor_polled'      => $sensor_polled_time);
    //dbUpdate(array('sensor_value'  => $sensor_poll['sensor_value'],
    //               'sensor_event'  => $sensor_poll['sensor_event'],
    //               'sensor_status' => $sensor_poll['sensor_status'],
    //               'sensor_last_change' => $sensor_poll['sensor_last_change'],
    //               'sensor_polled' => $sensor_polled_time),
    //         'sensors', '`sensor_id` = ?', array($sensor_db['sensor_id']));
  }
}

/**
 * Parse output of ipmitool sensor
 *
 * @param        $device
 * @param        $results
 * @param string $source
 *
 * @return mixed
 */
function parse_ipmitool_sensor($device, $results, $source = 'ipmi')
{
  global $valid, $config;

  $index = 0;

  $ipmi_sensors = [];
  foreach (explode("\n", $results) as $row)
  {
    $index++;

    # BB +1.1V IOH     | 1.089      | Volts      | ok    | na        | 1.027     | 1.054     | 1.146     | 1.177     | na
    list($desc,$current,$unit,$state,$low_nonrecoverable,$limit_low,$limit_low_warn,$limit_high_warn,$limit_high,$high_nonrecoverable) = explode('|',$row);

    // Trim values
    $current = trim($current);
    $state   = trim($state);
    $unit    = trim($unit);
    $desc    = trim($desc);
    if ($current != "na" && $state != "nr")
    {
      if (isset($config['ipmi_unit'][$unit]))
      {
        // Numeric sensors
        $class = $config['ipmi_unit'][$unit];
        $options = [];
        foreach (['limit_high', 'limit_low', 'limit_high_warn', 'limit_low_warn'] as $limit)
        {
          $limit_value = trim($$limit);
          if (is_numeric($limit_value))
          {
            $options[$limit] = $limit_value;
          }
        }
        if ($unit == 'CFM')
        {
          // Convert imperial airflow value to CMM
          //$options['sensor_unit'] = $unit;
        }
        discover_sensor($class, $device, '', $index, $source, $desc, 1, $current, $options, $source);

        $ipmi_sensors[$class][$source][$index] = ['description' => $desc, 'current' => $current, 'index' => $index, 'unit' => $unit];
      }
      elseif ($unit == 'discrete')
      {
        // Statuses
        # Intrusion        | 0x0        | discrete   | 0x0100| na        | na        | na        | na        | na        | na
        # Power Supply     | 0x0        | discrete   | 0x0000| na        | na        | na        | na        | na        | na
        $options = [];
        $ipmi_state = hexdec($state) + 0;

        if (str_istarts($desc, ['chassis', 'intrusion']))
        {
          // Chassis
          $options = array('entPhysicalClass' => 'chassis');
          $state = ($ipmi_state === 0x0000) ? 1 : 0;
        }
        elseif (str_istarts($desc, ['ps', 'power supply']))
        {
          // Power Supply
          $options = array('entPhysicalClass' => 'powersupply');
          $state = in_array($ipmi_state, [0x0100, 0x01ff]) ? 1 : 0;
        } else {
          // All others
          $options = array('entPhysicalClass' => 'other');
          $state = ($ipmi_state === 0x0000) ? 1 : 0;
        }

        discover_status($device, '', $index, 'unix-agent-state', $desc, $state, $options, $source);
        $ipmi_sensors['state']['unix-agent-state'][$index] = ['description' => $desc, 'current' => $state, 'index' => $index];
      }
    }
  }

  return $ipmi_sensors;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_sensor_rrd($device, $sensor)
{
  global $config;

  # For IPMI/agent, sensors tend to change order, and there is no index, so we prefer to use the description as key here.
  if ($config['os'][$device['os']]['sensor_descr'] || ($sensor['poller_type'] != "snmp" && $sensor['poller_type'] != ''))
  {
    $rrd_file = "sensor-".$sensor['sensor_class']."-".$sensor['sensor_type']."-".$sensor['sensor_descr'] . ".rrd";
  } else {
    // note, in discover_sensor_ng() sensor_type == %mib%-%object%
    $rrd_file = "sensor-".$sensor['sensor_class']."-".$sensor['sensor_type']."-".$sensor['sensor_index'] . ".rrd";
  }

  return($rrd_file);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_sensor_by_id($sensor_id)
{
  if (is_numeric($sensor_id))
  {
    $sensor = dbFetchRow("SELECT * FROM `sensors` WHERE `sensor_id` = ?", array($sensor_id));
  }
  if (is_array($sensor))
  {
    return $sensor;
  } else {
    return FALSE;
  }
}

// EOF
