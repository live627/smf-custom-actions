<?php

global $smcFunc, $user_info, $boardurl;

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot uninstall - please verify you put this in the same place as SMF\'s Settings.php.');

// If you uninstall manually, you have to be logged in!
if(!$user_info['is_admin'])
{
	if($user_info['is_guest'])
	{
		echo $txt['admin_login'] . ':<br />';
		ssi_login($boardurl . '/uninstall.php');
		die();
	}
	else
	{
		loadLanguage('Errors');
		fatal_error($txt['cannot_admin_forum']);
	}
}

// Delete our settings.
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable IN ({array_string:ca_settings})',
	array(
		'ca_settings' => array('ca_enabled', 'ca_cache', 'ca_menu_cache', 'ca_who_cache'),
	)
);

// Any extra permissions?
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}permissions
	WHERE SUBSTRING(permission, 1, 3) = {string:ca_prefix}',
	array(
		'ca_prefix' => 'ca_',
	)
);
