<?php
/*
th23 Example
Admin excerpt leveraging th23 Admin class

note: This is NOT intended to be a fully working meaningful admin script, but rather a summary of possibilities with examples!
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

/* required: initiatlization via main plugin file containing main plugin class

class th23_example {

	public $plugin = array();
	public $options = array();
	public $data = array();

	// ... constructor and further main plugin functions ...

}

$th23_example_path = plugin_dir_path(__FILE__);

// Load additional admin class, if required...
if(is_admin() && file_exists($th23_example_path . 'th23-example-admin.php')) {
	require($th23_example_path . 'th23-example-admin.php');
	$th23_example = new th23_example_admin();
}
// ...or initiate main plugin class
else {
	$th23_example = new th23_example();
}

*/

// --- OR ---

/* required: load admin class independently as standalone by adding below code into your main plugin file

$th23_example_path = plugin_dir_path(__FILE__);

if(is_admin() && file_exists($th23_example_path . 'th23-example-admin.php')) {
	// Mimic main plugin class, if it does not exist
	if(!class_exists('th23_example')) {
		class th23_example {
			public $plugin = array();
			public $options = array();
			public $data = array();
		}
	}
	require($th23_example_path . 'th23-example-admin.php');
	$th23_example_admin = new th23_example_admin();
}

*/

/* optional: load plugin options administrated by th23 Admin class

$options = (array) get_option($this->plugin['slug']);

*/
class th23_example_admin extends th23_example {

	// Extend class-wide variables
	public $i18n;
	private $admin;

	function __construct() {

		/* required: main plugin class constructor must define OR these have to be defined here
		- $this->plugin['basename']
		- $this->plugin['file']
		- $this->plugin['slug']
		- $this->plugin['version']
		(see main plugin class example for details)
		*/
		parent::__construct();

		// Setup basics (additions for backend)
		$this->plugin['dir_path'] = plugin_dir_path($this->plugin['file']);
		/* required: "settings/base" and "settings/permission"
		note: new admin page for plugin can be added to any of the existing parents in the main admin menu
		"settings/base" = parent admin page slug / filename
		"settings/permission" = required permission (level) to access the new admin page
		(for details to both options see https://developer.wordpress.org/reference/functions/add_submenu_page/)
		*/
		$this->plugin['settings'] = array(
			'base' => 'options-general.php',
			'permission' => 'manage_options',
		);
		/* optional: icons "square" 48 x 48px (footer) and "horizontal" 36px height (header, width irrelevant) / both (resized if larger)
		*/
		$this->plugin['icon'] = array(
			'square' => 'img/icon-square.png',
			'horizontal' => 'img/icon-horizontal.png'
		);
		/* optional: url of support page linked on plugin overview and new admin page, eg WP repository forum, GitHub issues page or own website */
		$this->plugin['support_url'] = 'https://th23.net/th23-example-support/';
		/* mandatory: empty array */
		$this->plugin['requirement_notices'] = array();

		// Load and setup required th23 Admin class
		if(file_exists($this->plugin['dir_path'] . '/inc/th23-admin-class.php')) {
			require($this->plugin['dir_path'] . '/inc/th23-admin-class.php');
			/* mandatory: to avoid version conflicts upon using the th23 Admin class with multiple plugins, you have to specify the version expected ie the one your plugin has been developed with, eg replacing "v000" below by "v170" */
			$admin = new th23_admin_v000($this);
		}
		if(!empty($this->admin)) {
			add_action('init', array(&$this, 'setup_admin_class'));
			// alternative update source for non-WP.org hosted plugin
			/* optional: must point towards an update.json file, see th23 Plugin Info class, leave unset or empty for no separate update server, then all (regular) checks target main WP.org repository
			* important: WP.org plugin repository does not allow plugins including own update sources - to comply remove the two lines about "update_url" and "site_transient_update_plugins"
			*/
			$this->plugin['update_url'] = 'https://github.com/th23x/th23-contact/releases/latest/download/update.json';
			add_filter('site_transient_update_plugins', array(&$this->admin, 'update_download'));
		}
		else {
			add_action('admin_notices', array(&$this, 'error_admin_class'));
		}

		// Load plugin options
		/* mandatory: this will bring in all your settings, see description for function below */
		// note: earliest possible due to localization only available at "init" hook
		add_action('init', array(&$this, 'init_options'));

		// Check requirements
		/* optional: function to check plugin requirements and populate requirement_notices array
		tip: see "function requirements" further below for an example on possible checks and how to populate the array properly
		*/
		add_action('init', array(&$this, 'requirements'), 100);

		// Install/ uninstall
		/* optional: functions to execute specific code upon activation / desctivation of the plugin
		tip: see "function install" / "function uninstall" further below for an example
		*/
		add_action('activate_' . $this->plugin['basename'], array(&$this, 'install'));
		add_action('deactivate_' . $this->plugin['basename'], array(&$this, 'uninstall'));

		// ... optional: hooks for plugin functionality in admin area ...

	}

	/* required: Setup of th23 Admin class
	note: language strings passed on to admin class are optional - in case not provided by plugin the admin class language strings are only available in English
	*/
	function setup_admin_class() {

		// enhance plugin info with generic plugin data
		// note: make sure function exists as it is loaded late only, if at all - see https://developer.wordpress.org/reference/functions/get_plugin_data/
		if(!function_exists('get_plugin_data')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		$this->plugin['data'] = get_plugin_data($this->plugin['file']);

		// admin class is language agnostic, except translations in parent i18n variable
		// note: need to populate $this->i18n earliest at init hook to get user locale
		$this->i18n = array(
			// reviewer: to keep consistency some admin language strings are used in sync with core
			'Settings' => __('Settings'),
			/* translators: parses in version number */
			'Version %s' => __('Version %s'),
			/* translators: parses in plugin name */
			'Copy from %s' => __('Copy from %s', 'th23-example'),
			'Support' => __('Support'),
			'Done' => __('Done'),
			'Settings saved.' => __('Settings saved.'),
			'+' => __('+'),
			'-' => __('-'),
			'Save Changes' => __('Save Changes'),
			/* translators: parses in author */
			'By %s' => __('By %s'),
			'View details' => __('View details'),
			'Visit plugin site' => __('Visit plugin site'),
			'Error' => __('Error'),
			/* translators: 1: option name, 2: opening a tag of link to support/ plugin page, 3: closing a tag of link */
			'Invalid combination of input field and default value for "%1$s" - please %2$scontact the plugin author%3$s' => __('Invalid combination of input field and default value for "%1$s" - please %2$scontact the plugin author%3$s', 'th23-example'),
			/* translators: parses in repository url for non-WP.org hosted plugin */
			'Updated via %s' => __('Updated via %s', 'th23-example'),
			/* translators: parses in plugin information source url */
			'Failed to load plugin information from %s' => __('Failed to load plugin information from %s', 'th23-example'),
		);

	}
	function error_admin_class() {
		/* translators: parses in names of 1: class which failed to load */
		echo '<div class="notice notice-error"><p style="font-size: 14px;"><strong>' . esc_html($this->plugin['data']['Name']) . '</strong></p><p>' . esc_html(sprintf(__('Failed to load %1$s class', 'th23-example'), 'th23 Admin')) . '</p></div>';
	}

	// Load plugin options
	function init_options() {

		// Settings: Screen options
		/* optional: allows user in admin to customize the settings screen
		note: default can handle boolean, integer or string
		user selected choice is added as CSS class to the settings form:
		- for true/false: eg "th23-admin-screen-option-hide_description" class is added (true) / not present (false)
		- for integer/string: eg "th23-admin-screen-option-hide_description-VALUE" is added, where blank spaces in the VALUE are replaced by "_" (underscore)
		note: "hide_description" is supported out-of-the-box as the required CSS is part of the default th23-admin-class.css file
		*/
		$this->plugin['screen_options'] = array(
			'hide_description' => array(
				'title' => __('Hide settings descriptions', 'th23-example'),
				'default' => false,
			),
		);

		// Settings: Help
		/* optional: populate help tabs and sidebar in the plugin settings screen
		note: there can be multiple "help_tabs", but only one "help_sidebar"
		note: usage of certain HTML tags for links and formatting is possible - supported are <a href="" title="" class="" target=""></a>, <strong></strong>, <em></em>, <span class="" style="" title=""></span> where not all arguments must be used in each tag
		*/
		/* translators: parses in opening/closing tags of link */
		$content = '<p>' . sprintf(__('You can find video tutorials explaning the plugin settings on %1$smy YouTube channel%2$s.', 'th23-example'), '<a href="https://www.youtube.com/channel/UCS3sNYFyxhezPVu38ESBMGA">', '</a>') . '</p>';
		/* translators: parses in opening/closing tags of links */
		$content .= '<p>' . sprintf(__('More details and explanations are available on %1$smy Frequently Asked Questions (FAQ) page%2$s or the %3$splugin support section on WordPress.org%4$s.', 'th23-example'), '<a href="https://th23.net/th23-example-support/">', '</a>', '<a href="https://wordpress.org/support/plugin/th23-example/">', '</a>') . '</p>';
		$this->plugin['help_tabs'] = array(
			'help-overview' => array(
				'title' => __('Settings and support', 'th23-example'),
				'content' => $content,
			),
		);
		/* translators: parses in opening/closing tags of links */
		$this->plugin['help_sidebar'] = '<p>' . sprintf(__('Support me by %1$sleaving a review%2$s or check out some of %3$smy other plugins%4$s', 'th23-example'), '<a href="https://wordpress.org/support/plugin/th23-example/reviews/#new-post">', '</a>', '<a href="https://wordpress.org/plugins/search/th23/">', '</a>') . '</p>';

		// Settings: Define plugin options
		$this->plugin['options'] = array();

		/* required: definition of plugin options - each with its unique "plugin_option_ident" string
		"plugin_option_ident" is also used to access the option value:
		$options = (array) get_option($this->plugin['slug']);
		$value = $options['plugin_option_ident'];

		note: following overview show ALL available settings with explanations, while examples further down show the "normal" usage with much smaller amount of code required...
		*/
		$this->plugin['options']['plugin_option_ident'] = array(

			/* optional: start a new section with a separate heading by adding a "section" with section title
			*/
			'section' => __('Section title', 'th23-example'),

			/* optional: sections can have a short description displayed under the section title
			note: usage of certain HTML tags for links, formatting and some specifics is possible - supported are <a href="" title="" class="" target=""></a>, <strong></strong>, <em></em>, <span class="" style="" title=""></span>, <br />, <code></code> and <input type="" /> where not all arguments must be used in each tag
			*/
			'section_description' => __('Section description', 'th23-example'),

			/* required: title of the setting, shown on the left hand side of the admin screen next to the input field
			*/
			'title' => __('Posts', 'th23-example'),

			/* optional: description of the setting, shown below the input field on the right hand side of the admin screens
			note: usage of certain HTML tags for links, formatting and some specifics is possible - supported are <a href="" title="" class="" target=""></a>, <strong></strong>, <em></em>, <span class="" style="" title=""></span>, <br />, <code></code> and <input type="" /> where not all arguments must be used in each tag
			*/
			'description' => $subscriptions_removal_warning,

			/* optional: name of a function provding a more complex rendering of the setting, eg showing a currently selected image together with alternative options to chose from
			arguments:
			1) $default_value of the setting - as specified in "default", see below
			2) $current_value of the setting
			tip: see "function watermark_image" for an example
			note: alternatively this can also be used to show some content before the input element and therefore use an anonymous function - see second "render" line below
			*/
			'render' => 'watermark_image',
			'render' => function() { return '<label for="input_spam_engine[]">' . __('Spam detection engine', 'th23-example') . '</label> '; },

			/* optional: defines the input element shown to the user to select the settings value
			available options:
			1) "input" (default, can be omitted) - shows a standard text input field
			2) "checkbox"
			3) "radio"
			4) "list"
			5) "dropdown"
			6) "hidden"
			7) "textarea"
			*/
			'element' => 'input_type',

			/* required: default of the option, if no user choice is given or the user choice is invalid - can be a text string or integer as well as an array, can also be empty "" or 0 but NOT be null
			note: first element in an array as default specifies if one ("single") or more ("multiple") elements can be selected at once via the element index and the value specifies the default be it text or number of the following items
			following example shows a simple yes/no choice, ideally shown as a "checkbox" element (see above)
			note: for "multiple" define an array of values ie "multiple => array('test')" to default to this value
			tip: "checkbox" elements with a "single => 0" or "single => 1" default can be used to structure leveled settings (see "attributes" / "data-childs" below) eg revealing username/password field only if user selects to upload something automatically
			tip: "template" is a special case of default allowing to describe options in form of table (see example "template" further down)
			*/
			'default' => array(
				'single' => 1,
				0 => '',
				1 => __('Allow users to subscribe to new posts being published', 'th23-example'),
			),

			/* optional: unit of settings value shown next to the input field on the right hand side eg "px" for pixels, "%" for percentages, ...
			*/
			'unit' => __('day(s)', 'th23-example'),

			/* optional: additional attributes added to the input field
			note: can be any attribute => value combination resulting in <input attribute="value" ...> html tag - see a few common examples below
			tip: "data-childs" is an option to make other subsequent options a leveled sub-group of the current option eg revealing username/password settings only, if user selects to upload something automatically via a checkbox (see "element" above) - allows this to be a comma-separated list of options each in the form of ".option-plugin_option_ident" (with plugin_option_ident as specified for the sub-option)
			*/
			'attributes' => array(
				'class' => 'small-text', // large-text
				'data-childs' => '.option-global_preselected,.option-yet_another_suboption',
				'disabled' => 'disabled',
				'size' => 8,
			),

			/* optional: allow users to add own set along the "template"
			note: only has an effect together with "template" being defined as "default" value (see "default" above and "template" example further below)
			*/
			'extendable' => true, // [optional, see 'template' example below]

			/* optional: execute defined function to evaluate / change user selected new settings BEFORE being stored in the database
			note: passes on 2 arguments
			1) $new_options - array of options as selected / defined by the user
			2) $current_options - array of options as stored in the database
			return: expects function to return (un-)changed $new_options array which will be stored in the database
			tip: see "function admin_save_global_subscriptions" for an example
			*/
			'save_before' => 'admin_save_global_subscriptions',

			/* optional: execute defined function to further process / prepare things based on user selected new settings AFTER being stored in the database
			note: passes on 2 arguments
			1) $new_options - array of options as updated into the database
			2) $current_options - array of options as these have been before the update
			return: expects function to return (un-)changed $new_options array which will be used as options for the remaining script execution
			tip: see "function save_admin_email" for an example
			*/
			'save_after' => 'save_admin_email',

			/* optional: allows a setting value to be "shared" across multiple plugins installed
			note: provides option to copy in the value as it is used with another plugin, but it does NOT automatically use the value from the other plugin, thus allowing the user to use different setting values, while promoting to use the same values
			tip: see real life example from "th23 Contact" plugin further below
			*/
			'shared' => true,

		);

		// example: for a "template" option eg allowing to enable/disable some social media platforms for sharing
		$this->plugin['options']['template_option'] = array(
			// ... see other option details above ...
			'default' => array(
				'template' => array(
					/* note: following lines within the "template" array define the columns of the template that are the same for each entry provided further down below or added by the user in case the "extendable" setting is allowed
					*/
					'name' => array(
						'title' => __('Name', 'th23-example'),
						'description' => __('Required', 'th23-example'),
						'default' => '',
					),
					'css_class' => array(
						'title' => __('CSS class', 'th23-example'),
						'default' => '',
					),
					'order' => array(
						'title' => __('Order', 'th23-example'),
						'description' => __('Order in which the services show up (same numbers result in order as shown here)', 'th23-example'),
						'default' => 0,
						'attributes' => array(
							'class' => 'small-text',
						),
					),
					'follow_active' => array(
						'title' => __('Followable', 'th23-example'),
						'element' => 'checkbox',
						'default' => array(
							'single' => 0,
							0 => '',
							1 => ' ', /* note: not empty to show this checkbox option */
						),
					),
				),
				/* note: from here default entries ie rows prefilling the "template" which are NOT user changable
				tip: for pre-fills changable by user see "presets" further down below
				*/
				'facebook' => array(
					'css_class' => 'f',
				),
				'tiktok' => array(
					'css_class' => 't',
				),
				// ... further prefills ...
			),
			/* note: allow user to add "rows" to the "template", but not columns
			*/
			'extendable' => true,
		);

		// example: defining presets for a "template" which can be changed by the user eg names of the social media service shown
		$this->plugin['presets'] = array(
			'template_option' => array(
				'facebook' => array(
					'name' => 'facebook',
				),
				// ... further changable settings in the "template" ...
			)
			// ... further changable settings for other "templates" ...
		);

		// example: for a shortest notation of a plugin option, storing a string value as "resize_suffix"
		$this->plugin['options']['resize_suffix'] => array(
			'title' => __('Resize suffix', 'th23-example'),
			'default' => '',
		);

		// example: real life example from "th23 Contact" plugin - see x) and a) to l) following
		// x) terms of usage link / title (used more than once)
		$terms_title = (empty($terms_title = get_option('th23_terms_title'))) ? __('Terms of Usage', 'th23-contact') : $terms_title;
		$terms_link = (!empty($terms_url = get_option('th23_terms_url'))) ? '<a href="' . esc_url($terms_url) . '" target="_blank">' . $terms_title . '</a>' : $terms_title;
		// a) post_ids
		$description = __('Limit usage of contact shortcode to selected pages / posts, reducing unnecessary CSS loading - leave empty to use on all pages and posts', 'th23-contact');
		/* translators: inserts shortcode within "<code></code>" tags */
		$description .= '<br />' . sprintf(__('Important: Requires contact shortcode %s placed in page / post to show contact form', 'th23-contact'), '<code style="font-style: normal;">[th23-contact]</code>');
		$description .= '<br />' . __('Note: Shortcode can only be used once per page / post', 'th23-contact');
		$this->plugin['options']['post_ids'] = array(
			'title' => __('Pages / Posts', 'th23-contact'),
			'element' => 'list',
			'default' => array(
				'multiple' => array(''),
				'pages' => __('All pages', 'th23-contact'),
				'posts' => __('All posts', 'th23-contact'),
			),
			'description' => $description,
			'attributes' => array(
				'size' => 8,
			),
		);
		$pages = get_pages();
		foreach($pages as $page) {
			/* translators: parses in page title */
			$this->plugin['options']['post_ids']['default'][$page->ID] = esc_attr(sprintf(__('Page: %s', 'th23-contact'), wp_strip_all_tags($page->post_title)));
		}
		$posts = get_posts(array('numberposts' => -1, 'orderby' => 'post_title', 'order' => 'ASC'));
		foreach($posts as $post) {
			/* translators: parses in post title */
			$this->plugin['options']['post_ids']['default'][$post->ID] = esc_attr(sprintf(__('Post: %s', 'th23-contact'), wp_strip_all_tags($post->post_title)));
		}
		// b) admin_email
		$admin_email = get_option('admin_email');
		$this->plugin['options']['admin_email'] = array(
			'title' =>  __('Recipient', 'th23-contact'),
			'default' => '',
			'shared' => true,
			/* translators: %1$s / %2$s <a> and </a> tags for link to insert admin mail, %3$s current general admin e-mail address within "<code></code>" tags */
			'description' => sprintf(__('Provide mail address for contact form submissions - %1$sclick here%2$s to use your default admin e-mail address %3$s', 'th23-contact'), '<a href="" class="copy" data-target="input_admin_email" data-copy="' . esc_attr($admin_email) . '">', '</a>', '<code>' . esc_attr($admin_email) . '</code>'),
			'save_after' => 'save_admin_email',
		);
		// c) pre_subject
		$this->plugin['options']['pre_subject'] = array(
			'title' =>  __('Subject prefix', 'th23-contact'),
			'default' => '',
			'description' => __('Optional prefix to be added before the subject of mails sent from the contact form', 'th23-contact'),
		);
		// d) spam_check
		$this->plugin['options']['spam_check'] = array(
			'title' => __('Spam check', 'th23-contact'),
			'element' => 'checkbox',
			'default' => array(
				'single' => 0,
				0 => '',
				1 => __('Check contact message for spam before sending', 'th23-contact'),
			),
			'attributes' => array(
				'data-childs' => '.option-spam_engine,.option-spam_key',
			),
		);
		// e) spam_engine
		$description = __('Note: Selected spam detection engine does not need to be installed as plugin', 'th23-contact');
		/* translators: parses in link with/or title to sites terms & conditions, as defined by admin */
		$description .= '<br />' . sprintf(__('Note: Consider to include according information into %s', 'th23-contact'), $terms_link);
		$this->plugin['options']['spam_engine'] = array(
			'render' => function() { return '<label for="input_spam_engine[]">' . __('Spam detection engine', 'th23-contact') . '</label> '; },
			'element' => 'dropdown',
			'default' => array(
				'single' => 'akismet',
				/* translators: 1: name of service eg "Akismet", 2: provider name eg "Automattic" */
				'akismet' => sprintf(__('%1$s by %2$s', 'th23-contact'), 'Akismet', 'Automattic'),
			),
			'description' => $description,
		);
		// f) spam_key
		$this->plugin['options']['spam_key'] = array(
			'render' => function() { return '<label for="input_spam_key">' . __('API key', 'th23-contact') . '</label> '; },
			'default' => '',
			/* translators: parses in name of service, sentence will be follow by a half sentence how to obtain the key eg " - get it from..." */
			'description' => sprintf(__('Important: %s requires valid API key', 'th23-contact'), '<em>Akismet</em>'),
		);
		// g) spam_key_verified
		$this->plugin['options']['spam_key_verified'] = array(
			'default' => '',
			'element' => 'hidden',
			'save_before' => 'save_spam_key_verified',
		);
		// h) visitors
		$this->plugin['options']['visitors'] = array(
			'title' => __('Visitors', 'th23-contact'),
			'element' => 'checkbox',
			'default' => array(
				'single' => 0,
				0 => '',
				1 => __('Enable contact form for unregistered users', 'th23-contact'),
			),
			'description' => __('If disabled, unregistered visitors will see a notice requiring them to login for sending a message', 'th23-contact'),
			'attributes' => array(
				'data-childs' => '.option-captcha,.option-terms',
			),
		);
		// i) captcha
		$this->plugin['options']['captcha'] = array(
			'title' => '<i>reCaptcha</i>',
			'element' => 'checkbox',
			'default' => array(
				'single' => 0,
				0 => '',
				1 => __('Unregistered users need to solve a captcha for better protection against spam and bots', 'th23-contact'),
			),
			/* translators: 1: "reCaptcha v2" as name of the service, 2: "Google" as provider name, 3/4: opening and closing tags for a link to Google reCaptcha website */
			'description' => sprintf(__('Important: %1$s is an external service by %2$s which requires %3$ssigning up for free keys%4$s - usage will embed external scripts and transfer data to %2$s', 'th23-contact'), '<em>reCaptcha v2</em>', '<em>Google</em>', '<a href="https://www.google.com/recaptcha/" target="_blank">', '</a>'),
			'attributes' => array(
				'data-childs' => '.option-captcha_public,.option-captcha_private',
			),
			'save_after' => 'save_captcha',
		);
		// j) captcha_public
		$this->plugin['options']['captcha_public'] = array(
			'title' => __('Public Key', 'th23-contact'),
			'default' => '',
			'shared' => true,
		);
		// k) captcha_private
		$this->plugin['options']['captcha_private'] = array(
			'title' => __('Secret Key', 'th23-contact'),
			'default' => '',
			'shared' => true,
		);
		// l) terms
		$description = '<a href="" class="toggle-switch">' . __('Show / hide examples', 'th23-contact') . '</a>';
		$description .= '<span class="toggle-show-hide" style="display: none;"><br />' . __('Example:', 'th23-contact');
		/* translators: parses in link with/or title to sites terms & conditions, as defined by admin */
		$description .= '&nbsp;<input type="checkbox" />' . sprintf(__('I accept the %s and agree with processing my data', 'th23-contact'), $terms_link);
		/* translators: parses in link to general options page in admin */
		$description .= '<br />' . sprintf(__('Note: For changing title and link shown see %s', 'th23-contact'), '<a href="options-general.php#th23_terms">' . __('General Settings') . '</a>');
		$description .= '</span>';
		$this->plugin['options']['terms'] = array(
			'title' => __('Terms', 'th23-contact'),
			'element' => 'checkbox',
			'default' => array(
				'single' => 0,
				0 => '',
				1 => __('Unregistered users are required to accept terms of usage before sending their message', 'th23-contact'),
			),
			'description' => $description,
		);

		// optional: define presets for template option values (pre-filled, but changable by user)
		$this->plugin['presets'] = array();

	}

	// example: for certain code to be executed upon new "install" of the plugin, eg ensure certain presets are prepared and some intial settings values are added to the database
	function install() {

		// Prefill values in an option template, keeping them user editable (and therefore not specified in the default value itself)
		// need to check, if items exist(ed) before and can be reused - so we dont' overwrite them (see uninstall with delete_option inactive)
		if(isset($this->plugin['presets'])) {
			if(!isset($this->options) || !is_array($this->options)) {
				$this->options = array();
			}
			$this->options = array_merge($this->plugin['presets'], $this->options);
		}
		// Set option values
		update_option($this->plugin['slug'], $this->adm->get_options($this->options));
		$this->options = (array) get_option($this->plugin['slug']);

	}

	// example: for certain code to be executed upon "uninstall" of the plugin, eg remove plugin settings from database
	function uninstall() {

		// NOTICE: To keep all settings etc in case the plugin is reactivated, return right away - if you want to remove previous settings and data, comment out the following line!
		return;

		// Delete option values
		delete_option($this->plugin['slug']);

	}

	// example: checks for "requirements" - filling $this->plugin['requirement_notices'] with entries in case of any issues detected
	function requirements() {
		// check requirements only on relevant admin pages
		global $pagenow;
		if(empty($pagenow)) {
			return;
		}
		if('index.php' == $pagenow) {
			// admin dashboard
			$context = 'admin_index';
		}
		elseif('plugins.php' == $pagenow) {
			// plugins overview page
			$context = 'plugins_overview';
		}
		elseif($this->plugin['settings']['base'] == $pagenow && !empty($_GET['page']) && $this->plugin['slug'] == $_GET['page']) {
			// plugin settings page
			$context = 'plugin_settings';
		}
		else {
			return;
		}

		// plugin not designed for multisite setup
		if(is_multisite()) {
			$this->plugin['requirement_notices']['multisite'] = '<strong>' . __('Warning', 'th23-example') . '</strong>: ' . __('Your are running a multisite installation - the plugin is not designed for this setup and therefore might not work properly', 'th23-example');
		}

		// e-mail address as recipient for contact form requests must be given
		if(empty($this->options['admin_email']) || !is_email($this->options['admin_email'])) {
			$this->plugin['requirement_notices']['admin_email'] = '<strong>' . __('Error', 'th23-example') . '</strong>: ' . __('No valid e-mail address is specified as recipient - despite your settings the plugin will be disabled until you specify one', 'th23-example');
		}

		// reCaptcha requires a public and private key to work
		if(!empty($this->options['captcha']) && (empty($this->options['captcha_public']) || empty($this->options['captcha_private']))) {
			$notice = '<strong>' . __('Error', 'th23-example') . '</strong>: ';
			/* translators: Parses in "reCaptcha v2" as service name */
			$notice .= sprintf(__('%s requires a public and a private key to work - despite your settings it will be disabled until you define them', 'th23-example'), '<i>reCaptcha v2</i>');
			$this->plugin['requirement_notices']['captcha'] = $notice;
		}

		// allow further checks (without re-assessing $context)
		do_action('th23_example_requirements', $context);

	}

	// example: for "render" of an option (see option definitions above), eg showing current watermark image and upload input field in plugin settings
	function watermark_image($default, $current_watermark) {
		$upload_dir = wp_get_upload_dir();
		$watermark_file = '/th23-example/' . $current_watermark;
		$html = '<div class="th23-example-watermark-image">';
		if(is_file($upload_dir['basedir'] . $watermark_file)) {
			$html .= '<img src="' . $upload_dir['baseurl'] . $watermark_file . '" />';
		}
		else {
			$html .= '<div class="th23-example-watermark-placeholder">' . __('Select watermark', 'th23-example') . '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	// example: for "save_before" of an option (see option definitions above), eg removing user subscriptions, if subscription to new posts is not allowed anymore
	function admin_save_global_subscriptions($new_options, $options_unfiltered) {
		if(empty($new_options['global_subscriptions']) && !empty($options_unfiltered['global_subscriptions'])) {
			global $wpdb;
			$sql = $wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'th23_example_subscriptions WHERE item = %s', 'global');
			$wpdb->query($sql);
		}
		return $new_options;
	}

	// example: for "save_after" of an option (see option definitions above), eg ensuring a valid admin address has been defined as recipient of contact request, and show a warning in case it is not
	function save_admin_email($new_options, $options_unfiltered) {
		// re-check requirement, as latest save (executed) after prerequisites check, might have changed things
		if(empty($this->options['admin_email']) || !is_email($this->options['admin_email'])) {
			$this->plugin['requirement_notices']['admin_email'] = '<strong>' . esc_html__('Error', 'th23-example') . '</strong>: ' . esc_html__('No valid e-mail address is specified as recipient - despite your settings the plugin will be disabled until you specify one', 'th23-example');
		}
		else {
			unset($this->plugin['requirement_notices']['admin_email']);
		}
		return $new_options;
	}

	// ... optional: functions for plugin functionality in admin area ...

}

?>
