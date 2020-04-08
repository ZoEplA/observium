<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (!is_array($vars['id'])) { $vars['id'] = array($vars['id']); }

$auth = TRUE;

foreach ($vars['id'] as $processor_id)
{
  if (!$auth && !is_entity_permitted('processor', $processor_id))
  $auth = FALSE;
}

$title = "Multi Processor :: ";

// EOF

