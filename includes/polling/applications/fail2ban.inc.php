<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (!empty($agent_data['app']['fail2ban']))
{
  $fail2ban = $agent_data['app']['fail2ban'];

  $app_id = discover_app($device, 'fail2ban');

  list($sshd_banned_ip, $dropbear_banned_ip, $nginx_banned_ip) = explode("\n", $fail2ban);

  $data = array(
    'sshd' => $sshd_banned_ip,
    'dropbear' => $dropbear_banned_ip,
    'nginx' => $nginx_banned_ip,
  );

  rrdtool_update_ng($device, 'fail2ban', $data, $app_id);

  update_application($app_id, $data);

}

// EOF
