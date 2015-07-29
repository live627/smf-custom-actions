<?php
// Version 1.0: CustomForms.php
namespace CustomAction\Controllers;

if (!defined('SMF')) {
	die('Hacking attempt...');
}

/**
 * @package CustomForms
 * @since 1.0
 */
class CustomAction extends \Suki\Ohara
{
	use \ModHelper\SingletonTrait;

	public $name = __CLASS__;
	protected static $_activity = array();

	public function __construct()
	{
		$this->setRegistry();
		global $context, $smcFunc, $db_prefix, $txt, $sourcedir, $scripturl;
		// So which custom action is this?
		$request = $smcFunc['db_query']('', '
			SELECT id_action, name, permissions_mode, action_type, header, body
			FROM {db_prefix}custom_actions
			WHERE url = {string:url}
				AND enabled = 1
				AND id_parent = 0',
			array(
				'url' => $context['current_action'],
			)
		);

		$context['action'] = $smcFunc['db_fetch_assoc']($request);

		$smcFunc['db_free_result']($request);

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=' . $context['current_action'],
			'name' => ca_text($context['current_action'], $context['action']['name']),
		);

		// By any chance are we in a sub-action?
		if (!empty($_REQUEST['sa']))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_action, name, permissions_mode, action_type, header, body
				FROM {db_prefix}custom_actions
				WHERE url = {string:url}
					AND enabled = 1
					AND id_parent = {int:id_parent}',
				array(
					'id_parent' => $context['action']['id_action'],
					'url' => $_REQUEST['sa'],
				)
			);

			if ($smcFunc['db_num_rows']($request) != 0)
			{
				$sub = $smcFunc['db_fetch_assoc']($request);

				$smcFunc['db_free_result']($request);

				$context['action']['name'] = $sub['name'];
				// Do we have our own permissions?
				if ($sub['permissions_mode'] != 2)
				{
					$context['action']['id_action'] = $sub['id_action'];
					$context['action']['permissions_mode'] = $sub['permissions_mode'];
				}
				if($sub['action_type'] != 3) {
					$context['action']['action_type'] = $sub['action_type'];
					$context['action']['header'] = $sub['header'];
					$context['action']['body'] = $sub['body'];
				}
			}
		}

		// Are we even allowed to be here?
		if ($context['action']['permissions_mode'] == 1)
		{
			// Standard message, please.
			$txt['cannot_ca_' . $context['action']['id_action']] = '';
			isAllowedTo('ca_' . $context['action']['id_action']);
		}

		// Do this first to allow it to be overwritten by PHP source file code.
		$context['page_title'] = ca_text((!empty($_REQUEST['sa']) ? $_REQUEST['sa'] : $context['current_action']), $context['action']['name']);
		if (!empty($_REQUEST['sa']))
		{
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=' . $context['current_action'] . ';sa=' . $_REQUEST['sa'],
				'name' => $context['page_title'],
			);
		}

		switch ($context['action']['action_type'])
		{
			// Any HTML headers?
			case 0:
				$context['html_headers'] .= $context['action']['header'];
				break;
			// Do we need to parse any BBC?
			case 1:
				$context['action']['body'] = parse_bbc($context['action']['body']);
				break;
			// We have some more stuff to do for PHP actions.
			case 2:
				fixPHP($context['action']['header']);
				fixPHP($context['action']['body']);

				eval($context['action']['header']);
		}

		// Get the templates sorted out!
		loadTemplate('CustomAction');
		$context['sub_template'] = 'view_custom_action';
	}

	// Get rid of any <? or <?php at the start of code.
	function fixPHP(&$code)
	{
		$code = preg_replace('~^\s*<\?(php)?~', '', $code);
	}
}
