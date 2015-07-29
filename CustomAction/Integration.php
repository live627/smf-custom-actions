<?php
// Version: 4.0: Integration.php

namespace CustomAction\Services;

if (!defined('SMF')) {
	die('Hacking attempt...');
}

/**
 * @package CustomAction
 * @since 4.0
 */
class Integration
{
	/**
	 * Called by integration hook integrate_whos_online.
	 *
	 * Returns the text to use in Who's Online when someone is executing a custom action.
	 * If there exists a language string for this action,(whoall_, whoadmin_ or whoallow_), return false to use that string.
	 * If the user viewing Who's online lacks permission to perform the action then $txt['who_hidden'] is returned.
	 * Otherwise return the, possibly localized, name of the custom action.
	*/
	function ca_integrate_who($actions)
	{
		global $txt, $modSettings, $sourcedir;
		$key = $actions['action'] . (isset($actions['sa']) ? '_' . $actions['sa'] : '');
		if (isset($txt['whoall_' . $key]) || isset($txt['whoadmin_' . $key]) || isset($txt['whoallow_' . $key]))
			return false;
		$ca_who = unserialize($modSettings['ca_who_cache']);
		if (isset($ca_who[$key]))
		{
			list($name, $perm) = $ca_who[$key];
			if ($perm && !allowedTo($perm))
				return $txt['who_hidden'];
			else
			{
				return ca_text((isset($actions['sa']) ? $actions['sa'] : $actions['action']), $name);
			}
		}
		else
			return false;
	}
	
	/**
	 * Called by integration hook integrate_actions.
	 *
	 * Adds all enabled custom actions to the action array.
	 */
	function ca_integrate_actions(&$actionArray)
	{
		global $modSettings;
		foreach (explode(';', $modSettings['ca_cache']) as $custom_action)
			$actionArray[$custom_action] = array('CustomAction.php', 'ViewCustomAction');
	}
	
	/**
	 * Called by integration hook integrate_menu_buttons.
	 *
	 * Adds the menu buttons for all enabled custom actions to the button array.
	 */
	function ca_integrate_menu_buttons(&$buttons)
	{
		global $modSettings, $scripturl;
		$ca_menu_cache = unserialize($modSettings['ca_menu_cache']);
		$ca_buttons = array();
		foreach ($ca_menu_cache as $button)
		{
			$sub_buttons = array();
			foreach ($button[3] as $sub_button)
			{
				$sub_buttons[] = array(
					'title' => ca_text($sub_button[0], $sub_button[1]),
					'href' => $scripturl . '?action=' . $button[0] . ($sub_button[0] ? ';sa=' . $sub_button[0] : ''),
					'show' => $sub_button[2] ? allowedTo($sub_button[2]) : true,
					'sub_buttons' => array(),
					'is_last' => true,
				);
			}
			$ca_buttons[$button[0]] = array(
				'title' => ca_text($button[0], $button[1]),
				'href' => $scripturl . '?action=' . $button[0],
				'show' => $button[2] ? allowedTo($button[2]) : true,
				'sub_buttons' => $sub_buttons,
				'is_last' => true,
			);
		}
		$tmp = array_splice ($buttons, 0, array_search('login', array_keys($buttons)));
		$buttons = array_merge ($tmp, $ca_buttons, $buttons);
	}
	
	/**
	 * Return the, possibly localized, name of a custom action.
	 *
	 * If the name parameter starts with $txt['key'] followed by an optional default text an attempt is made to find a language string using key 'key'.
	 * If the key is empty, (name starts with $txt[]), an attempt is made to find a language string using the url parameter prefixed with 'ca_menu_' as key.
	 * If both key and url are empty no attempt to find a language string is made.
	 * If no language string is found a default text is returned.
	 * If a default text follows $txt[],($txt['key']This is the default), that default text is returned.
	 * If no default is supplied then the key, with any inital 'ca_menu_' prefix removed and the first character in upper case, is returned.
	 * If no default is supplied and the key is empty then the url, with the first character in upper case, is returned.
	 * If also the url is empty the name parameter is returned unchanged.
	 */
	function ca_text($url, $name, $show_original = false)
	{
		global $txt;
		$pattern = "/^\\\$txt\['?([^'\]]*)'?\](.*)/"; //$txt['key']Default. Quotes, key and default are all optional.
		if (preg_match($pattern, $name, $matches))
		{
			$original = $show_original ? ' (' . $name . ')' : ''; //Only used in the admin page.
			$key = (!empty($matches[1]) ? $matches[1] : (!empty($url) ? 'ca_menu_' . $url : ''));
			if (!empty($key) && isset($txt[$key])) //Language string found.
				return $txt[$key] . $original;
			$default = (!empty($matches[2]) ? $matches[2] : ucfirst(preg_replace('/^ca_menu_/', '', $key, 1)));
			if (!empty($default)) //Would only be empty if both default and url are empty.
				return $default . $original;
		}
		return $name; //Just return the literal name.
	}
}
