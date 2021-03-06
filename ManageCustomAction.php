<?php

if (!defined('SMF'))
	die('Hacking attempt...');

function CustomActionList()
{
	global $context, $txt, $sourcedir, $scripturl, $db_prefix, $smcFunc;

	$context['page_title'] = $txt['custom_action_title'];
	$context['sub_template'] = 'show_custom_action';

	// Are we listing sub-actions?
	if (!empty($_REQUEST['id_action']))
	{
		$id_action = (int) $_REQUEST['id_action'];

		$request = $smcFunc['db_query']('', '
			SELECT name, url
			FROM {db_prefix}custom_actions
			WHERE id_action = {int:id_action}',
			array(
				'id_action' => $id_action,
			)
		);

		// Found the parent action?
		if ($smcFunc['db_num_rows']($request) != 0)
		{
			list ($parent_name, $parent_url) = $smcFunc['db_fetch_row']($request);
			$parent = $id_action;
		}
		else
			$parent = 0;

		$smcFunc['db_free_result']($request);
	}
	else
		$parent = 0;

	// Load up our list.
	require_once($sourcedir . '/Subs-List.php');

	$listOptions = array(
		'id' => 'custom_actions',
		'title' => $parent ? sprintf($txt['custom_action_title_sub'], $parent_name) : $txt['custom_action_title'],
		'base_href' => $scripturl . '?action=admin;area=featuresettings;sa=action' . ($parent ? ';action=' . $parent : ''),
		'default_sort_col' => 'action_name',
		'no_items_label' => $parent ? sprintf($txt['custom_action_none_sub'], $parent_name) :$txt['custom_action_none'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getCustomActions',
			'params' => array(
				$parent,
			),
		),
		'get_count' => array(
			'function' => 'list_getCustomActionSize',
			'params' => array(
				$parent,
			),
		),
		'columns' => array(
			'action_name' => array(
				'header' => array(
					'value' => $txt['custom_action_name'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl;

						return $rowData[\'enabled\'] ? \'<a href="\' . $scripturl  . \'?action=' . ($parent ? $parent_url . ';sa=' : '') . '\' . $rowData[\'url\'] . \'">\' . $rowData[\'name\'] . \'</a>\' : $rowData[\'name\'];'),
					// Limit the width if we have the sub-action column.
					'style' => 'width: ' . ($parent ? '62%' : '50%') . ';',
				),
				'sort' => array(
					'default' => 'ca.name',
					'reverse' => 'ca.name DESC',
				),
			),
			'action_type' => array(
				'header' => array(
					'value' => $txt['custom_action_type'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						return isset($txt[\'custom_action_type_\' . $rowData[\'action_type\']]) ? $txt[\'custom_action_type_\' . $rowData[\'action_type\']] : $rowData[\'action_type\'];'),
					'style' => 'width: 15%;',
				),
				'sort' => array(
					'default' => 'ca.action_type',
					'reverse' => 'ca.action_type DESC',
				),
			),
			'sub_actions' => array(
				'header' => array(
					'value' => $txt['custom_action_sub_actions'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl;

						return \'<a href="\' . $scripturl . \'?action=admin;area=featuresettings;sa=action;id_action=\' . $rowData[\'id_action\'] . \'">\' . $rowData[\'sub_actions\'] . \'</a>\';'),
					'style' => 'width: 12%; text-align: center;',
				),
				'sort' => array(
					'default' => 'COUNT(sa.id_action)',
					'reverse' => 'COUNT(sa.id_action) DESC',
				),
			),
			'enabled' => array(
				'header' => array(
					'value' => $txt['custom_action_enabled'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						return $rowData[\'enabled\'] ? $txt[\'yes\'] : $txt[\'no\'];'),
					//'class' => 'windowbg',
					'style' => 'width: 8%; text-align: center;',
				),
				'sort' => array(
					'default' => 'ca.enabled DESC',
					'reverse' => 'ca.enabled',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?action=admin;area=featuresettings;sa=actionedit;id_action=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_action' => false,
						),
					),
					//'class' => 'windowbg',
					'style' => 'width: 15%; text-align: center;',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '[<a href="' . $scripturl . '?action=admin;area=featuresettings;sa=actionedit' . ($parent ? ';id_parent=' . $parent : '') . '">' . $txt['custom_action_make_new' . ($parent ? '_sub' : '')] . '</a>]',
				'class' => 'titlebg',
			),
		),
	);

	// Will we be needing the sub-action column?
	if ($parent)
		unset($listOptions['columns']['sub_actions']);

	createList($listOptions);
}

function list_getCustomActions($start, $items_per_page, $sort, $parent)
{
	global $smcFunc, $db_prefix;

	$list = array();

	// Load all the actions.
	if ($parent)
		$request = $smcFunc['db_query']('', '
			SELECT ca.id_action, ca.name, ca.url, ca.action_type, ca.enabled
			FROM {db_prefix}custom_actions AS ca
			WHERE ca.id_parent = {int:id_parent}
			ORDER BY ' . $sort . '
			LIMIT ' . $start . ', ' . $items_per_page,
			array(
				'id_parent' => $parent,
			)
		);
	else
		$request = $smcFunc['db_query']('', '
			SELECT ca.id_action, ca.name, ca.url, ca.action_type, COUNT(sa.id_action) AS sub_actions, ca.enabled
			FROM {db_prefix}custom_actions AS ca
				LEFT JOIN {db_prefix}custom_actions AS sa ON (ca.id_action = sa.id_parent)
			WHERE ca.id_parent = 0
			GROUP BY ca.id_action, ca.name, ca.url, ca.action_type, ca.enabled
			ORDER BY ' . $sort . '
			LIMIT ' . $start . ', ' . $items_per_page,
			array(
			)
		);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$row['name'] = ca_text($row['url'], $row['name'], true); //Use language string if available.
		$list[] = $row;
	}
	$smcFunc['db_free_result']($request);

	return $list;
}

function list_getCustomActionSize($parent)
{
	global $smcFunc, $db_prefix;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}custom_actions
		WHERE id_parent = {int:id_parent}',
		array(
			'id_parent' => $parent,
		)
	);

	list ($numCustomActions) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $numCustomActions;
}

function CustomActionEdit()
{
	global $context, $txt, $smcFunc, $db_prefix, $sourcedir;

	$context['id_action'] = isset($_REQUEST['id_action']) ? (int)$_REQUEST['id_action'] : 0;
	$context['id_parent'] = isset($_REQUEST['id_parent']) ? (int)$_REQUEST['id_parent'] : 0;
	$context[$context['admin_menu_name']]['current_subsection'] = 'action';
	$context['page_title'] = $txt['custom_action_title'];
	$context['sub_template'] = 'edit_custom_action';

	// Needed for inline permissions.
	require_once($sourcedir . '/ManagePermissions.php');
	// Needed for BBC actions.
	require_once($sourcedir . '/Subs-Post.php');

	// Saving?
	if (isset($_REQUEST['save']))
	{
		checkSession();

		if (!empty($context['id_action']))
		{
			// Is this action a child?
			$request = $smcFunc['db_query']('', '
				SELECT id_parent
				FROM {db_prefix}custom_actions
				WHERE id_action = {int:id_action}',
				array(
					'id_action' => $context['id_action'],
				)
			);

			if ($smcFunc['db_num_rows']($request) == 0)
				fatal_lang_error('custom_action_not_found', false);

			list ($context['id_parent']) = $smcFunc['db_fetch_row']($request);

			$smcFunc['db_free_result']($request);
		}

		// Do we have a valid name?
		$url = strtolower($_POST['url']);
		if (preg_match('~[^a-z0-9_]~', $url))
			fatal_lang_error('custom_action_invalid_url', false);

		// Inline permissions?
		if ($_POST['permissions_mode'] == 1)
		{
			save_inline_permissions(array('ca_' . (!empty($context['id_action']) ? $context['id_action'] : 'temp')));
			$permissions_mode = 1;
		}
		else if ($context['id_parent'] && $_POST['permissions_mode'] == 2)
			$permissions_mode = 2;
		else
			$permissions_mode = 0;

		// Is the field enabled?
		$enabled = !empty($_POST['enabled']) ? 1 : 0;

		// What about the type?
		if (in_array($_POST['type'], $context['id_parent'] ? array(0, 1, 2, 3) : array(0, 1, 2)))
			$type = $_POST['type'];
		else
			$type = 0;

		// A menu button?
		$menu = !empty($_POST['menu']) ? 1 : 0;

		// Clean the body and headers.
		$header = $_POST['header'];
		if ($type == 1)
		{
			$body = $_POST['body'];
			preparsecode($body);

			// No headers for us!
			$header = '';
		}
		else
			$body = $_POST['body'];

		$name = $_POST['name'];

		// Update the database.
		if (!empty($context['id_action']))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}custom_actions
				SET name = {string:name}, url = {string:url}, enabled = {int:enabled}, permissions_mode = {int:permissions_mode},
					action_type = {int:action_type}, menu = {int:menu}, header = {string:header}, body = {string:body}
				WHERE id_action = {int:id_action}',
				array(
					'id_action' => $context['id_action'],
					'name' => $name,
					'url' => $url,
					'enabled' => $enabled,
					'permissions_mode' => $permissions_mode,
					'action_type' => $type,
					'menu' => $menu,
					'header' => $header,
					'body' => $body,
				)
			);
		// A new action.
		else
		{
			// Insert the data.
			$smcFunc['db_insert']('',
				'{db_prefix}custom_actions',
				array(
					'id_parent' => 'int', 'name' => 'string', 'url' => 'string', 'enabled' => 'int',
					'permissions_mode' => 'int', 'action_type' => 'int', 'menu' => 'int', 'header' => 'string', 'body' => 'string',
				),
				array(
					$context['id_parent'], $name, $url, $enabled,
					$permissions_mode, $type, $menu, $header, $body,
				),
				array('id_action')
			);

			$context['id_action'] = $smcFunc['db_insert_id']('{db_prefix}custom_actions', 'id_action');

			// Update our temporary permissions.
			if ($permissions_mode == 1)
			{
				// There's a small posibillity that there may already be some permissions with the same name.
				$smcFunc['db_query']('', '
					DELETE FROM {db_prefix}permissions
					WHERE permission = {string:permission}',
					array(
						'permission' => 'ca_' . $context['id_action'],
					)
				);

				$smcFunc['db_query']('', '
					UPDATE {db_prefix}permissions
					SET permission = {string:permission}
					WHERE permission = {string:temporary_permission}',
					array(
						'permission' => 'ca_' . $context['id_action'],
						'temporary_permission' => 'ca_temp',
					)
				);
			}
		}

		// Recache.
		recacheCustomActions();

		redirectexit('action=admin;area=featuresettings;sa=action' . ($context['id_parent'] ? ';id_action=' . $context['id_parent'] : ''));
	}
	// Deleting?
	elseif (isset($_REQUEST['delete']))
	{
		checkSession();

		// Before we do anything we need to know what to redirect to when we're done.
		$request = $smcFunc['db_query']('', '
			SELECT id_parent
			FROM {db_prefix}custom_actions
			WHERE id_action = {int:id_action}',
			array(
				'id_action' => $context['id_action'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('custom_action_not_found', false);

		list ($context['id_parent']) = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);

		$to_delete = array($context['id_action']);
		// Does this action have any children we need to kill, too?
		$request = $smcFunc['db_query']('', '
			SELECT id_action
			FROM {db_prefix}custom_actions
			WHERE id_parent = {int:id_parent}',
			array(
				'id_parent' => $context['id_action'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$to_delete[] = $row['id_action'];
		$smcFunc['db_free_result']($request);

		// First take the actions.
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}custom_actions
			WHERE id_action IN ({array_int:to_delete})',
			array(
				'to_delete' => $to_delete,
			)
		);

		// Now get rid of those extra permissions.
		foreach ($to_delete as $key => $value)
			$to_delete[$key] = 'ca_' . $value;
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}permissions
			WHERE permission IN ({array_string:to_delete})',
			array(
				'to_delete' => $to_delete,
			)
		);

		// We'll need to recache.
		recacheCustomActions();

		redirectexit('action=admin;area=featuresettings;sa=action' . ($context['id_parent'] ? ';id_action=' . $context['id_parent'] : ''));
	}
	// Are we editing or creating a new action?
	elseif (!empty($context['id_action']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_parent, name, url, enabled, permissions_mode, action_type, menu, header, body
			FROM {db_prefix}custom_actions
			WHERE id_action = {int:id_action}',
			array(
				'id_action' => $context['id_action'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('custom_action_not_found', false);

		$row = $smcFunc['db_fetch_assoc']($request);

		$smcFunc['db_free_result']($request);

		$context['id_parent'] = $row['id_parent'];

		$context['action'] = array(
			'name' => $row['name'],
			'url' => $row['url'],
			'enabled' => $row['enabled'],
			'permissions_mode' => $row['permissions_mode'],
			'type' => $row['action_type'],
			'menu' => $row['menu'],
			'header' => $row['header'],
			'body' => $row['body'],
		);

		// BBC?
		if ($context['action']['type'] == 1)
			$context['action']['body'] = un_preparsecode($context['action']['body']);

		init_inline_permissions(array('ca_' . $context['id_action']));
	}
	else
	{
		// Set up the default options.
		$context['action'] = array(
			'name' => '',
			'url' => '',
			'enabled' => 1,
			'permissions_mode' => 0,
			'type' => 0,
			'menu' => 0,
			'header' => '',
			'body' => '',
		);

		// We'll have to rename these later when we knoe what the action ID will be.
		init_inline_permissions(array('ca_temp'));
	}
}

function recacheCustomActions()
{
	global $smcFunc, $db_prefix, $context, $user_info, $modSettings;

	// Get all the action names.
	$request = $smcFunc['db_query']('', '
		SELECT id_action, id_parent, name, url, permissions_mode, menu
		FROM {db_prefix}custom_actions
		WHERE (id_parent = 0 OR menu != 0)
			AND enabled = 1
		ORDER BY id_parent DESC, id_action ASC',
		array(
		)
	);

	$cache = array();
	$menu_cache = array();
	$who_cache = array();
	$subactions = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_parent'] != 0)
		{
			$subactions[$row['id_parent']][] = array(
				0 => $row['url'],
				1 => $row['name'],
				2 => $row['permissions_mode'] == 2 ? ('ca_' . $row['id_parent']) : ($row['permissions_mode'] == 1 ? 'ca_' . $row['id_action'] : false),
			);
		}
		else
		{
			$cache[] = $row['url'];
			$who_cache[$row['url']] = array($row['name'], ($row['permissions_mode'] == 1 ? 'ca_' . $row['id_action'] : false));
			if (isset($subactions[$row['id_action']]))
			{
				foreach ($subactions[$row['id_action']] as $sub)
				{	if (!empty($sub[0]))
						$who_cache[$row['url'] . '_' . $sub[0]] = array($sub[1], $sub[2]);
				}
			}
			// On the menu?
			if ($row['menu'])
				$menu_cache[] = array(
					0 => $row['url'],
					1 => $row['name'],
					2 => $row['permissions_mode'] == 1 ? 'ca_' . $row['id_action'] : false,
					3 => isset($subactions[$row['id_action']]) ? $subactions[$row['id_action']] : array(),
				);
		}
	}

	$smcFunc['db_free_result']($request);

	updateSettings(array(
		'ca_cache' => implode(';', $cache),
		'ca_menu_cache' => serialize($menu_cache),
		'ca_who_cache' => serialize($who_cache),
	), true);

	// Try to at least clear the cache for them.
	cache_put_data('menu_buttons-' . implode('_', $user_info['groups']) . '-' . $user_info['language'], null);
	if (isset($modSettings['sp_version'])) //Using Simple Portal?
		cache_put_data('menu_buttons-' . implode('_', $user_info['groups']) . '-' . $user_info['language'] . '-' . empty($context['disable_sp']), null);
}
