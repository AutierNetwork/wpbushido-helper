<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Security;

class Settings
{
    /**
     * Setup Security settings methods
     *
     * @return void
     */
    public static function setup()
    {
        /* Disable Author Enumeration, cf. https://perishablepress.com/stop-user-enumeration-wordpress/ */
        self::disableAuthorEnumeration();

        /* Avoid login hints when in error ("wrong login" or "wrong password") */
        self::mitigateLoginHints();
        
        /* Load Options page */
        self::addOptionsPageSite();

        add_filter('acf/settings/save_json', array('WPBushido\Security\Settings', 'acfJsonSavePoint'));
        add_filter('acf/settings/load_json', array('WPBushido\Security\Settings', 'acfJsonLoadPoint'));
    }

    /**
     * Mitigate Login hints when error
     *
     * @return void
     */
    public static function mitigateLoginHints()
    {
        add_filter('login_errors', function() {
            return __('Wrong login and/or password.');
        });
    }

    /**
     * Block Author enumeration
     *
     * @return void
     */
    public static function disableAuthorEnumeration()
    {
        if (!is_admin()) {
            // default URL format
            if (isset($_SERVER['QUERY_STRING']) && preg_match('/author=([0-9]*)/i', $_SERVER['QUERY_STRING'])) {
                die();
            }
            add_filter('redirect_canonical', array('\WPBushido\Security\Settings', 'shapeSpaceCheckEnum'), 10, 2);
        }
    }

    /**
     * Check redirect of author
     *
     * @param string $redirect
     * @param object $request
     * @return void
     */
    public static function shapeSpaceCheckEnum($redirect, $request) {
        if (preg_match('/\?author=([0-9]*)(\/*)/i', $request)) {
            die();
        } else {
            return $redirect;
        }
    }

    /**
     * ACF parameters
     */
    public static function acfJsonSavePoint($path)
    {
        // update path
        $path = get_stylesheet_directory() . '/acf-json';

        // return
        return $path;
    }

    /**
     * ACF parameters
     */
    public static function acfJsonLoadPoint($paths)
    {
        // remove original path (optional)
        unset($paths[0]);

        // append path
        $paths[] = get_stylesheet_directory() . '/acf-json';

        // return
        return $paths;
    }

    /**
     * ACF Options page : Site (Global)
     */
    public static function addOptionsPageSite()
    {
        if (is_admin()) {
            $user = wp_get_current_user();
            $roles = $user->roles;

            if (in_array('administrator', $roles)) {
                if (function_exists('acf_add_options_page')) {
                    $optionsSite = acf_add_options_page(array(
                        'page_title' => __('Options générales'),
                        'menu_title' => __('Options générales'),
                        'menu_slug' => 'general-settings-site',
                        'icon_url' => 'dashicons-admin-generic',
                    ));
                }
            }
        }
    }


}
