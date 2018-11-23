<?php
/**
 * WPBushido
 *
 * @package   WPBushido
 * @author    Philippe AUTIER <philippe.autier@koomma.fr>
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:    	  WPBushido
 * Description:       Wordpress Plugin Helper (Timber / ACF)
 * Version:           1.0.2
 * Author:            Koomma
 * Author URI:        https://www.koomma.fr
 * Text Domain:       wppi
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function($class) {
    $namespaces = explode('\\', $class);
    $project = reset($namespaces);

    if ($project == 'WPBushido') {
        unset($namespaces[0]);
        $path = null;
        while (($namespace = current($namespaces)) !== false) {
            $path .= DIRECTORY_SEPARATOR . $namespace;
            next($namespaces);
        }
        $path .= '.php';

        if (file_exists(plugin_dir_path(__FILE__) . 'includes' . $path)) {
            require (plugin_dir_path(__FILE__) . 'includes' .  $path);
        }
    }
});

require_once(plugin_dir_path(__FILE__) .'plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://wppi.guichet.download/release/plugin.json',
	__FILE__,
	'wppi-helper-plugin'
);

add_action('init', function() {
    WPBushido\Security\Settings::setup();
});

