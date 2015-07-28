<?php

global $smcFunc, $user_info, $boardurl;

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot uninstall - please verify you put this in the same place as SMF\'s Settings.php.');

// If you do this manually, you have to be logged in!
if(!$user_info['is_admin'])
{
	if($user_info['is_guest'])
	{
		echo $txt['admin_login'] . ':<br />';
		ssi_login($boardurl . '/hooks.php');
		die();
	}
	else
	{
		loadLanguage('Errors');
		fatal_error($txt['cannot_admin_forum']);
	}
}

// Add or delete hooks
$call = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';
$hooks = array('integrate_actions' => 'ca_integrate_actions', 'integrate_whos_online' => 'ca_integrate_who', 'integrate_menu_buttons' => 'ca_integrate_menu_buttons');
foreach ($hooks as $hook => $function)
	$call($hook, $function);
