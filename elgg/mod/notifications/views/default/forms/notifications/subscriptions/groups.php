<?php

/**
 * Saves subsription notification settings by group
 *
 * @uses $vars['user'] ElggUser
 */
$user = elgg_extract('user', $vars);
if (!elgg_instanceof($user, 'user')) {
	return;
}

echo elgg_view_input('hidden', [
	'name' => 'guid',
	'value' => $user->guid,
]);

echo elgg_format_element('p', [
	'class' => 'elgg-text-help',
], elgg_echo('notifications:subscriptions:groups:description'));


$records = elgg_view('notifications/subscriptions/groups', $vars);
echo elgg_format_element('div', [
	'class' => 'elgg-subscriptions',
], $records);

$footer = elgg_view_input('submit', [
	'value' => elgg_echo('save'),
]);

elgg_set_form_footer($footer);
