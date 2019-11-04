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

$config['rrd_types']['smart'] = array(
  'file'  => 'app-smart-%index%.rrd',
  'ds'    => array(
    '1'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '2'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '3'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '4'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '5'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '6'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '7'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '8'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '9'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '10'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '11'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '12'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '13'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '22'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '170'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '171'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '172'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '173'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '174'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '175'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '176'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '177'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '179'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '180'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '181'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '182'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '183'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '184'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '185'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '186'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '187'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '188'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '189'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '190'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '191'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '192'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '193'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '194'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '195'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '196'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '197'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '198'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '199'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '200'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '201'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '202'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '203'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '204'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '205'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '206'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '207'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '208'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '209'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '210'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '211'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '212'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '220'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '221'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '223'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '224'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '225'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '226'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '227'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '228'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '230'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '231'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '232'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '233'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '234'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '235'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '240'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '241'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '242'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '243'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '249'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '250'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '251'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '252'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    '254'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
  ),
);

$config['rrd_types']['rrdcached'] = array(
  'file'  => 'app-rrdcached-%index%.rrd',
  'ds'    => array(
    'QueueLength'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'UpdatesReceived'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
    'FlushesReceived'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
    'UpdatesWritten'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
    'DataSetsWritten'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
    'TreeNodesNumber'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'TreeDepth'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'JournalBytes'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
    'JournalRotate'  => array('type' => 'COUNTER', 'min' => 0, 'max' => 125000000000),
  ),
);

$config['rrd_types']['phpfpm'] = array(
  'file'  => 'app-phpfpm-%index%.rrd',
  'ds'    => array(
    'lq'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'mlq' => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'ip'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'ap'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'tp'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'map' => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'mcr' => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'sr'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
  ),
);

$config['rrd_types']['fail2ban'] = array(
  'file'  => 'app-fail2ban-%index%.rrd',
  'ds'    => array(
    'sshd'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'dropbear'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
    'nginx'  => array('type' => 'GAUGE', 'min' => 0, 'max' => 125000000000),
  ),
);

// EOF
