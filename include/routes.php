<?php
/**
 * Every page the front controller may render: name => shows the right sidebar.
 * index.php refuses anything not listed here. Phase 2 maps these names to real paths.
 */
return [
	'index' => true,
	'join' => true,
	'ladder' => true,
	'news' => true,
	'viewdrop' => true,
	'vote' => true,
	'shop' => true,
	'buy' => true,
	'profile' => true,
	'administration' => true,
	'signin' => false,
	'register' => false,
	'password' => false,
];
