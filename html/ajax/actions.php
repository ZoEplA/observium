<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     ajax
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2018 Observium Limited
 *
 */

$config['install_dir'] = "../..";

include_once("../../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated'])
{
   echo("unauthenticated");
   exit;
}

$vars = get_vars();

$json = json_decode(trim(file_get_contents("php://input")), TRUE);
if (isset($json['action']))
{
   $vars = $json;
} // Got a JSON payload. Replace $var.

switch ($vars['action'])
{

   case "group_edit":

      if (dbFetchRow("SELECT * FROM `groups` WHERE `group_id` = ?", array($vars['group_id'])))
      {

         $rows_updated = dbUpdate(array('group_descr' => $vars['group_descr'], 'group_name' => $vars['group_name'], 'group_assoc' => $vars['group_assoc']),
            'groups', '`group_id` = ?',
            array($vars['group_id']));
         if ($rows_updated)
         {
            update_group_table($vars['group_id']);
            print json_encode(array('id' => $group_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'group', 'group_id' => $vars['group_id']))));
         }
         else
         {
            header('Content-Type: application/json');
            print json_encode(array('status' => 'failed'));
         }
      }
      else
      {
         print json_encode(array('status' => 'failed'));
      }
      break;

   case "alert_assoc_edit":

      if (dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", array($vars['alert_test_id'])))
      {

         $rows_updated = dbUpdate(array('alert_assoc' => $vars['alert_assoc']), 'alert_tests', '`alert_test_id` = ?', array($vars['alert_test_id']));

         if ($rows_updated)
         {
            update_alert_table($vars['alert_test_id']);
            print json_encode(array('id' => $vars['alert_test_id'], 'status' => 'ok', 'redirect' => generate_url(array('page' => 'alert_check', 'alert_test_id' => $vars['alert_test_id']))));
         }
         else
         {
            header('Content-Type: application/json');
            print json_encode(array('status' => 'failed', 'message' => 'Database was not updated.'));
         }
      }
      else
      {
         print json_encode(array('status' => 'failed', 'message' => 'Alert Checker does not exist: ['.$vars['alert_test_id'].']'));
      }
      break;

   case "alert_check_add":

      //print json_encode(array($vars));


      $ok = TRUE;
      foreach (array('entity_type', 'alert_name', 'alert_severity', 'alert_conditions') as $var)
      {
         if (!isset($vars[$var]) || strlen($vars[$var]) == '0')
         {
            $ok       = FALSE;
            $failed[] = $var;
         }
      }

      if ($ok)
      {
         $check_array = array();

         $conditions = array();
         foreach (explode("\n", trim($vars['alert_conditions'])) AS $cond)
         {
            $condition = array();
            list($condition['metric'], $condition['condition'], $condition['value']) = explode(" ", trim($cond), 3);
            $conditions[] = $condition;
         }
         $check_array['conditions']        = json_encode($conditions);
         $check_array['alert_assoc']       = $vars['alert_assoc'];
         $check_array['entity_type']       = $vars['entity_type'];
         $check_array['alert_name']        = $vars['alert_name'];
         $check_array['alert_message']     = $vars['alert_message'];
         $check_array['severity']          = $vars['alert_severity'];
         $check_array['suppress_recovery'] = ($vars['alert_send_recovery'] == '1' || $vars['alert_send_recovery'] == 'on' ? 0 : 1);
         $check_array['alerter']           = NULL;
         $check_array['and']               = $vars['alert_and'];
         $check_array['delay']             = $vars['alert_delay'];
         $check_array['enable']            = '1';

         $check_id = dbInsert('alert_tests', $check_array);

         if (is_numeric($check_id))
         {

            update_alert_table($check_id);

            header('Content-Type: application/json');
            print json_encode(array('id' => $check_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'alert_check', 'alert_test_id' => $check_id))));

         }
         else
         {
            header('Content-Type: application/json');
            print json_encode(array('status' => 'failed', 'message' => 'Alert creation failed. Please note that the alert name <b>must</b> be unique.'));
         }
      }
      else
      {
         header('Content-Type: application/json');
         print json_encode(array('status' => 'failed', 'message' => 'Missing required data. (' . implode(", ", $failed) . ')'));
      }

      break;

   case "add_group":

      $ok          = TRUE;
      $group_array = array();

      foreach (array('entity_type', 'group_name', 'group_descr', 'group_assoc') as $var)
      {
         if (!isset($vars[$var]) || strlen($vars[$var]) == '0')
         {
            $ok = FALSE;
         }
         else
         {
            $group_array[$var] = $vars[$var];
         }
      }

      if ($ok)
      {
         $group_id = dbInsert('groups', $group_array);
         //print_r(dbError());
      }

      if ($group_id)
      {
         update_group_table($group_id);
         header('Content-Type: application/json');
         print json_encode(array('id' => $group_id, 'status' => 'ok', 'redirect' => generate_url(array('page' => 'group', 'group_id' => $group_id))));
      }
      else
      {
         //header('Content-Type: application/json');
         //print json_encode(array('status' => 'failed'));
         //print_r($vars); // For debugging
      }

      break;

   case "save_grid": // Save current layout of dashboard grid

      foreach ($vars['grid'] as $w)
      {
         dbUpdate(array('x' => $w['x'], 'y' => $w['y'], 'width' => $w['width'], 'height' => $w['height'],), 'dash_widgets',
            '`widget_id` = ?', array($w['id']));
      }
      break;

   case "add_widget": // Add widget of 'widget_type' to dashboard 'dash_id'

      if (isset($vars['dash_id']) && isset($vars['widget_type']))
      {
         $widget_id = dbInsert(array('dash_id' => $vars['dash_id'], 'widget_config' => json_encode(array()), 'widget_type' => $vars['widget_type']),
            'dash_widgets');
      }

      if ($widget_id)
      {
         header('Content-Type: application/json');
         print json_encode(array('id' => $widget_id));
      }
      else
      {
         //print_r($vars); // For debugging
      }
      break;

   case "del_widget":

      if (is_numeric($vars['widget_id']))
      {
         $rows_deleted = dbDelete('dash_widgets', '`widget_id` = ?', array($vars['widget_id']));
      }

      if ($rows_deleted)
      {
         header('Content-Type: application/json');
         print json_encode(array('id' => $vars['widget_id'], 'message' => 'Widget Deleted'));
      }
      break;

   case "edit_widget":

      $widget = dbFetchRow("SELECT * FROM `dash_widgets` WHERE widget_id = ?", array($vars['widget_id']));

      switch ($widget['widget_type'])
      {

         case "graph":

            print_message('To add a graph to this widget, navigate to the required graph and use the "Add To Dashboard" function on the graph page.');

            echo '<h3>Step 1. Locate Graph and click for Graph Browser.</h3>';
            echo '<img class="img img-thumbnail" src="images/doc/add_graph_1">';

            echo '<h3>Step 2. Select Add to Dashboard in Graph Browser.</h3>';
            echo '<img class="img" src="images/doc/add_graph_2">';
            break;

         default:
            print_vars($widget);

      }
      break;
}