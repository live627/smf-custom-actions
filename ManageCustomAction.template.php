<?php
// Version: 3.0; CustomAction

function template_show_custom_action()
{
	template_show_list('custom_actions');
}

function template_edit_custom_action()
{
	global $context, $txt, $scripturl, $settings;

	echo '
	<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/ca_codemirror-min.js"></script>
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/ca_codemirror.css">
	<script type="text/javascript"><!-- // --><![CDATA[
		var cm_head, cm_body;
		function createCm(ta, type)
		{
			var cm = CodeMirror.fromTextArea(document.getElementById(ta), {
				mode: (type == 2 ? "text/x-php" : "htmlmixed"),
				indentUnit: 4,
				matchBrackets: true,
				extraKeys: {
					"Ctrl-I": function(cm) {
						cm.indentSelection("add");
					},
					"Ctrl-J": function(cm) {
						cm.indentSelection("smart");
					},
					"Ctrl-U": function(cm) {
						cm.indentSelection("subtract");
					},
					"F11": function(cm) {
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen"))	cm.setOption("fullScreen", false);
					}
				}
			});
			return cm;
		}
		function updateInputBoxes()
		{
			permission_mode = document.getElementById("permissions_mode").value;
			type = document.getElementById("type").value;

			document.getElementById("inline_permissions").style.display = permission_mode == 1 ? "" : "none";
			document.getElementById("header_box").style.display = (type != 1 && type != 3) ? "" : "none";
			document.getElementById("body_box").style.display = type != 3 ? "" : "none";
			document.getElementById("header_text").style.display = type == 0 ? "" : "none";
			document.getElementById("source_text").style.display = type == 2 ? "" : "none";
			document.getElementById("html_body_text").style.display = type == 0 ? "" : "none";
			document.getElementById("body_text").style.display = type == 1 ? "" : "none";
			document.getElementById("php_body_text").style.display = type == 2 ? "" : "none";

			if (cm_head) {
				if (type == 0 || type == 2) {
					cm_head.setOption( "mode", type == 2 ? "text/x-php" : "htmlmixed");
					cm_body.setOption( "mode", type == 2 ? "text/x-php" : "htmlmixed");
				} else {
					cm_head.toTextArea();
					cm_body.toTextArea();
					cm_head = null;
					cm_body = null;
				}
			} else if (type == 0 || type == 2) {
				cm_head = createCm("ta_header", type);
				cm_body = createCm("ta_body", type);
			}
		}
	// ]', ']></script>';

	echo '
	<form action="', $scripturl, '?action=admin;area=featuresettings;sa=actionedit', $context['id_action'] ? ';id_action=' . $context['id_action'] : '', '" method="post" accept-charset="', $context['character_set'], '">
		<table width="80%" align="center" cellpadding="3" cellspacing="0" border="0" class="tborder">
			<tr class="titlebg">
				<td colspan="2">', $context['page_title'], '</td>
			</tr><tr class="catbg">
				<td colspan="2">', $txt['custom_action_settings'], ':</td>
			</tr><tr class="windowbg2">
				<td width="50%">
					<b>', $txt['custom_action_name'], ':</b>
				</td>
				<td width="50%">
					<input type="text" name="name" value="', $context['action']['name'], '" size="20" maxlength="255" />
				</td>
			</tr><tr class="windowbg2">
				<td width="50%">
					<b>', $txt['custom_action_url'], ':</b>
					<div class="smalltext">', $txt['custom_action_url_desc'], '</div>
				</td>
				<td width="50%">
					<input type="text" name="url" value="', $context['action']['url'], '" size="20" maxlength="40" />
				</td>
			</tr><tr class="windowbg2">
				<td width="50%">
					<b>', $txt['custom_action_type'], ':</b>
				</td>
				<td width="50%">
					<select name="type" id="type" onchange="updateInputBoxes();">
						<option value="0" ', $context['action']['type'] == 0 ? 'selected="selected"' : '', '>', $txt['custom_action_type_0'], '</option>
						<option value="1" ', $context['action']['type'] == 1 ? 'selected="selected"' : '', '>', $txt['custom_action_type_1'], '</option>
						<option value="2" ', $context['action']['type'] == 2 ? 'selected="selected"' : '', '>', $txt['custom_action_type_2'], '</option>', $context['id_parent'] ? '
						<option value="3" ' . ($context['action']['type'] == 3 ? 'selected="selected"' : '') . '>' . $txt['custom_action_type_3'] . '</option>' : '', '
					</select>
				</td>
			</tr><tr class="windowbg2">
				<td width="50%">
					<b>', $txt['custom_action_permissions_mode'], ':</b>
				</td>
				<td width="50%">
					<select name="permissions_mode" id="permissions_mode" onchange="updateInputBoxes();">
						<option value="0" ', $context['action']['permissions_mode'] == 0 ? 'selected="selected"' : '', '>', $txt['custom_action_permissions_mode_0'], '</option>
						<option value="1" ', $context['action']['permissions_mode'] == 1 ? 'selected="selected"' : '', '>', $txt['custom_action_permissions_mode_1'], '</option>', $context['id_parent'] ? '
						<option value="2" ' . ($context['action']['permissions_mode'] == 2 ? 'selected="selected"' : '') . '>' . $txt['custom_action_permissions_mode_2'] . '</option>' : '', '
					</select>
					<div id="inline_permissions">
						', theme_inline_permissions('ca_' . ($context['id_action'] ? $context['id_action'] : 'temp')), '
					</div>
				</td>
			</tr>', '<tr class="windowbg2">
				<td width="50%">
					<b>' . $txt['custom_action_menu'] . ':</b>
				</td>
				<td width="50%">
					<input type="checkbox" name="menu" ' . ($context['action']['menu'] ? 'checked="checked"' : '') . ' class="check" />
				</td>
			</tr>', '<tr class="windowbg2">
				<td width="50%">
					<b>', $txt['custom_action_enabled'], ':</b>
				</td>
				<td width="50%">
					<input type="checkbox" name="enabled" ', $context['action']['enabled'] ? 'checked="checked"' : '', ' class="check" />
				</td>
			</tr><tr class="catbg">
				<td colspan="2">', $txt['custom_action_settings_code'], ':</td>
			</tr><tr class="windowbg2"  valign="top" id="header_box">
				<td width="50%" id="header_text">
					<b>', $txt['custom_action_header'], ':</b>
					<div class="smalltext">', $txt['custom_action_header_desc'], '</div>
				</td>
				<td width="50%" id="source_text">
					<b>', $txt['custom_action_source'], ':</b>
					<div class="smalltext">', $txt['custom_action_source_desc'], '</div>
				</td>
				<td width="50%">
					<textarea id="ta_header" name="header" rows="10" cols="60">', htmlspecialchars($context['action']['header']), '</textarea>
				</td>
			</tr><tr class="windowbg2" valign="top" id="body_box">
				<td width="50%" id="body_text">
					<b>', $txt['custom_action_body'], ':</b>
					<div class="smalltext">', $txt['custom_action_body_desc'], '</div>
				</td>
				<td width="50%" id="html_body_text">
					<b>', $txt['custom_action_body_html'], ':</b>
					<div class="smalltext">', $txt['custom_action_body_html_desc'], '</div>
				</td>
				<td width="50%" id="php_body_text">
					<b>', $txt['custom_action_body_php'], ':</b>
					<div class="smalltext">', $txt['custom_action_body_php_desc'], '</div>
				</td>
				<td width="50%">
					<textarea id="ta_body" name="body" rows="20" cols="60">', htmlspecialchars($context['action']['body']), '</textarea>
				</td>
			</tr><tr class="titlebg">
				<td colspan="4" align="center">
					<input type="submit" name="save" value="', $txt['save'], '" />';

	if ($context['id_action'])
		echo '
					<input type="submit" name="delete" value="', $txt['delete'], '" onclick="return confirm(\'', $txt['custom_action_delete_sure'], '\');" />';

	echo '
				</td>
			</tr>
		</table>', $context['id_parent'] ? '
		<input type="hidden" name="id_parent" value="' . $context['id_parent'] . '" />' : '', '
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
	</form>';

	// Get the javascript bits right!
	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		updateInputBoxes();
	// ]', ']></script>';
}
