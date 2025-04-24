<?php
/*
th23 Example
Upgrade handling to leverage th23 Admin class

Coded 2024-2025 by Thorsten Hartmann (th23)
https://th23.net/
*/

/*
* IMPORTANT:
*   This upgrader might run on frontend triggered by a normal user visiting the site just after the update!
*   Thus consider time consumed, no user might be authenticated (yet), the caller might be an untrustworthy
*   person, you can NOT rely on anything!
*   Also this script runs VERY early to be able to complete any outstanding work before loading the page,
*   but this also means main WP and other plugins might not yet be fully loaded...
*
* RECOMMENDED:
*   When possible ie upon manual triggered updates, encourage admins triggering the update
*   to reload the page after WP updater claims completion to complete the plugin upgrade!
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

class th23_contact_upgrade {

	// ensure availability of plugin data in upgrader
	// important: plugin data are "read only" in upgrader and NOT passed back to main plugin file
	private $parent;
	function __construct($parent) {
		$this->parent = $parent;
	}

	/* main update function
	*  - chain individual actions required with individual if statements in ascending version order
	*  - update option version and save it after each update step - allowing to restart process where is was stopped
	*  - for larger changes required eg db structure updates, copying larger amounts of data eg in batches
	*    consider to "wait for admin" approach: enable maintenance mode, notify admin via mail
	*/
	function start() {

		// ensure version compare works also for older (not yet versioned) options
		if(empty($this->parent->options['version'])) {
			$this->parent->options['version'] = '0.0.0';
		}

		// from < v3.0.5: transfer settings to slug named option, from previously underscored naming
		if(version_compare($this->parent->options['version'], '3.0.5', '<')) {
			if(!empty($previous_options = (array) get_option('th23_contact_options'))) {
				$previous_options['version'] = '3.0.5';
				if(update_option($this->parent->plugin['slug'], $previous_options)) {
					$this->parent->options = (array) get_option($this->parent->plugin['slug']);
					delete_option('th23_contact_options');
				}
			}
		}

		// example: from < v3.1.0: ...
		/*
		if(version_compare($this->parent->options['version'], '3.1.0', '<')) {
			// ... action for required changes, if longer code required consider calling separate function
			$this->parent->options['version'] = '3.1.0';
			update_option($this->parent->plugin['slug'], $this->parent->options);
		}
		*/

		// nothing more to do to finalize the update - ensure option version is up to date
		$this->parent->options['version'] = $this->parent->plugin['version'];
		update_option($this->parent->plugin['slug'], $this->parent->options);

	}

}

?>
