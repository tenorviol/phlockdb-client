<?php

spl_autoload_register(function($class) {
	if (strncmp('Phlock', $class, 6)) {
		return false;
	}
	static $dir = __DIR__;
	require_once $dir.'/'.str_replace('_', '/', $class).'.php';
});
