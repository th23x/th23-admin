# 🛠️ th23 Admin

Admin framework / boilerplate with easy re-usable functionality for WordPress plugins


## 🚀 Introduction

Adding some **single options** to an **existing settings page** in the WP admin area is easy using the "[register_setting](https://developer.wordpress.org/reference/functions/register_setting/)" and "[add_settings_field](https://developer.wordpress.org/reference/functions/add_settings_field/)" functions.

But building your own dedicated **plugin settings page** and manage various **settings depending on each other**, ie layered, with some options only relevant depending on a previous choice, can be a more challenging task to achive.

Therefore I developed this comprehensive admin class for my plugins allowing to **quickly define** a relevant and **well structured** settings page for each of my plugins.

The `th23 Admin` class allows you to
* Store all settings into one WP option in the database - for **easy loading and access** during plugin execution
* Define plugin settings via one central array - allowing for a **wide range of default inputs** eg text, radio buttons, checkboxes, dropdowns, lists, as well as **custom rendered content** eg images to select from, etc
* Show **settings and support** opportunities prominently on the plugin overview page as well as the plugin settings page
* Leverage prepared **install / uninstall** hooks - to execute code when your plugin is added / removed
* Enable **plugin update detection** via plugin version stored with settings - to allow specific code running after updates to eg change database structure or previous settings
* Add **screen options and help tabs** to your plugins settings page


## ⚙️ Setup

The folder / file structure of your WordPress plugin when adding `th23 Admin` class should look something like this:
```
inc/
   th23-admin-class.php
   th23-admin-class.js
   th23-admin-class.css
th23-example.php
th23-example-admin.php
...
```

The `/inc` folder contains the whole `th23 Admin` script and its accompanying JS and CSS files, thus being separated from your plugin code and keeping it easy to change / update later on.

In the `th23-example.php` as the main plugin file with your plugins functionality simply add the following code to setup / initialize the `th23 Admin` class:
```
$th23_example_path = plugin_dir_path(__FILE__);

if(is_admin() && file_exists($th23_example_path . 'th23-example-admin.php')) {
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
```

> [!TIP]
> This is the most minimal example, ideally the you use a frontend class for your plugin as well, which defines some variables used on both frontend and backend. In such a setup the admin class extends the frontend class and both together can be initialized.
>
> See `th23-example-admin.php` file for more about loading the admin class separately or as extension of the frontend...


## 🖐️ Usage

Define your plugin settings upon loading the `th23 Admin` class from your plugins admin file (`th23-example-admin.php` in the example above).

See `th23-example-admin.php` file in this repository for an extensive description of the possibilities - no worries, your file will usually be much shorter as many options can be defined much swifter and without such extensive comments, eg like this one:
```
// example: for shortest notation of a plugin option, storing a string value as "resize_suffix"
$this->plugin['options']['resize_suffix'] => array(
	'title' => __('Resize suffix', 'th23-example'),
	'default' => '',
);
```

> [!TIP]
> Ensure you have covered everything labeled as `required:` in the `th23-example-admin.php` file, while other things are `optional:` or even only `èxamples:` you can look at!

**Access setting values** in your main plugin by simply using the default `get_option` function, which returns an indexed array, with the indexes being the defined `plugin_option_ident` names:
```
$options = (array) get_option($this->plugin['slug']);

print_r($options); // echo options array
```


## 🤝 Contributors

Feel free to [raise issues](https://github.com/th23x/th23-admin/issues) or [contribute code](https://github.com/th23x/th23-admin/pulls) for improvements via GitHub.


## ©️ License

You are free to use this code in your projects as per the `GNU General Public License v3.0`. References to this repository are of course very welcome in return for my work 😉
