<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2016 Observium Limited
 *
 */

// FIXME. From this uses only check_valid_sensors(), maybe need move to global functions or copy to polling. --mike
// Also uses check_valid_virtual_machines(). We (should) do a lot more discovery through the agent, IMO we should do away with the distinction. --tom
include_once("includes/discovery/functions.inc.php");

//define("NET_SSH2_LOGGING", 2);
//define("LOG_REALTIME_FILENAME", "/opt/observium/logs/sshdebug.".$device['hostname'].".log");

global $valid, $agent_sensors;

if ($device['os_group'] == "unix")
{
  echo("Observium UNIX Agent: ");

  // Use port configured in config (or defaults)
  $agent_port = $config['unix-agent']['port'];

  // ... Unless user configured a port for this specific device, and it's valid (numeric and within 16-bit port range)
  $override_port = get_dev_attrib($device, 'agent_port');

  if (is_numeric($override_port) && $override_port < 65536)
  {
    $agent_port = $override_port;
  }

  // Try SSH Connect inspired by http://ispire.me/polling-observium-unix-agent-with-ssh
  $agent_start = utime();

  // RSA signature verification is much faster than ECDSA signature verification
  // Use RSA keys for SSH authentication with private keys
  $identity_key = new phpseclib\Crypt\RSA();
  if (file_exists("/opt/observium/ssh/id_rsa")) {
    $identity_key->loadKey(file_get_contents("/opt/observium/ssh/id_rsa"));
  }

  $connection = new phpseclib\Net\SSH2($device['hostname'], $device['ssh_port'], 10);

  $connection->enableQuietMode();

  $host_key = $connection->getServerPublicHostKey();

  if ($connection->signature_validated) {
    echo("Host key: ".$host_key."\r\n");

    $stored_host_key = get_entity_attrib('device', $device, 'host_public_key');

    if (empty($stored_host_key)) {
      // Store host public key on first connection attempt
      set_entity_attrib('device', $device, 'host_public_key', $host_key);
      // Delete attribute if it was set
      del_entity_attrib('device', $device, 'host_id_changed');
    } else {
      echo("Stored key: ".$stored_host_key."\r\n");

      if (strcmp($host_key, $stored_host_key)) {
        print_warning("SSH connection to UNIX agent: Remote host identification has changed!");
        logfile("SSH connection to UNIX agent: Remote host identification has changed!");

        if (get_entity_attrib('device', $device, 'host_id_changed') === NULL) {
          set_entity_attrib('device', $device, 'host_id_changed', $config['time']['now']);
          log_event('Host identification has changed.', $device, 'device', $device['device_id'], 3);
        }
      } else {
        del_entity_attrib('device', $device, 'host_id_changed');
      }
    }

    if ($connection->login("root", $identity_key)) {
      $agent_raw = $connection->exec("/usr/bin/observium_agent");

      if (strpos($agent_raw, '<<<Observium>>>') === false) {
        // This is for LibreElec
        $agent_raw = $connection->exec("/storage/observium/observium_agent");
      }
    }
  }

  if (defined('NET_SSH2_LOGGING') && defined('LOG_REALTIME_FILENAME')) {
    $ssh_connection_log = $connection->getLog();

    $fp = fopen(LOG_REALTIME_FILENAME, 'w');
    fputs($fp, $ssh_connection_log);
    fclose($fp);
  }

  // Fallback to xinetd connection
  if (strpos($agent_raw, '<<<Observium>>>') === false) {
    print_warning("SSH connection to UNIX agent failed: Unable to establish a connection. Fallback to xinetd connection.");
    logfile("UNIX-AGENT: SSH connection failed: Unable to establish a connection. Fallback to xinetd connection.");

    $agent_socket = "tcp://".$device['hostname'].":".$agent_port;
    $agent = @stream_socket_client($agent_socket, $errno, $errstr, 10);

    if (!$agent)
    {
        print_warning("Connection to UNIX agent on ".$agent_socket." failed. ERROR: ".$errno." ".$errstr);
        logfile("UNIX-AGENT: Connection on ".$agent_socket." failed. ERROR: ".$errno." ".$errstr);
    } else {
        $agent_raw = stream_get_contents($agent);
    }
  }

  $agent_end = utime(); $agent_time = round(($agent_end - $agent_start) * 1000);

  echo("execution time: ".$agent_time."ms\r\n");

  if (strpos($agent_raw, '<<<Observium>>>') !== false)
  {
    rrdtool_update_ng($device, 'agent', array('time' => $agent_time));
    $graphs['agent'] = TRUE;

    foreach (explode("<<<", $agent_raw) as $section)
    {
      list($section, $data) = explode(">>>", $section);

      $sa = '';
      $sb = '';
      $sc = '';

      # DO -NOT- ADD NEW CASES BELOW -- use <<<app-$foo>>> in your application script
      switch ($section)
      {
        // Compatibility with versions of scripts with and without app-
        // Disabled for DRBD because it falsely detects the check_mk output
        case "drbd":
          $sa = "app"; $sb = "drbd";
          break;
        case "apache":
          $sa = "app"; $sb = "apache";
          break;
        case "mysql":
          $sa = "app"; $sb = "mysql";
          break;
        case "nginx":
          $sa = "app"; $sb = "nginx";
          break;
        # FIXME why is this here? New application scripts should just return app-$foo
        case "freeradius":
          $sa = "app"; $sb = "freeradius";
          break;
        case "postfix_qshape":
          $sa = "app"; $sb = "postfix_qshape";
          break;
        case "postfix_mailgraph":
          $sa = "app"; $sb = "postfix_mailgraph";
          break;
        # Workaround for older script where we didn't split into 3 possible parts yet
        case "app-powerdns-recursor":
          $sa = "app"; $sb = "powerdns-recursor"; $sc = "";
          break;
        case "app-exim-mailqueue":
          $sa = "app"; $sb = "exim-mailqueue"; $sc = "";
          break;
        default:
          // default section name: "app-some-sc"
          list($sa, $sb, $sc) = explode("-", $section, 3);
      }
      # DO -NOT- ADD NEW CASES ABOVE -- use <<<app-$foo>>> in your application script

      if ($section != "app-drbd" && !empty($sa) && !empty($sb))
      {
        if (!empty($sc))
        {
          $agent_data[$sa][$sb][$sc] = trim($data);
        } else {
          $agent_data[$sa][$sb] = trim($data);
        }
      } else {
        $agent_data[$section] = trim($data);
      }
    }

    $agent_sensors = array(); # Init to empty to be able to use array_merge() later on

    if (OBS_DEBUG && count($agent_data)) { print_vars($agent_data); }

    include("unix-agent/packages.inc.php");
    include("unix-agent/munin-plugins.inc.php");

    foreach (array_keys($agent_data) as $key)
    {
      if (file_exists($config['install_dir'].'/includes/polling/unix-agent/'.$key.'.inc.php'))
      {
        print_debug('Including: unix-agent/'.$key.'.inc.php');

        include($config['install_dir'].'/includes/polling/unix-agent/'.$key.'.inc.php');
      } else {
        print_warning("No include: ".$key);
      }
    }

    if (is_array($agent_data['app']))
    {
      foreach (array_keys($agent_data['app']) as $key)
      {
        if (file_exists('includes/polling/applications/'.$key.'.inc.php'))
        {
          echo(" ");

          // FIXME Currently only used by drbd to get $app['app_instance'] - shouldn't the drbd parser get instances from somewhere else?
          // $app = @dbFetchRow("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_type` = ?", array($device['device_id'],$key));

          // print_debug('Including: applications/'.$key.'.inc.php');

          // echo($key);

          //include('includes/polling/applications/'.$key.'.inc.php');

          $valid_applications[$key] = $key;

        }
      }
    }

    // Processes
    // FIXME unused
    if (!empty($agent_data['ps']))
    {
      echo("\nProcesses: ");
      foreach (explode("\n", $agent_data['ps']) as $process)
      {
        $process = preg_replace("/\((.*),([0-9]*),([0-9]*),([0-9\.]*)\)\ (.*)/", "\\1|\\2|\\3|\\4|\\5", $process);
        list($user, $vsz, $rss, $pcpu, $command) = explode("|", $process, 5);
        $processlist[] = array('user' => $user, 'vsz' => $vsz, 'rss' => $rss, 'pcpu' => $pcpu, 'command' => $command);
      }
      #print_vars($processlist);
      echo("\n");
    }
  }

  echo("Sensors: ");
  foreach (array_keys($config['sensor_types']) as $sensor_class)
  {
    check_valid_sensors($device, $sensor_class, $valid['sensor'], 'agent');
  }
  echo("\n");

  echo("Virtual machines: ");
  check_valid_virtual_machines($device, $valid['vm'], 'agent');
  echo("\n");
}

// EOF
