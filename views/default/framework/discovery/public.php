<?php

$maxwidth = get_input('maxwidth', 640);
$maxheight = get_input('maxheight', 480);

$card = elgg_view('framework/discovery/public', $vars, false, false, 'oembed');

echo elgg_format_element('div', [
	'style' => "width: {$maxwidth}px; height: {$maxheight}px",
], $card);
