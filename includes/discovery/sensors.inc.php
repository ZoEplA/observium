<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$valid['sensor'] = array();
$valid['status'] = array();
$valid['counter'] = array();

// Sensor, Status and Counter entities are discovered together since they are often in the same MIBs.
//

// Run sensor discovery scripts (also discovers state sensors as status entities)
$include_dir = "includes/discovery/sensors";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

// Run status-specific discovery scripts
$include_dir = "includes/discovery/status";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

// Run counter-specific discovery scripts
$include_dir = "includes/discovery/counter";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

$cache_snmp = array();

// Detect sensors by definitions
foreach (get_device_mibs($device) as $mib)
{
  if (is_array($config['mibs'][$mib]['sensor']))
  {
    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['sensor'] as $oid => $oid_data)
    {

      print_cli($oid.' [');
      // Sensors with index specified
      foreach ($oid_data['indexes'] as $index => $entry)
      {
        // Check duplicate sensors by $valid['sensor'] array
        if (isset($entry['skip_if_valid_exist']) && $tree = explode('->', $entry['skip_if_valid_exist']))
        {
          //print_vars($tree);
          switch (count($tree))
          {
            case 1:
              if (isset($valid['sensor'][$tree[0]]) &&
                  count($valid['sensor'][$tree[0]])) { continue 2; }
              break;
            case 2:
              if (isset($valid['sensor'][$tree[0]][$tree[1]]) &&
                  count($valid['sensor'][$tree[0]][$tree[1]])) { continue 2; }
              break;
            case 3:
              if (isset($valid['sensor'][$tree[0]][$tree[1]][$tree[2]]) &&
                  count($valid['sensor'][$tree[0]][$tree[1]][$tree[2]])) { continue 2; }
              break;
            default:
              print_debug("Too many array levels for valid sensor!");
          }
        }

        //$entry['oid'] = $oid;
        if (empty($entry['oid_num']))
        {
          // Use snmptranslate if oid_num not set
          $entry['oid_num'] = snmp_translate($oid . '.' . $index, $mib);
        }

        $value = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_num']));
        if (is_numeric($value))
        {
          // Fetch description from oid if specified
          if (isset($entry['oid_descr']))
          {
            $descr = snmp_get_oid($device, $entry['oid_descr'], $mib);
            if (isset($entry['descr']) && str_contains($entry['descr'], '%oid_descr%'))
            {
              // If descr definition have this magic key, use combination of static 'descr' and named 'descr' from oid
              $descr = array_tag_replace(array('oid_descr' => $descr), $entry['descr']);
            }
            $entry['descr'] = $descr;
          }
          $entry['type'] = $mib . '-' . $oid;

          // Check for min/max values, when sensors report invalid data as sensor does not exist
          if ((isset($entry['min']) && $value <= $entry['min']) ||
              (isset($entry['max']) && $value >= $entry['max'])) { continue; }

          $options = array();

          // Unit
          if (isset($entry['unit'])) { $options['sensor_unit'] = $entry['unit']; }
          if (isset($entry['oid_unit']))
          {
            // Fetch and translate unit from specific Oid
            $unit = snmp_get_oid($device, $entry['oid_unit'], $mib, NULL, OBS_SNMP_ALL_ENUM);
            if (isset($entry['map_unit'][$unit]))
            {
              $options['sensor_unit'] = $entry['map_unit'][$unit];
            }
            unset($unit);
          }
          // Scale
          if (isset($entry['oid_scale']))
          {
            // Fetch and translate scale from specific Oid
            $scale = snmp_get_oid($device, $entry['oid_scale'], $mib, NULL, OBS_SNMP_ALL_ENUM);
            if (isset($entry['map_scale']))
            {
              $entry['scale'] = $entry['map_scale'][$scale];
            }
            else if (is_numeric($scale))
            {
              $entry['scale'] = $scale;
            }
            unset($scale);
          }
          if (!isset($entry['scale'])) { $entry['scale'] = 1; }
          if ($entry['limit_scale'] === 'scale')
          {
            // If limit_scale equals to 'scale' - copy main scale for limits
            $entry['limit_scale'] = $entry['scale'];
          }

          // Check limits oids if set
          foreach (array('limit_low', 'limit_low_warn', 'limit_high_warn', 'limit_high') as $limit)
          {
            $oid_limit = 'oid_'   . $limit;
            if (isset($entry[$oid_limit]))
            {
              // Get limit from OID
              $options[$limit] = snmp_fix_numeric(snmp_get_oid($device, $entry[$oid_limit], $mib));
              // Scale limit
              if (isset($entry['limit_scale']) && is_numeric($entry['limit_scale']) && $entry['limit_scale'] != 0)
              {
                $options[$limit] *= $entry['limit_scale'];
              }
            }
            else if (isset($entry[$limit]) && is_numeric($entry[$limit]))
            {
              // Limit from definition
              $options[$limit] = $entry[$limit];
            }
          }

          // Rename old (converted) RRDs to definition format
          if (isset($entry['rename_rrd']))
          {
            $old_rrd = 'sensor-'.$entry['class'].'-'.$entry['rename_rrd'];
            $new_rrd = 'sensor-'.$entry['class'].'-'.$entry['type'].'-'.$index;
            rename_rrd($device, $old_rrd, $new_rrd);
            unset($old_rrd, $new_rrd);
          }
          else if (isset($entry['rename_rrd_array']) && is_array($entry['rename_rrd_array']))
          {
            $old_rrd_array = $entry['rename_rrd_array'];
            if (!isset($old_rrd_array['class'])) { $old_rrd_array['class'] = $entry['class']; }
            if (!isset($old_rrd_array['descr'])) { $old_rrd_array['descr'] = $entry['descr']; }
            if (!isset($old_rrd_array['index'])) { $old_rrd_array['index'] = $index; }
            rename_rrd_entity($device, 'sensor', $old_rrd_array, // old
                                                 array('descr' => $entry['descr'], 'class' => $entry['class'], 'index' => $index, 'type' => $entry['type'])); // new
          }
          discover_sensor($valid['sensor'], $entry['class'], $device, $entry['oid_num'], $index, $entry['type'], $entry['descr'], $entry['scale'], $value, $options);
        }
      }

      // Enable caching walk, if multiple OIDs in same table (for both sensor and status)
      $cache_snmp_enable = (count($oid_data['tables']) + count($config['mibs'][$mib]['status'][$oid]['tables'])) > 1;

      // Sensors walked by table
      foreach ($oid_data['tables'] as $entry)
      {
        // Check duplicate sensors by $valid['sensor'] array
        if (isset($entry['skip_if_valid_exist']) && $tree = explode('->', $entry['skip_if_valid_exist']))
        {
          switch (count($tree))
          {
            case 1:
              if (isset($valid['sensor'][$tree[0]]) &&
                  count($valid['sensor'][$tree[0]])) { continue 2; }
              break;
            case 2:
              if (isset($valid['sensor'][$tree[0]][$tree[1]]) &&
                  count($valid['sensor'][$tree[0]][$tree[1]])) { continue 2; }
              break;
            case 3:
              if (isset($valid['sensor'][$tree[0]][$tree[1]][$tree[2]]) &&
                  count($valid['sensor'][$tree[0]][$tree[1]][$tree[2]])) { continue 2; }
              break;
            default:
              print_debug("Too many array levels for valid sensor!");
          }
        }

        $table = isset($entry['table']) ? $entry['table'] : $oid; // Table oid
        // Walk table or oids
        $sensor_array = array();
        if (isset($entry['table_walk']) && $entry['table_walk'] == FALSE)
        {
          // Walk by oids separately
          $table_oids = array('oid', 'oid_descr', 'oid_scale', 'oid_unit',
                              'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high',
                              'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale');
          foreach ($table_oids as $table_oid)
          {
            if (isset($entry[$table_oid]))
            {
              $sensor_array = snmpwalk_cache_oid($device, $table_oid, $sensor_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX | OBS_SNMP_DISPLAY_HINT);
            }
          }
        } else {
          // Walk by table
          if (isset($cache_snmp[$mib][$table]) && is_array($cache_snmp[$mib][$table]))
          {
            print_debug("Get cached Table OID: $mib::$table");
            $sensor_array = $cache_snmp[$mib][$table];
          } else {
            $sensor_array = snmpwalk_cache_oid($device, $table, $sensor_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX | OBS_SNMP_DISPLAY_HINT);
          }
          if ($cache_snmp_enable && !isset($cache_snmp[$mib][$table]))
          {
            print_debug("Store in cache Table OID: $mib::$table");
            $cache_snmp[$mib][$table] = $sensor_array;
          }
        }

        if (empty($entry['oid_num']))
        {
          // Use snmptranslate if oid_num not set
          $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
        }

        $entry['type'] = $mib . '-' . $entry['oid'];
        if (!isset($entry['scale'])) { $entry['scale'] = 1; }

        $counters = array(); // Reset per-class counters for each MIB

        foreach ($sensor_array as $index => $sensor)
        {
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

          // FIXME - this stuff is messy. Things are rewritten silently and it's a pain to work out why.
          // Setting descr to oid_descr should be the last resort, not the first attempt
          // Randomly shoving index on the end is really confusing too

          // Quick attempt at making the structure more logical, needs ifs restructuring

          $dot_index = '.' . $index;
          $oid_num   = $entry['oid_num'] . $dot_index;
          if (!isset($entry['descr']) && $entry['oid_descr'] && $sensor[$entry['oid_descr']])
          {
            $descr = $sensor[$entry['oid_descr']];
            if (isset($entry['descr']) && str_contains($entry['descr'], '%oid_descr%'))
            {
              // If descr definition have this magic key, use combination of static 'descr' and named 'descr' from oid
              $descr = array_tag_replace(array('oid_descr' => $descr), $entry['descr']);
            }
          }
          elseif (isset($entry['descr']))
          {
            $descr = $entry['descr'];
          } else {
            $descr = 'Sensor ' . $index;
          }

          // echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($sensor); echo PHP_EOL; print_vars($descr); echo PHP_EOL;


          // %i% can be used in description, a counter is kept per sensor class
          $counters[$class]++;

          // Rewrite specific keys
          if (str_contains($descr, array('%i%', '%index')) || TRUE)
          {
            $replace_array = array(
              'oid_descr' => $sensor[$entry['oid_descr']],
              'class' => nicecase($class),  // Class in descr
              'index' => $index,            // Index in descr
              'i'     => $counters[$class], // i++ counter in descr (per sensor class)
            );
            // Multipart index: Oid.0.1.2 -> %index0%, %index1%, %index2%
            if (preg_match('/%index\d+%/', $entry['descr']))
            {
              // Multipart index
              foreach (explode('.', $index) as $k => $k_index)
              {
                $replace_array['index'.$k] = $k_index; // Multipart indexes
              }
            }
            $descr = array_tag_replace($replace_array, $descr);
          }

          $value = snmp_fix_numeric($sensor[$entry['oid']]);
          if (is_numeric($value))
          {
            // Check for min/max values, when sensors report invalid data as sensor does not exist
            if ((isset($entry['min']) && $value <= $entry['min']) ||
                (isset($entry['max']) && $value >= $entry['max'])) { continue; }

            $options = array();
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
              else if (isset($entry[$limit]) && is_numeric($entry[$limit]))
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

            // Unit
            if (isset($entry['unit'])) { $options['sensor_unit'] = $entry['unit']; }
            if (isset($entry['oid_unit']) && isset($sensor[$entry['oid_unit']]))
            {
              // Translate unit from specific Oid
              $unit = $sensor[$entry['oid_unit']];
              if (isset($entry['map_unit'][$unit]))
              {
                $options['sensor_unit'] = $entry['map_unit'][$unit];
              }
            }

            // Measured entity
            if (isset($entry['measured_entity_by']))
            {
              switch ($entry['measured'])
              {
                case 'port':
                  // Example in MIKROTIK-MIB
                  $options['measured_class']   = 'port';
                  $options['entPhysicalIndex'] = $index;
                  if ($entry['measured_entity_by'] == 'ifDescr')
                  {
                    $entity_name = array_tag_replace(array('oid_descr' => $sensor[$entry['oid_descr']]), $entry['measured_entity']);
                    //$entity_id   = get_port_id_by_ifDescr($device['device_id'], $entity_name);
                    if ($entity = dbFetchRow("SELECT `port_id`, `ifIndex` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` = ? OR `ifName` = ?) AND `deleted` = ? LIMIT 1", array($device['device_id'], $entity_name, $entity_name, 0)))
                    {
                      $options['measured_entity']           = $entity['port_id'];
                      $options['entPhysicalIndex_measured'] = $entity['ifIndex'];
                    }
                  }
                  break;
                case 'ifIndex':
                case 'index':
                  // FIXME need more examples
                  break;
              }
            }

            // Rename old (converted) RRDs to definition format
            if (isset($entry['rename_rrd']))
            {
              $entry['rename_rrd'] = array_tag_replace(array('index' => $index), $entry['rename_rrd']); // %index% >> $index
              $old_rrd = 'sensor-'.$entry['class'].'-'.$entry['rename_rrd'];
              $new_rrd = 'sensor-'.$entry['class'].'-'.$entry['type'].'-'.$index;
              rename_rrd($device, $old_rrd, $new_rrd);
              unset($old_rrd, $new_rrd);
            }
            else if (isset($entry['rename_rrd_array']) && is_array($entry['rename_rrd_array']))
            {
              $old_rrd_array = $entry['rename_rrd_array'];
              if (!isset($old_rrd_array['class'])) { $old_rrd_array['class'] = $entry['class']; }
              if (!isset($old_rrd_array['descr'])) { $old_rrd_array['descr'] = $entry['descr']; }
              if (!isset($old_rrd_array['index'])) { $old_rrd_array['index'] = $index; }
              foreach (array('descr', 'index') as $param)
              {
                $replace_array         = array('index' => $index, 'descr' => $descr);
                $old_rrd_array[$param] = array_tag_replace($replace_array, $old_rrd_array[$param]);
              }
              rename_rrd_entity($device, 'sensor', $old_rrd_array, // old
                                                   array('descr' => $entry['descr'], 'class' => $entry['class'], 'index' => $index, 'type' => $entry['type'])); // new
              unset($old_rrd_array);
            }

            discover_sensor($valid['sensor'], $class, $device, $oid_num, $index, $entry['type'], $descr, $entry['scale'], $value, $options);
          }
        }

      }

      print_cli('] ');
    }
    print_cli(PHP_EOL);
  }
}

// Detect Status by simple MIB-based discovery :
foreach (get_device_mibs($device) as $mib)
{
  if (is_array($config['mibs'][$mib]['status']))
  {
    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['status'] as $oid => $oid_data)
    {
      print_cli($oid.' [');
      // Statuses with index specified
      foreach ($oid_data['indexes'] as $index => $entry)
      {
        // Check duplicate statuses by $valid['status'] array
        if (isset($entry['skip_if_valid_exist']) && $tree = explode('->', $entry['skip_if_valid_exist']))
        {
          //print_vars($tree);
          switch (count($tree))
          {
            case 1:
              if (isset($valid['status'][$tree[0]]) &&
                  count($valid['status'][$tree[0]])) { continue 2; }
              break;
            case 2:
              if (isset($valid['status'][$tree[0]][$tree[1]]) &&
                  count($valid['status'][$tree[0]][$tree[1]])) { continue 2; }
              break;
            case 3:
              if (isset($valid['status'][$tree[0]][$tree[1]][$tree[2]]) &&
                  count($valid['status'][$tree[0]][$tree[1]][$tree[2]])) { continue 2; }
              break;
            default:
              print_debug("Too many array levels for valid status!");
          }
        }

        $entry['oid'] = $oid;
        if (empty($entry['oid_num']))
        {
          // Use snmptranslate if oid_num not set
          $entry['oid_num'] = snmp_translate($oid . '.' . $index, $mib);
        }

        $value = snmp_get_oid($device, $entry['oid_num'], $mib, NULL, OBS_SNMP_ALL_ENUM);
        if (is_numeric($value))
        {
          // Fetch description from oid if specified
          if (isset($entry['oid_descr']))
          {
            $descr = snmp_get_oid($device, $entry['oid_descr'], $mib);
            if (isset($entry['descr']) && str_contains($entry['descr'], '%oid_descr%'))
            {
              // If descr definition have this magic key, use combination of static 'descr' and named 'descr' from oid
              $descr = array_tag_replace(array('oid_descr' => $descr), $entry['descr']);
            }
            $entry['descr'] = $descr;
          }

          $options = array('entPhysicalClass' => $entry['measured']);

          // Definition based events
          if (isset($entry['oid_map']))
          {
            $options['status_map'] = snmp_get_oid($device, $entry['oid_map'], $mib);
          }

          // Rename old (converted) RRDs to definition format
          if (isset($entry['rename_rrd']))
          {
            $old_rrd = 'status-'.$entry['rename_rrd'];
            $new_rrd = 'status-'.$entry['type'].'-'."$oid.$index";
            rename_rrd($device, $old_rrd, $new_rrd);
            unset($old_rrd, $new_rrd);
          }
          else if (isset($entry['rename_rrd_array']) && is_array($entry['rename_rrd_array']))
          {
            $old_rrd_array = $entry['rename_rrd_array'];
            if (!isset($old_rrd_array['type']))  { $old_rrd_array['type']  = $entry['type']; }
            if (!isset($old_rrd_array['descr'])) { $old_rrd_array['descr'] = $entry['descr']; }
            if (!isset($old_rrd_array['index'])) { $old_rrd_array['index'] = "$oid.$index"; }
            rename_rrd_entity($device, 'status', $old_rrd_array, // old
                                                 array('descr' => $entry['descr'], 'index' => "$oid.$index", 'type' => $entry['type'])); // new
            unset($old_rrd_array);
          } else {
            rename_rrd($device, "status-".$entry['type'].'-'.$index, "status-".$entry['type'].'-'."$oid.$index");
          }

          discover_status($device, $entry['oid_num'], "$oid.$index", $entry['type'], $entry['descr'], $value, $options);
        }
      }

      // Enable caching walk, if multiple OIDs in same table (for both sensor and status)
      $cache_snmp_enable = count($oid_data['tables']) > 1;

      // Statuses walked by table
      foreach ($oid_data['tables'] as $entry)
      {
        // Check duplicate statuses by $valid['status'] array
        if (isset($entry['skip_if_valid_exist']) && $tree = explode('->', $entry['skip_if_valid_exist']))
        {
          switch (count($tree))
          {
            case 1:
              if (isset($valid['status'][$tree[0]]) &&
                  count($valid['status'][$tree[0]])) { continue 2; }
              break;
            case 2:
              if (isset($valid['status'][$tree[0]][$tree[1]]) &&
                  count($valid['status'][$tree[0]][$tree[1]])) { continue 2; }
              break;
            case 3:
              if (isset($valid['status'][$tree[0]][$tree[1]][$tree[2]]) &&
                  count($valid['status'][$tree[0]][$tree[1]][$tree[2]])) { continue 2; }
              break;
            default:
              print_debug("Too many array levels for valid status!");
          }
        }

        $table = isset($entry['table']) ? $entry['table'] : $oid; // Table oid
        // Walk table or oids
        $status_array = array();
        if (isset($entry['table_walk']) && $entry['table_walk'] == FALSE)
        {
          // Walk by oids separately
          $table_oids = array('oid', 'oid_descr', 'oid_map');
          foreach ($table_oids as $table_oid)
          {
            if (isset($entry[$table_oid]))
            {
              $status_array = snmpwalk_cache_oid($device, $table_oid, $status_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            }
          }
        } else {
          // Walk by table
          if (isset($cache_snmp[$mib][$table]) && is_array($cache_snmp[$mib][$table]))
          {
            print_debug("Get cached Table OID: $mib::$table");
            $status_array = $cache_snmp[$mib][$table];
          } else {
            $status_array = snmpwalk_cache_oid($device, $table, $status_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
          }
          if ($cache_snmp_enable && !isset($cache_snmp[$mib][$table]))
          {
            print_debug("Store in cache Table OID: $mib::$table");
            $cache_snmp[$mib][$table] = $status_array;
          }
        }

        if (!isset($entry['oid']))
        {
          $entry['oid'] = $table;
        }

        if (empty($entry['oid_num']))
        {
          // Use snmptranslate if oid_num not set
          $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
        }

        $i = 1; // Used in descr as $i++
        foreach ($status_array as $index => $status)
        {
          // FIXME - this stuff is messy. Things are rewritten silently and it's a pain to work out why.
          // Setting descr to oid_descr should be the last resort, not the first attempt
          // Randomly shoving index on the end is really confusing too

          // Quick attempt at making the structure more logical, needs ifs restructuring

          $dot_index = '.' . $index;
          $oid_num   = $entry['oid_num'] . $dot_index;
          if (!isset($entry['descr']) && $entry['oid_descr'] && $status[$entry['oid_descr']])
          {
            $descr = $status[$entry['oid_descr']];
            if (isset($entry['descr']) && str_contains($entry['descr'], '%oid_descr%'))
            {
              // If descr definition have this magic key, use combination of static 'descr' and named 'descr' from oid
              $descr = array_tag_replace(array('oid_descr' => $descr), $entry['descr']);
            }
          }
          elseif (isset($entry['descr']))
          {
            $descr = $entry['descr'];
          } else {
            $descr = 'Status ' . $index;
          }

          //echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($status); echo PHP_EOL; print_vars($descr); echo PHP_EOL;


          // %i% can be used in description
          $i++;

          // Rewrite specific keys
          if (str_contains($descr, array('%i%', '%index')) || TRUE)
          {
            $replace_array = array(
              'oid_descr' => $status[$entry['oid_descr']],
              'class' => nicecase($class),  // Class in descr
              'index' => $index,            // Index in descr
              'i'     => $i, // i++ counter in descr
            );
            // Multipart index: Oid.0.1.2 -> %index0%, %index1%, %index2%
            if (preg_match('/%index\d+%/', $entry['descr']))
            {
              // Multipart index
              foreach (explode('.', $index) as $k => $k_index)
              {
                $replace_array['index'.$k] = $k_index; // Multipart indexes
              }
            }
            $descr = array_tag_replace($replace_array, $descr);
          }

          $value = $status[$entry['oid']];
          if (strlen($value))
          {
            $options = array('entPhysicalClass' => $entry['measured']);

            // Definition based events
            if (isset($entry['oid_map']) && $status[$entry['oid_map']])
            {
              $options['status_map'] = $status[$entry['oid_map']];
            }

            // Rename old (converted) RRDs to definition format
            if (isset($entry['rename_rrd']))
            {
              $entry['rename_rrd'] = array_tag_replace(array('index' => $index), $entry['rename_rrd']);
              $old_rrd = 'status-'.$entry['rename_rrd'];
              $new_rrd = 'status-'.$entry['type'].'-'.$entry['oid'].$dot_index;
              rename_rrd($device, $old_rrd, $new_rrd);
              unset($old_rrd, $new_rrd);
            }
            else if (isset($entry['rename_rrd_array']) && is_array($entry['rename_rrd_array']))
            {
              $old_rrd_array = $entry['rename_rrd_array'];
              if (!isset($old_rrd_array['type']))  { $old_rrd_array['type']  = $entry['type']; }
              if (!isset($old_rrd_array['descr'])) { $old_rrd_array['descr'] = $entry['descr']; }
              if (!isset($old_rrd_array['index'])) { $old_rrd_array['index'] = $entry['oid'].$dot_index; }
              foreach (array('descr', 'index') as $param)
              {
                $replace_array         = array('index' => $index, 'descr' => $descr);
                $old_rrd_array[$param] = array_tag_replace($replace_array, $old_rrd_array[$param]);
              }
              rename_rrd_entity($device, 'status', $old_rrd_array, // old
                                                   array('descr' => $entry['descr'], 'index' => $entry['oid'].$dot_index, 'type' => $entry['type'])); // new
              unset($old_rrd_array);
            } else {
              rename_rrd($device, "status-".$entry['type'].'-'.$index, "status-".$entry['type'].'-'.$entry['oid'].$dot_index);
            }

            discover_status($device, $oid_num, $entry['oid'].$dot_index, $entry['type'], $descr, $value, $options);
          }
        }
      }

      print_cli('] ');
    }
    print_cli('] ');
  }
}

// Clean
unset($cache_snmp, $cache_snmp_enable);

print_debug_vars($valid['sensor']);
foreach (array_keys($config['sensor_types']) as $type)
{
  check_valid_sensors($device, $type, $valid['sensor']);
}

print_debug_vars($valid['status']);
check_valid_status($device, $GLOBALS['valid']['status']);

echo(PHP_EOL);

// EOF
