<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 */

// This file contains definitions for our RRD files.
// There is no real intersection with the graph_types definitions, because multiple graph types can 
// reference the same physical file, and the same DSes can sometimes be drawn in different ways.

// Available "ds" attributes: type (GAUGE/COUNTER/DERIVE, default COUNTER), heartbeat (default step*2), min (default U), max (default U)

// FIXME the "graphs" (table-based) poller (collect_table) has duplication of both the definition array and the RRD creation code, but it's heavily intertwined
// with SNMP polling code, OID renames, etc. Will be separated out a bit so RRD functions are in rrdtool_* functions only.
// RRD data structure from collect_table should be passed as array (structured like below) when calling rrdtool_update_ng() 
// Advanced RRD functionality from collect_table should be merged into rrdtool_create_ng().

// Do *NOT* change RRD DS names after the fact, that will break all existing RRD files' graphs.

// HTTP Live Streaming (HLS)

$config['rrd_types']['hls'] = array(
  'file'  => 'app-hls-%index%.rrd',
  'ds'    => array(
    'bitrate_video'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'bitrate_audio'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
  ),
);

// EOF
