<?php
/*
th23 Admin
Basic admin functionality
Version: 1.5.1

Coded 2024-2025 by Thorsten Hartmann (th23)
https://th23.net/

see th23-example-admin.php file for documentation of options
*/

// Security - exit if accessed directly
if(!defined('ABSPATH')) {
    exit;
}

class th23_admin {

	public $version = '1.5.1';

	private $parent;
	private $kses = array();

	function __construct($parent) {

		// get parent information
		$this->parent = $parent;

		// define allowed html tags for some elements
		$this->kses['label'] = array('label' => array('for' => array()));
		// note: data-target, data-copy and data-shared attributes are used for cross-plugin and suggested settings
		$this->kses['link'] = array('a' => array('href' => array(), 'title' => array(), 'class' => array(), 'target' => array(), 'data-target' => array(), 'data-shared' => array(), 'data-copy' => array()));
		$this->kses['format'] = array('strong' => array(), 'em' => array(), 'span' => array('style' => array(), 'title' => array(), 'class' => array()));
		$this->kses['link_format'] = array_merge($this->kses['link'], $this->kses['format']);
		$this->kses['description'] = array_merge($this->kses['link_format'], array('br' => array(), 'code' => array(), 'input' => array('type' => array())));

		// note: priority 20 ensures hooking actions after parent has pepared admin class setup
		add_action('init', array(&$this, 'actions'), 20);

	}

	// Hook actions
	function actions() {

		// Requirements
		add_action('admin_notices', array(&$this, 'requirement_notices'));

		// Modify plugin overview page
		add_filter('plugin_action_links_' . $this->parent->plugin['basename'], array(&$this, 'settings_link'), 10);
		add_filter('plugin_row_meta', array(&$this, 'contact_link'), 10, 2);

		// Add settings page and JS/ CSS
		if(!empty($this->parent->plugin['settings'])) {
			add_action('admin_init', array(&$this, 'register_admin_js_css'));
			add_action('admin_menu', array(&$this, 'add_admin'));
			add_action('wp_ajax_th23_admin_screen_options', array(&$this, 'set_screen_options'));
		}

	}

	// Localization for language agnostic admin class
	function __($string = '') {
		return (!empty($this->parent->i18n[$string])) ? $this->parent->i18n[$string] : $string;
	}

	// Requirements - show requirement notices on admin dashboard
	function requirement_notices() {
		global $pagenow;
		if(!empty($pagenow) && 'index.php' == $pagenow && !empty($this->parent->plugin['requirement_notices'])) {
			echo '<div class="notice notice-error">';
			echo '<p style="font-size: 14px;"><strong>' . esc_html($this->parent->plugin['data']['Name']) . '</strong></p>';
			foreach($this->parent->plugin['requirement_notices'] as $notice) {
				echo '<p>' . wp_kses($notice, $this->kses['link_format']) . '</p>';
			}
			echo '</div>';
		}
	}

	// Add settings link to plugin actions in plugin overview page
	function settings_link($links) {
		if(!empty($this->parent->plugin['settings'])) {
			$links['settings'] = '<a href="' . esc_url($this->parent->plugin['settings']['base'] . '?page=' . $this->parent->plugin['slug']) . '">' . esc_html($this->__('Settings')) . '</a>';
		}
		return $links;
	}

	// Add supporting information (eg links and notices) to plugin row in plugin overview page
	// note: Any CSS styling needs to be "hardcoded" here as plugin CSS might not be loaded (e.g. when plugin deactivated)
	function contact_link($links, $file) {
		if($this->parent->plugin['basename'] == $file) {
			// Add support link
			if(!empty($this->parent->plugin['support_url'])) {
				$links[] = '<a href="' . esc_url($this->parent->plugin['support_url']) . '">' . esc_html($this->__('Support')) . '</a>';
			}
			// Show warning, if installation requirements are not met - add it after/ to last link
			if(!empty($this->parent->plugin['requirement_notices'])) {
				$notices = '';
				foreach($this->parent->plugin['requirement_notices'] as $notice) {
					$notices .= '<div style="margin: 1em 0; padding: 5px 10px; background-color: #FFFFFF; border-left: 4px solid #DD3D36; box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);">' . wp_kses($notice, $this->kses['link_format']) . '</div>';
				}
				$last = array_pop($links);
				$links[] = $last . $notices;
			}
		}
		return $links;
	}

	// Add settings page and JS/ CSS
	// Register admin JS and CSS
	function register_admin_js_css() {
		wp_register_script('th23-admin-class', $this->parent->plugin['dir_url'] . 'inc/th23-admin-class.js', array('jquery'), $this->parent->plugin['version'], true);
		wp_register_style('th23-admin-class', $this->parent->plugin['dir_url'] . 'inc/th23-admin-class.css', array(), $this->parent->plugin['version']);
	}

	// Register admin page in admin menu/ prepare loading admin JS and CSS/ trigger screen options and help
	function add_admin() {
		$page = add_submenu_page($this->parent->plugin['settings']['base'], $this->parent->plugin['data']['Name'], $this->parent->plugin['data']['Name'], $this->parent->plugin['settings']['permission'], $this->parent->plugin['slug'], array(&$this, 'show_admin'));
		add_action('admin_print_scripts-' . $page, array(&$this, 'load_admin_js'));
		add_action('admin_print_styles-' . $page, array(&$this, 'load_admin_css'));
		if(!empty($this->parent->plugin['screen_options'])) {
			add_action('load-' . $page, array(&$this, 'add_screen_options'));
		}
		if(!empty($this->parent->plugin['help_tabs'])) {
			add_action('load-' . $page, array(&$this, 'add_help'));
		}
	}

	// Load admin JS
	function load_admin_js() {
		wp_enqueue_script('th23-admin-class');
	}

	// Load admin CSS
	function load_admin_css() {
		wp_enqueue_style('th23-admin-class');
	}

	// Handle screen options
	function add_screen_options() {
		add_filter('screen_settings', array(&$this, 'show_screen_options'), 10, 2);
	}
	function show_screen_options($html, $screen) {
		$html .= '<div id="th23-admin-screen-options">';
		$html .= '<input type="hidden" id="th23-admin-screen-options-nonce" value="' . wp_create_nonce('th23-admin-screen-options-nonce') . '" />';
		$html .= $this->get_screen_options(true);
		$html .= '</div>';
		return $html;
	}
	function get_screen_options($html = false) {
		if(empty($this->parent->plugin['screen_options'])) {
			return array();
		}
		if(empty($user = get_user_meta(get_current_user_id(), 'th23_admin_screen_options-' . $this->parent->plugin['slug'], true))) {
			$user = array();
		}
		$screen_options = ($html) ? '' : array();
		foreach($this->parent->plugin['screen_options'] as $option => $details) {
			$type = gettype($details['default']);
			$value = (isset($user[$option]) && gettype($user[$option]) == $type) ? $user[$option] : $details['default'];
			if($html) {
				$name = 'th23_admin_screen_options_' . $option;
				$class = 'th23-admin-screen-option-' . $option;
				if('boolean' == $type) {
					$checked = (!empty($value)) ? ' checked="checked"' : '';
					$screen_options .= '<fieldset class="' . $name . '"><label><input name="' . $name .'" id="' . $name .'" value="1" type="checkbox"' . $checked . ' data-class="' . $class . '">' . esc_html($details['title']) . '</label></fieldset>';
				}
				elseif('integer' == $type) {
					$min_max = (isset($details['range']['min'])) ? ' min="' . $details['range']['min'] . '"' : '';
					$min_max .= (isset($details['range']['max'])) ? ' max="' . $details['range']['max'] . '"' : '';
					$screen_options .= '<fieldset class="' . $name . '"><label for="' . $name . '">' . esc_html($details['title']) . '</label><input id="' . $name . '" name="' . $name . '" type="number"' . $min_max . ' value="' . $value . '" data-class="' . $class . '" /></fieldset>';
				}
				elseif('string' == $type) {
					$screen_options .= '<fieldset class="' . $name . '"><label for="' . $name . '">' . esc_html($details['title']) . '</label><input id="' . $name . '" name="' . $name . '" type="text" value="' . esc_attr($value) . '" data-class="' . $class . '" /></fieldset>';
				}
			}
			else {
				$screen_options[$option] = $value;
			}
		}
		return $screen_options;
	}
	// update user preference for screen options via AJAX
	function set_screen_options() {
		if(!empty($_POST['nonce']) || wp_verify_nonce($_POST['nonce'], 'th23-admin-screen-options-nonce')) {
			$screen_options = $this->get_screen_options();
			$new = array();
			foreach($screen_options as $option => $value) {
				$name = 'th23_admin_screen_options_' . $option;
				if('boolean' == gettype($value)) {
					if(empty($_POST[$name])) {
						$screen_options[$option] = $value;
					}
					elseif('true' == $_POST[$name]) {
						$screen_options[$option] = true;
					}
					else {
						$screen_options[$option] = false;
					}
				}
				else {
					$screen_options[$option] = (!empty($_POST[$name])) ? settype(sanitize_text_field($_POST[$name]), gettype($value)) : $value;
				}
			}
			update_user_meta(get_current_user_id(), 'th23_admin_screen_options-' . $this->parent->plugin['slug'], $screen_options);
		}
		wp_die();
	}

	// Add help
	function add_help() {
		$screen = get_current_screen();
		foreach($this->parent->plugin['help_tabs'] as $id => $details) {
			$screen->add_help_tab(array(
				'id' => $this->parent->plugin['slug'] . '-' . $id,
				'title' => $details['title'],
				'content' => $details['content'],
			));
		}
		if(!empty($this->parent->plugin['help_sidebar'])) {
			$screen->set_help_sidebar($this->parent->plugin['help_sidebar']);
		}
	}

	// Get validated plugin options
	function get_options($options = array(), $html_input = false) {
		$checked_options = array();
		foreach($this->parent->plugin['options'] as $option => $option_details) {
			$default = $option_details['default'];
			// default array can be template or allowing multiple inputs
			$default_value = $default;
			$type = '';
			if(is_array($default)) {
				$default_value = reset($default);
				$type = key($default);
			}

			// if we have a template, pass all values for each element through the check against the template defaults
			if($type == 'template') {
				unset($default['template']);
				// create complete list of all elements - those from previous settings (re-activation), overruled by (most recent) defaults and merged with any possible user input
				$elements = array_keys($default);
				if($html_input && !empty($option_details['extendable']) && !empty($_POST['input_' . $option . '_elements'])) {
					$elements = array_merge($elements, explode(',', sanitize_text_field($_POST['input_' . $option . '_elements'])));
				}
				else {
					$elements = array_merge(array_keys($options[$option]), $elements);
				}
				$elements = array_unique($elements);
				// loop through all elements - and validate previous / user values
				$checked_options[$option] = array();
				$sort_elements = array();
				foreach($elements as $element) {
					$checked_options[$option][$element] = array();
					// loop through all (sub-)options
					foreach($default_value as $sub_option => $sub_option_details) {
						$sub_default = $sub_option_details['default'];
						$sub_default_value = $sub_default;
						$sub_type = '';
						if(is_array($sub_default)) {
							$sub_default_value = reset($sub_default);
							$sub_type = key($sub_default);
						}
						unset($value);
						// force pre-set options for elements given in default
						if(isset($default[$element][$sub_option])) {
							$value = $default[$element][$sub_option];
						}
						// html input
						elseif($html_input) {
							if(isset($_POST['input_' . $option . '_' . $element . '_' . $sub_option])) {
								// if only single value allowed, only take first element from value array for validation
								if($sub_type == 'single' && is_array($_POST['input_' . $option . '_' . $element . '_' . $sub_option])) {
									$value = stripslashes(sanitize_text_field(reset($_POST['input_' . $option . '_' . $element . '_' . $sub_option])));
								}
								else {
									$value = stripslashes(sanitize_text_field($_POST['input_' . $option . '_' . $element . '_' . $sub_option]));
								}
							}
							// avoid empty items filled with default - will be filled with default in case empty/0 is not allowed for single by validation
							elseif($sub_type == 'multiple') {
								$value = array();
							}
							elseif($sub_type == 'single') {
								$value = '';
							}
						}
						// previous value
						elseif(isset($options[$option][$element][$sub_option])) {
							$value = $options[$option][$element][$sub_option];
						}
						// in case no value is given, take default
						if(!isset($value)) {
							$value = $sub_default_value;
						}
						// verify and store value
						$value = $this->get_valid_option($sub_default, $value);
						$checked_options[$option][$element][$sub_option] = $value;
						// prepare sorting
						if($sub_option == 'order') {
							$sort_elements[$element] = $value;
						}
					}
				}
				// sort verified elements according to order field (after validation to sort along valid order values)
				if(isset($default_value['order'])) {
					asort($sort_elements);
					$sorted_elements = array();
					foreach($sort_elements as $element => $null) {
						$sorted_elements[$element] = $checked_options[$option][$element];
					}
					$checked_options[$option] = $sorted_elements;
				}
			}
			// normal input fields
			else {
				unset($value);
				// html input
				if($html_input) {
					if(isset($_POST['input_' . $option])) {
						// if only single value allowed, only take first element from value array for validation
						if($type == 'single' && is_array($_POST['input_' . $option])) {
							$value = stripslashes(sanitize_text_field(reset($_POST['input_' . $option])));
						}
						elseif($type == 'multiple' && is_array($_POST['input_' . $option])) {
							$value = array();
							foreach($_POST['input_' . $option] as $key => $val) {
								$value[$key] = stripslashes(sanitize_text_field($val));
							}
						}
						else {
							$value = stripslashes(sanitize_text_field($_POST['input_' . $option]));
						}
					}
					// avoid empty items filled with default - will be filled with default in case empty/0 is not allowed for single by validation
					elseif($type == 'multiple') {
						$value = array();
					}
					elseif($type == 'single') {
						$value = '';
					}
				}
				// previous value
				elseif(isset($options[$option])) {
					$value = $options[$option];
				}
				// in case no value is given, take default
				if(!isset($value)) {
					$value = $default_value;
				}
				// check value defined by user
				$checked_options[$option] = $this->get_valid_option($default, $value);
			}
		}
		return $checked_options;
	}

	// Validate / type match value against default
	function get_valid_option($default, $value) {
		if(is_array($default)) {
			$default_value = reset($default);
			$type = key($default);
			unset($default[$type]);
			if($type == 'multiple') {
				// note: multiple selections / checkboxes can be empty
				$valid_value = array();
				foreach($value as $selected) {
					// force allowed type - determined by first default element / no mixed types allowed
					if(gettype($default_value[0]) != gettype($selected)) {
						settype($selected, gettype($default_value[0]));
					}
					// check against allowed values - including type check
					if(isset($default[$selected])) {
						$valid_value[] = $selected;
					}
				}
			}
			else {
				// force allowed type - determined default value / no mixed types allowed
				if(gettype($default_value) != gettype($value)) {
					settype($value, gettype($default_value));
				}
				// check against allowed values
				if(isset($default[$value])) {
					$valid_value = $value;
				}
				// single selections (radio buttons, dropdowns) should have a valid value
				else {
					$valid_value = $default_value;
				}
			}
		}
		else {
			// force allowed type - determined default value
			if(gettype($default) != gettype($value)) {
				settype($value, gettype($default));
			}
			$valid_value = $value;
		}
		return $valid_value;
	}

	// Show admin page
	function show_admin() {

		$form_classes = array();

		// Open wrapper and show plugin header
		echo '<div class="wrap th23-admin-options ' . esc_attr($this->parent->plugin['slug']) . '-options">';

		// Header - logo / plugin name
		echo '<h1>';
		if(!empty($this->parent->plugin['icon']['horizontal'])) {
			echo '<img class="icon" src="' . esc_url($this->parent->plugin['dir_url'] . $this->parent->plugin['icon']['horizontal']) . '" alt="' . esc_attr($this->parent->plugin['data']['Name']) . '" />';
		}
		else {
			echo esc_html($this->parent->plugin['data']['Name']);
		}
		echo '</h1>';

		// Get screen options, ie user preferences - and build CSS class
		if(!empty($this->parent->plugin['screen_options'])) {
			$screen_options = $this->get_screen_options();
			foreach($screen_options as $option => $value) {
				if($value === true) {
					$form_classes[] = 'th23-admin-screen-option-' . $option;
				}
				elseif(!empty($value)) {
					$form_classes[] = 'th23-admin-screen-option-' . $option . '-' . esc_attr(str_replace(' ', '_', $value));
				}
			}
		}

		// start form
		echo '<form method="post" enctype="multipart/form-data" id="th23-admin-options" action="' . esc_url($this->parent->plugin['settings']['base'] . '?page=' . $this->parent->plugin['slug']) . '" class="' . esc_attr(implode(' ', $form_classes)) . '">';

		// Do update of plugin options if required
		if(!empty($_POST['th23-admin-options-do'])) {
			check_admin_referer('th23_admin_settings', 'th23-admin-settings-nonce');

			$new_options = $this->get_options($this->parent->options, true);
			// always keep current plugin stored as (invisible) option value to be able to detect updates
			$new_options['version'] = $this->parent->plugin['version'];
			// re-acquire options from DB to ensure we check against unfiltered options (in case filters are allowed somewhere)
			$options_unfiltered = (array) get_option($this->parent->plugin['slug']);
			if($new_options != $options_unfiltered) {

				// save_before filters option values (option to modify user input after verification against default, but before saving, normally what you want - for examples see th23 Subscribe plugin)
				foreach($this->parent->plugin['options'] as $option => $option_details) {
					if(!empty($option_details['save_before']) && method_exists($this->parent, $option_details['save_before'])) {
						$save = $option_details['save_before'];
						$new_options = $this->parent->$save($new_options, $options_unfiltered);
					}
				}
				update_option($this->parent->plugin['slug'], $new_options);
				$this->parent->options = $new_options;

				// save_after filters option values (option to modify changes option values after saving and reloading them, rather for special cases - for examples see th23 Social plugin using this to reload presets into default and re-apply filters as well as to recreate changed image sizes)
				foreach($this->parent->plugin['options'] as $option => $option_details) {
					if(!empty($option_details['save_after']) && method_exists($this->parent, $option_details['save_after'])) {
						$save = $option_details['save_after'];
						$this->parent->options = $this->parent->$save($this->parent->options, $options_unfiltered);
					}
				}

				// shared x-plugin
				$shared_options = get_option('th23_shared');
				foreach($this->parent->plugin['options'] as $option => $option_details) {
					if(!empty($option_details['shared'])) {
						if(!empty($this->parent->options[$option]) && (empty($shared_options[$option]) || $this->parent->options[$option] != $shared_options[$option]['value'])) {
							$shared_options[$option] = array('value' => $this->parent->options[$option], 'plugin' => $this->parent->plugin['data']['Name']);
						}
						elseif(empty($this->parent->options[$option]) && !empty($shared_options[$option]) && $shared_options[$option]['plugin'] == $this->parent->plugin['data']['Name']) {
							unset($shared_options[$option]);
						}
					}
				}
				update_option('th23_shared', $shared_options);

				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html($this->__('Done')) . '</strong>: ' . esc_html($this->__('Settings saved.')) . '</p><button class="notice-dismiss" type="button"></button></div>';

			}
		}

		// Show warnings, if requirements are not met
		if(!empty($this->parent->plugin['requirement_notices'])) {
			foreach($this->parent->plugin['requirement_notices'] as $notice) {
				echo '<div class="notice notice-error"><p>' . wp_kses($notice, $this->kses['link_format']) . '</p></div>';
			}
		}

		// Show plugin settings
		// start table
		echo '<table class="form-table"><tbody>';

		// collect all children options - and the no shows
		$child_list = '';
		$sub_child_list = '';
		$no_show_list = '';

		// loop through all options
		foreach($this->parent->plugin['options'] as $option => $option_details) {

			// add children options and no shows
			if(isset($option_details['element']) && $option_details['element'] == 'checkbox' && !empty($option_details['attributes']['data-childs'])) {
				// if the current option itself is on the child list, then the options in data-childs are sub childs
				if(strpos($child_list, 'option-' . $option . ',') !== false) {
					$sub_child_list .= $option_details['attributes']['data-childs'] . ',';
				}
				// otherwise we have first level children
				else {
					$child_list .= $option_details['attributes']['data-childs'] . ',';
				}
				if(empty($this->parent->options[$option]) || strpos($no_show_list, 'option-' . $option . ',') !== false) {
					$no_show_list .= $option_details['attributes']['data-childs'] . ',';
				}
			}
			// assign proper child or sub-child class - for proper indent
			$child_class = '';
			if(strpos($child_list, 'option-' . $option . ',') !== false) {
				$child_class = ' child';
			}
			elseif(strpos($sub_child_list, 'option-' . $option . ',') !== false) {
				$child_class = ' sub-child';
			}
			// prepare show/hide style for current element
			$no_show_style = (strpos($no_show_list, 'option-' . $option . ',') !== false) ? 'display: none;' : '';

			$key = '';
			if(is_array($option_details['default'])) {
				$default_value = reset($option_details['default']);
				$key = key($option_details['default']);
				unset($option_details['default'][$key]);
				if($key == 'template') {

					echo '</tbody></table>';
					echo '<div class="option option-template option-' . esc_attr($option . $child_class) . '" style="' . esc_attr($no_show_style) . '">';
					echo '<h2>' . esc_html($option_details['title']) . '</h2>';
					if(!empty($option_details['description'])) {
						echo '<p class="section-description">' . wp_kses($option_details['description'], $this->kses['description']) . '</p>';
					}
					echo '<table class="option-template"><tbody>';

					// create template headers
					echo '<tr>';
					foreach($default_value as $sub_option => $sub_option_details) {
						$hint_open = '';
						$hint_close = '';
						if(isset($sub_option_details['description'])) {
							$hint_open = '<span class="hint" title="' . esc_attr($sub_option_details['description']) . '">';
							$hint_close = '</span>';
						}
						echo '<th class="' . esc_attr($sub_option) . '">' . wp_kses($hint_open . $sub_option_details['title'] . $hint_close, $this->kses['format']) . '</th>';
					}
					// show add button, if template list is user editable
					if(!empty($option_details['extendable'])) {
						echo '<td class="template-actions"><button type="button" id="template-add-' . esc_attr($option) . '" value="' . esc_attr($option) . '">' . esc_html($this->__('+')) . '</button></td>';
					}
					echo '</tr>';
					// get elements for rows - and populate hidden input (adjusted by JS for adding/ deleting rows)
					$elements = array_keys(array_merge($this->parent->options[$option], $option_details['default']));
					// sort elements array according to order field
					if(isset($default_value['order'])) {
						$sorted_elements = array();
						foreach($elements as $element) {
							$sorted_elements[$element] = (isset($this->parent->options[$option][$element]['order'])) ? $this->parent->options[$option][$element]['order'] : 0;
						}
						asort($sorted_elements);
						$elements = array_keys($sorted_elements);
					}

					// add list of elements and empty row as source for user inputs - filled with defaults
					if(!empty($option_details['extendable'])) {
						echo '<input id="input_' . esc_attr($option) . '_elements" name="input_' . esc_attr($option) . '_elements" value="' . esc_attr(implode(',', $elements)) . '" type="hidden" />';
						$elements[] = 'template';
					}

					// show template rows
					foreach($elements as $element) {
						echo '<tr id="' . esc_attr($option) . '-' . esc_attr($element) . '">';
						foreach($default_value as $sub_option => $sub_option_details) {
							echo '<td>';
							// get sub value default - and separate any array to show as sub value
							$sub_key = '';
							if(is_array($sub_option_details['default'])) {
								$sub_default_value = reset($sub_option_details['default']);
								$sub_key = key($sub_option_details['default']);
								unset($sub_option_details['default'][$sub_key]);
							}
							else {
								$sub_default_value = $sub_option_details['default'];
							}
							// force current value to be default and disable input field for preset elements / fields (not user changable / editable)
							if(isset($option_details['default'][$element][$sub_option])) {
								// set current value to default (not user-changable)
								$this->parent->options[$option][$element][$sub_option] = $option_details['default'][$element][$sub_option];
								// disable input field
								if(!isset($sub_option_details['attributes']) || !is_array($sub_option_details['attributes'])) {
									$sub_option_details['attributes'] = array();
								}
								$sub_option_details['attributes']['disabled'] = 'disabled';
								// show full value in title, as field is disabled and thus sometimes not scrollable
								$sub_option_details['attributes']['title'] = esc_attr($this->parent->options[$option][$element][$sub_option]);
							}
							// set to template defined default, if not yet set (eg options added via filter before first save)
							elseif(!isset($this->parent->options[$option][$element][$sub_option])) {
								$this->parent->options[$option][$element][$sub_option] = $sub_default_value;
							}
							// build and show input field
							$html = $this->build_input_field($option . '_' . $element . '_' . $sub_option, $sub_option_details, $sub_key, $sub_default_value, $this->parent->options[$option][$element][$sub_option]);
							if(!empty($html)) {
								/* reviewer: html content is html escaped at source */
								echo $html;
							}
							echo '</td>';
						}
						// show remove button, if template list is user editable and element is not part of the default set
						if(!empty($option_details['extendable'])) {
							echo '<td class="template-actions">' . ((empty($this->parent->plugin['options'][$option]['default'][$element]) || $element == 'template') ? '<button type="button" id="template-remove-' . esc_attr($option) . '-' . esc_attr($element) . '" value="' . esc_attr($option) . '" data-element="' . esc_attr($element) . '">' . esc_html($this->__('-')) . '</button>' : '') . '</td>';
						}
						echo '</tr>';
					}

					echo '</tbody></table>';
					echo '</div>';
					echo '<table class="form-table"><tbody>';

					continue;

				}
			}
			else {
				$default_value = $option_details['default'];
			}

			// separate option sections - break table(s) and insert heading
			if(!empty($option_details['section'])) {
				echo '</tbody></table>';
				echo '<h2 class="option option-section option-' . esc_attr($option . $child_class) . '" style="' . esc_attr($no_show_style) . '">' . esc_html($option_details['section']) . '</h2>';
				if(!empty($option_details['section_description'])) {
					echo '<p class="section-description">' . wp_kses($option_details['section_description'], $this->kses['description']) . '</p>';
				}
				echo '<table class="form-table"><tbody>';
			}

			// Build input field and output option row
			if(!isset($this->parent->options[$option])) {
				// might not be set upon fresh activation
				$this->parent->options[$option] = $default_value;
			}
			$html = $this->build_input_field($option, $option_details, $key, $default_value, $this->parent->options[$option]);
			if(!empty($html)) {
				echo '<tr class="option option-' . esc_attr($option . $child_class) . '" valign="top" style="' . esc_attr($no_show_style) . '">';
				$option_title = $option_details['title'];
				if(!isset($option_details['element']) || ($option_details['element'] != 'checkbox' && $option_details['element'] != 'radio')) {
					$brackets = (isset($option_details['element']) && ($option_details['element'] == 'list' || $option_details['element'] == 'dropdown')) ? '[]' : '';
					$option_title = '<label for="input_' . $option . $brackets . '">' . $option_title . '</label>';
				}
				echo '<th scope="row">' . wp_kses($option_title, $this->kses['label']) . '</th>';
				echo '<td><fieldset>';
				// Rendering additional field content via callback function
				// passing on to callback function as parameters: $default_value = default value, $this->parent->options[$option] = current value
				if(!empty($option_details['render']) && method_exists($this->parent, $option_details['render'])) {
					$render = $option_details['render'];
					/* reviewer: rendered content is html escaped at source */
					echo $this->parent->$render($default_value, $this->parent->options[$option]);
				}
				/* reviewer: html content is html escaped at source */
				echo $html;
				if(!empty($option_details['description'])) {
					echo '<span class="description">' . wp_kses($option_details['description'], $this->kses['description']) . '</span>';
				}
				echo '</fieldset></td>';
				echo '</tr>';
			}

		}

		// end table
		echo '</tbody></table>';
		echo '<br/>';

		// submit
		echo '<input type="hidden" name="th23-admin-options-do" value=""/>';
		echo '<input type="button" id="th23-admin-options-submit" class="button-primary th23-admin-options-submit" value="' . esc_attr($this->__('Save Changes')) . '"/>';
		wp_nonce_field('th23_admin_settings', 'th23-admin-settings-nonce');

		echo '<br/>';

		// Plugin information
		echo '<div class="th23-admin-about">';
		if(!empty($this->parent->plugin['icon']['square'])) {
			echo '<img class="icon" src="' . esc_url($this->parent->plugin['dir_url'] . $this->parent->plugin['icon']['square']) . '" alt="' . esc_attr($this->parent->plugin['data']['Name']) . '" /><p>';
		}
		else {
			echo '<p><strong>' . esc_html($this->parent->plugin['data']['Name']) . '</strong>' . ' | ';
		}
		/* translators: parses in plugin version number */
		echo esc_html(sprintf($this->__('Version %s'), $this->parent->plugin['version']));
		/* translators: parses in plugin author name / link */
		echo ' | ' . wp_kses(sprintf($this->__('By %s'), $this->parent->plugin['data']['Author']), $this->kses['link']);
		if(!empty($this->parent->plugin['support_url'])) {
			echo ' | <a href="' . esc_url($this->parent->plugin['support_url']) . '">' . esc_html($this->__('Support')) . '</a>';
		}
		elseif(!empty($this->parent->plugin['data']['PluginURI'])) {
			echo ' | <a href="' . esc_url($this->parent->plugin['data']['PluginURI']) . '">' . esc_html($this->__('Visit plugin site')) . '</a>';
		}
		echo '</p></div>';

		// Close form and wrapper
		echo '</form>';
		echo '</div>';

	}

	// Create admin input field
	// note: uses the chance to point out any invalid combinations for element and validation options
	function build_input_field($option, $option_details, $key, $default_value, $current_value) {

		if(!isset($option_details['element'])) {
			$option_details['element'] = 'input';
		}
		$element_name = 'input_' . $option;
		$element_attributes = array();
		if(!isset($option_details['attributes']) || !is_array($option_details['attributes'])) {
			$option_details['attributes'] = array();
		}
		$element_attributes_suggested = array();
		$valid_option_field = true;
		if($option_details['element'] == 'checkbox') {
			// exceptional case: checkbox allows "single" default to handle (yes/no) checkbox
			if(empty($key) || ($key == 'multiple' && !is_array($default_value)) || ($key == 'single' && is_array($default_value))) {
				$valid_option_field = false;
			}
			$element_name .= '[]';
			$element_attributes['type'] = 'checkbox';
		}
		elseif($option_details['element'] == 'radio') {
			if(empty($key) || $key != 'single' || is_array($default_value)) {
				$valid_option_field = false;
			}
			$element_name .= '[]';
			$element_attributes['type'] = 'radio';
		}
		elseif($option_details['element'] == 'list') {
			if(empty($key) || $key != 'multiple' || !is_array($default_value)) {
				$valid_option_field = false;
			}
			$element_name .= '[]';
			$element_attributes['multiple'] = 'multiple';
			$element_attributes_suggested['size'] = '5';
		}
		elseif($option_details['element'] == 'dropdown') {
			if(empty($key) || $key != 'single' || is_array($default_value)) {
				$valid_option_field = false;
			}
			$element_name .= '[]';
			$element_attributes['size'] = '1';
		}
		elseif($option_details['element'] == 'hidden') {
			if(!empty($key)) {
				$valid_option_field = false;
			}
			$element_attributes['type'] = 'hidden';
		}
		else {
			if(!empty($key)) {
				$valid_option_field = false;
			}
			$element_attributes_suggested['type'] = 'text';
			$element_attributes_suggested['class'] = 'regular-text';
		}
		// no valid option field, due to missmatch of input field and default value
		if(!$valid_option_field) {
			$support_open = '';
			$support_close = '';
			if(!empty($this->parent->plugin['support_url'])) {
				$support_open = '<a href="' . esc_url($this->parent->plugin['support_url']) . '">';
				$support_close = '</a>';
			}
			elseif(!empty($this->parent->plugin['data']['PluginURI'])) {
				$support_open = '<a href="' . esc_url($this->parent->plugin['data']['PluginURI']) . '">';
				$support_close = '</a>';
			}
			echo '<div class="notice notice-error"><p><strong>' . esc_html($this->__('Error')) . '</strong>: ';
			/* translators: 1: option name, 2: opening a tag of link to support/ plugin page, 3: closing a tag of link */
			echo wp_kses(sprintf($this->__('Invalid combination of input field and default value for "%1$s" - please %2$scontact the plugin author%3$s'), $option, $support_open, $support_close), $this->kses['link']);
			echo '</p></div>';
			return '';
		}

		$html = '';

		// handle repetitive elements (checkboxes and radio buttons)
		if($option_details['element'] == 'checkbox' || $option_details['element'] == 'radio') {
			$html .= '<div>';
			// special handling for single checkboxes (yes/no)
			$checked = ($option_details['element'] == 'radio' || $key == 'single') ? array($current_value) : $current_value;
			foreach($option_details['default'] as $value => $text) {
				// special handling for yes/no checkboxes
				if(!empty($text)){
					$html .= '<div><label><input name="' . $element_name . '" id="' . $element_name . '_' . $value . '" value="' . $value . '" ';
					foreach(array_merge($element_attributes_suggested, $option_details['attributes'], $element_attributes) as $attr => $attr_value) {
						$html .= $attr . '="' . $attr_value . '" ';
					}
					$html .= (in_array($value, $checked)) ? 'checked="checked" ' : '';
					$html .= '/>' . $text . '</label></div>';
				}
			}
			$html .= '</div>';
		}
		// handle repetitive elements (dropdowns and lists)
		elseif($option_details['element'] == 'list' || $option_details['element'] == 'dropdown') {
			$html .= '<select name="' . $element_name . '" id="' . $element_name . '" ';
			foreach(array_merge($element_attributes_suggested, $option_details['attributes'], $element_attributes) as $attr => $attr_value) {
				$html .= $attr . '="' . $attr_value . '" ';
			}
			$html .= '>';
			$selected = ($option_details['element'] == 'dropdown') ? array($current_value) : $current_value;
			foreach($option_details['default'] as $value => $text) {
				$html .= '<option value="' . $value . '"';
				$html .= (in_array($value, $selected)) ? ' selected="selected"' : '';
				$html .= '>' . $text . '</option>';
			}
			$html .= '</select>';
			if($option_details['element'] == 'dropdown' && !empty($option_details['unit'])) {
				$html .= '<span class="unit">' . $option_details['unit'] . '</span>';
			}
		}
		// textareas
		elseif($option_details['element'] == 'textarea') {
			$html .= '<textarea name="' . $element_name . '" id="' . $element_name . '" ';
			foreach(array_merge($element_attributes_suggested, $option_details['attributes'], $element_attributes) as $attr => $attr_value) {
				$html .= $attr . '="' . $attr_value . '" ';
			}
			$html .= '>' . esc_textarea($current_value) . '</textarea>';
		}
		// simple (self-closing) inputs
		else {
			// shared x-plugin
			$shared = '';
			if(!empty($option_details['shared'])) {
				$shared_options = get_option('th23_shared');
				if(!empty($shared_options[$option]) && $current_value != $shared_options[$option]['value'] && $this->parent->plugin['data']['Name'] != $shared_options[$option]['plugin']) {
					$html .= '<span class="shared-option"></span>';
					$shared = '<span class="shared dashicons dashicons-edit" data-target="' . $element_name . '" data-shared="' . $shared_options[$option]['value'] . '" title="' . esc_html(sprintf($this->__('Copy from %s'), $shared_options[$option]['plugin'])) . '"></span>';
				}
			}
			$html .= '<input name="' . $element_name . '" id="' . $element_name . '" ';
			foreach(array_merge($element_attributes_suggested, $option_details['attributes'], $element_attributes) as $attr => $attr_value) {
				$html .= $attr . '="' . $attr_value . '" ';
			}
			$html .= 'value="' . esc_attr($current_value) . '" />';
			if(!empty($option_details['unit'])) {
				$html .= '<span class="unit">' . $option_details['unit'] . '</span>';
			}
			// shared x-plugin
			$html .= $shared;
		}

		return $html;

	}

}

?>
