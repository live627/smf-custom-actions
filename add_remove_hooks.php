<?php

/**
 * @package CustomAction
 * @since 1.0
 */
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF')) {
	$ssi = true;
	require_once(__DIR__ . '/SSI.php');
} elseif (!defined('SMF')) {
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

if(!$user_info['is_admin'])
{
	if($user_info['is_guest'])
	{
		echo $txt['admin_login'] . ':<br />';
		ssi_login($boardurl . '/add_remove_hooks.php');
		die();
	}
	else
	{
		loadLanguage('Errors');
		fatal_error($txt['cannot_admin_forum']);
	}
}

if (!class_exists('ModHelper\Psr4AutoloaderClass')) {
	require_once(__DIR__ . '/CustomAction/ModHelper/Psr4AutoloaderClass.php');
}
// instantiate the loader
$loader = new \ModHelper\Psr4AutoloaderClass;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace('ModHelper', __DIR__ . '/CustomAction/ModHelper');
$loader->addNamespace('CustomAction', __DIR__ . '/CustomAction');

(new \ModHelper\Hooks)->add('integrate_pre_include', '$sourcedir/CustomAction/Services/Integration.php')
	->add('integrate_pre_load', '\\CustomAction\\Services\\Integration::pre_load')
	->add('integrate_actions', '\\CustomAction\\Services\\Integration::actions')
	->add('integrate_menu_buttons', '\\CustomAction\\Services\\Integration::menu_buttons')
	->add('integrate_admin_areas', '\\CustomAction\\Services\\Integration::admin_areas')
	->add('integrate_whos_online', '\\CustomAction\\Services\\Integration::whos_online')
	->execute(empty($context['uninstalling']));

if (!empty($ssi)) {
	echo 'Database installation complete!';
}
