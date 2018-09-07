<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$config['install_dir'] = "../..";

include_once("../../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    echo("unauthenticated");
    exit;
}

include_dir($config['html_dir'] . "/includes/widgets/");


$widget = dbFetchRow("SELECT * FROM `dash_widgets` WHERE widget_id = ?", array($_POST['id']));

$widget['height'] = (is_numeric($_POST['height']) ? $_POST['height'] : '3');
$widget['width'] = (is_numeric($_POST['width']) ? $_POST['width'] : '4');

print_dash_mod($widget);

function print_dash_graph($mod, $width, $height)
{
  global $config;

  $vars = $mod['vars'];

  if(!isset($vars['type']))
  {

      echo '<div style="position: relative;  top: 50%;  transform: perspective(1px) translateY(-50%); width: 100%; text-align: center;">
            <btn class="btn btn-primary" onclick="configWidget('.$mod['widget_id'].')"><i class="icon-signal"/> &nbsp; Select Graph</btn>
            </div>';

      exit();
  }



  $timestamp_pattern = '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/';
  if (isset($vars['timestamp_from']) && preg_match($timestamp_pattern, $vars['timestamp_from']))
  {
    $vars['from'] = strtotime($vars['timestamp_from']);
    unset($vars['timestamp_from']);
  }
  if (isset($vars['timestamp_to'])   && preg_match($timestamp_pattern, $vars['timestamp_to']))
  {
    $vars['to'] = strtotime($vars['timestamp_to']);
    unset($vars['timestamp_to']);
  }
  if (isset($vars['period']))
  {
      $vars['to'] = $config['time']['now'];
      $vars['from'] = $vars['to'] - $vars['period'];
  }


  if (!is_numeric($vars['from'])) { $vars['from'] = $config['time']['day']; }
  if (!is_numeric($vars['to']))   { $vars['to']   = $config['time']['now']; }

  preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>.+)/', $vars['type'], $graphtype);

  if (OBS_DEBUG) { print_vars($graphtype); }

  $type = $graphtype['type'];
  $subtype = $graphtype['subtype'];

  if (is_numeric($vars['device']))
  {
    $device = device_by_id_cache($vars['device']);
  } elseif (!empty($vars['device'])) {
    $device = device_by_name($vars['device']);
  }

  if (is_file($config['html_dir']."/includes/graphs/".$type."/auth.inc.php"))
  {
    include($config['html_dir']."/includes/graphs/".$type."/auth.inc.php");
  }

  if (!$auth)
  {
    print_error_permission();
    return;
  }

   /**
    $entity = get_entity_by_id_cache($vars['entity_type'], $vars['entity_id']);

    $device = device_by_id_cache($entity['device_id']);
    entity_rewrite($vars['entity_type'], $entity);

    $graph_array['type'] = $vars['entity_type'] . '_' . $vars['graph_type'];
    $graph_array['id'] = $vars['entity_id'];
    **/


    // Generate navbar with subtypes
    $graph_array = $vars;

    //$graph_array['from'] = '-1day';
    //$graph_array['to'] = 'now';

    $graph_array['width'] = $width - 76;                            // RRD graphs are 75px wider than request value
    $graph_array['height'] = $height - 34; //68;                          // RRD graphs are taller than request value

    if ($graph_array['width'] > 350) {
        $graph_array['width'] -= 6;
    } // RRD graphs > 350px are 6 px wider because of larger legend font
    if ($graph_array['width'] > 350) {
        $graph_array['height'] -= 6;
    } // RRD graphs > 350px are 6 px wider because of larger legend font

    $d = 'top:0px; left: 0px; padding: 4px; border-top-left-radius: 4px; border: 1px solid #e5e5e5; border-left: none; border-top: none;  background: white;';

    if ($height < 170) {
        $graph_array['height'] = $height;
        $graph_array['width'] = $width;

        $d = 'top:5px; left: 5px; padding: none; border-radius: 2px; border: 1px solid #e5e5e5; background: rgba(255, 255, 255, 0.7);;';

    }

    $graph_array['img_id'] = generate_random_string(5);
    $graph_array['legend'] = 'no';

    $graph = generate_graph_tag($graph_array, TRUE);

    $t_len = $vars['width'] / 10;

    //echo '    <div class="box-header with-border">' . $device['hostname'] . '<span class="pull-right">' . truncate($entity['entity_name'], 32) . '</span></div>';

    echo '  <div class="hover-hide" style="z-index: 900; position: absolute; ' . $d . '"><h4>' . short_hostname($device['hostname'], $t_len / 2 - 2) . ($type != "device" ? ' :: ' . truncate($entity['entity_name'], 32) : ' :: ' . $subtype) . '</div>'.PHP_EOL;

    echo '  <div class="box-content">';
    echo $graph['img_tag'];
    echo '  </div>';


    // Auto refresh the image every 30 seconds
    echo '
    <script>
    $(function() {
        url' . $graph['img_id'] . ' = $("#' . $graph['img_id'] . '").prop("src");
      setInterval(function() {
        $("#' . $graph['img_id'] . '").prop("src", url' . $graph['img_id'] . ' + "&nocache=" + new Date());
      }, 30000);
    });
    </script>
    ';


}

function print_dash_mod($mod)
{

    global $config;
    global $cache;

    $mod['vars'] = json_decode($mod['widget_config'], TRUE);

    $width = (is_numeric($mod['width']) ? $mod['width'] : 1240);
    $height = (is_numeric($mod['height']) ? $mod['height'] : 80);

    switch ($mod['widget_type']) {

        case "map":
            print_dash_map($mod['vars'], $width, $height);
            break;

        case "graph":
            print_dash_graph($mod, $width, $height);
            break;

      case "port_percent":
        include($config['html_dir']."/includes/status-portpercent.inc.php");
        break;

      case "alert_table":

        print_alert_table(array('status' => 'failed', 'pagination' => FALSE, 'short' => TRUE,
                                'header' => array('title' => 'Current Alerts',
                                                  'url' => '/alerts/'
                                )
        ));
        break;

      case "status_summary":
        include($config['html_dir']."/includes/status-summary.inc.php");
        break;

      case "status_donuts":
        include($config['html_dir']."/includes/status-donuts.inc.php");
        break;

      case "syslog":
            echo '  <div class="box box-solid gridstack-update" style="overflow: hidden;">';
            echo '    <div class="box-header" style="cursor: hand;"><h4>Syslog</h3></div>';
            echo '    <div class="box-content" style="overflow: hidden;">';
            print_syslogs(array('short' => TRUE, 'pagesize' => ($height - 36) / 26));
            echo '  </div>';
            echo '</div>';
            break;

      case "alertlog":
        echo '  <div class="box box-solid gridstack-update" style="overflow: hidden;">';
        echo '    <div class="box-header" style="cursor: hand;"><h4>Alert Log</h3></div>';
        echo '    <div class="box-content" style="overflow: hidden;">';
        print_alert_log_short(array('short' => TRUE, 'pagesize' => ($height - 36) / 26));
        echo '  </div>';
        echo '</div>';
        break;

        case "eventlog":
            echo '  <div class="box box-solid gridstack-update" style="overflow: hidden;">';
            echo '    <div class="box-header" style="cursor: hand;"><h4>Eventlog</h3></div>';
            echo '    <div class="box-content" style="overflow: hidden;">';
            print_events(array('short' => TRUE, 'pagesize' => ($height - 36) / 26));
            echo '  </div>';
            echo '</div>';
            break;



        case "realtime":
            echo '  <div class="box box-solid gridstack-update" style="overflow: hidden;">';
            $realtime_link = 'graph-realtime.php?type=bits&amp;id=194467&amp;interval=10';

            ?>
            <object data="<?php echo($realtime_link); ?>" type="image/svg+xml" width="<?php echo $width; ?>"
                    height="<?php echo $height; ?>">
                <param name="src"
                       value="graph.php?type=bits&amp;id=<?php echo($port['port_id'] . "&amp;interval=" . $vars['interval']); ?>"/>
                Your browser does not support SVG! You need to either use Firefox or Chrome, or download the Adobe SVG
                plugin.
            </object>
            <?php
            echo '</div>';


        default:
            echo '<div class="grid-stack-item-content box box-solid" style="overflow: hidden; justify-content: center; align-items: center;">';
            echo '  <div class="box-content" style="overflow: hidden;">';
            echo '    <h4>Unconfigured Module</h4>';
            echo '  </div>';
            echo '</div>';
            break;
    }

    //echo '</div>';

}

function print_dash_map($vars, $width, $height)
{

    global $config;

    ?>
    <style type="text/css">
        #map label {
            width: auto;
            display: inline;
        }

        #map img {
            max-width: none;
        }

        #map {
            height: 100%;
            width: 100%;
        }
    </style>

    <?php

    echo '<div id="map"></div>';

    // Map API and keys
    $map_default = 'leaflet'; // Default
    switch ($config['frontpage']['map']['api']) {
        case 'google-mc':
        case 'google':
            // Check if key exist
            if (strlen($config['remote_api']['maps']['google']['key'])) {
                $map = $config['frontpage']['map']['api'];
            }
            break;
        case 'mapbox':
            // Check if key exist
            if (!strlen($config['remote_api']['maps']['mapbox']['key'])) {
                // If api key not set, reset to default map provider
                $config['frontpage']['map']['api'] = 'carto';
            }
            break;

        case 'carto':
        default:
            //$map = 'leaflet';
            break;
    }

    if (isset($map) && is_file($config['html_dir'] . "/includes/map/$map.inc.php")) {
        include($config['html_dir'] . "/includes/map/$map.inc.php");
    } else {
        include($config['html_dir'] . "/includes/map/$map_default.inc.php");
        //print_error("Unknown map type: $map");
    }

} // End show_map


?>
