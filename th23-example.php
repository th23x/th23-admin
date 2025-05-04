<?php
/*
th23 Example
Frontend excerpt leveraging th23 Admin class

note: This is NOT intended to be a fully working meaningful plugin script, but rather a summary of possibilities with examples!
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

// recommended: within constructor of main plugin class add detection of previous upgrade

class th23_example {

	public $plugin = array();
	public $options = array();
	public $data = array();

	function __construct() {

		// ... basic setup ...

		// Load plugin options
		$this->options = (array) get_option($this->plugin['slug']);

		// ... localization ...

		// Detect update
		if(empty($this->options['version']) || $this->options['version'] != $this->plugin['version']) {
			// load class and trigger required actions
			$plugin_dir_path = plugin_dir_path($this->plugin['file']);
			if(file_exists($plugin_dir_path . '/th23-example-upgrade.php')) {
				require($plugin_dir_path . '/th23-example-upgrade.php');
				$upgrade = new th23_example_upgrade($this);
				$upgrade->start();
				// reload options - at least option version should have changed
				$this->options = (array) get_option($this->plugin['slug']);
			}
		}

		// ... hooks for plugin functionality ...

	}

	// ... main plugin functions ...

}

// ... initialization of class ...

?>
