<?php

/**
 *
 * Name: Keyboard Shortcuts
 * Description: Keyboard shortcut integration
 * Version: 0.1
 * Author: Andrew Manning <andrew@reticu.li>
 * MinVersion: 1.4.2
 *
 */

function keyboard_shortcuts_load() {
	register_hook('page_end', 'addon/keyboard_shortcuts/keyboard_shortcuts.php', 'keyboard_shortcuts_script');

}

function keyboard_shortcuts_unload() {
    unregister_hook('page_end', 'addon/keyboard_shortcuts/keyboard_shortcuts.php', 'keyboard_shortcuts_script');
}

function keyboard_shortcuts_install() {}
function keyboard_shortcuts_uninstall() {}

function keyboard_shortcuts_script() {
	head_add_js('addon/keyboard_shortcuts/keyboard_shortcuts.js');
	//$content = '<script type="text/javascript" src="' . z_root() . '/addon/keyboard_shortcuts/keyboard_shortcuts.js"></script>' . "\r\n";
	//return $content;
}
