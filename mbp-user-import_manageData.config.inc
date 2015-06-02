<?php
/**
 * Message Broker configuration settings for mbp-user-import
 */
 
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'stathat_disable_tracking' => getenv('DISABLE_STAT_TRACKING'),
  'gmail_machine_username' => getenv("GMAIL_MACHINE_USERNAME"),
  'gmail_machine_password' => getenv("GMAIL_MACHINE_PASSWORD"),
);
