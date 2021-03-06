<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$row = 0;

if ($_GET['optc']) { $graph_type =  "atmvp_".$_GET['optc']; }
if (!$graph_type) { $graph_type = "atmvp_bits"; }

echo('<table cellspacing="0" cellpadding="5" border="0">');

foreach (dbFetchRows("SELECT * FROM juniAtmVp WHERE port_id = ?", array($interface['port_id'])) as $vp)
{
  if (is_integer($row/2)) { $row_colour = $list_colour_a; } else { $row_colour = $list_colour_b; }
  echo('<tr bgcolor="'.$row_colour.'">');
  echo('<td><span class=strong>'.$row.'. VP'.$vp['vp_id'].' '.$vp['vp_descr'].'</span></td>');
  echo('</tr>');

  $graph_array['height'] = "100";
  $graph_array['width']  = "214";
  $graph_array['to']     = $config['time']['now'];
  $graph_array['id']     = $vp['juniAtmVp_id'];
  $graph_array['type']   = $graph_type;

  $periods = array('day', 'week', 'month', 'year');

  echo('<tr bgcolor="'.$row_colour.'"><td>');

  foreach ($periods as $period)
  {
    $graph_array['from'] = $$period;
    $graph_array_zoom   = $graph_array; $graph_array_zoom['height'] = "150"; $graph_array_zoom['width'] = "400";
    echo(overlib_link("#", generate_graph_tag($graph_array), generate_graph_tag($graph_array_zoom),  NULL));
  }

  echo('</td></tr>');

  $row++;
}

echo('</table>');

// EOF
