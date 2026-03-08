<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'style.css',
	'js' => 'script.js',
	'rel' => [
		'main.core',
		'ui.vue3',
		'ui.design-tokens',
		'ui.icon-set.main',
		'ui.icon-set.actions',
	],
	'skip_core' => false,
];
