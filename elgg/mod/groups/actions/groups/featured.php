<?php
/**
 * Feature a group
 *
 * @package ElggGroups
 */

$group_guid = get_input('group_guid');
$action = get_input('action_type');

$group = get_entity($group_guid);

if (!elgg_instanceof($group, 'group')) {
	register_error(elgg_echo('groups:featured_error'));
	forward(REFERER);
}

//get the action, is it to feature or unfeature
if ($action == "feature") {
	$group->featured_group = "yes";
	system_message(elgg_echo('groups:featuredon', [$group->name]));
} else {
	$group->deleteMetadata('featured_group');
	system_message(elgg_echo('groups:unfeatured', [$group->name]));
}

forward(REFERER);
