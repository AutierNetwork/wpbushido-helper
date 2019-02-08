<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Admin;

use Timber\Timber;

class AdminPage
{
    public static function registerHooks()
    {

    }

    public static function displayAdmin($incomingContext, $timberContext = false, $plugin_path = false)
    {
        $timber = new Timber();
        if (!$timberContext) {
            $context = $timber::get_context();
        }
        else {
            $context = array();
        }
        $context = array_merge($context, $incomingContext);
        if (!$plugin_path) {
            $plugin_path = plugin_dir_path( __FILE__ );
        }
        $pathViews = $plugin_path.'../../ressources/views';
        $timber::$locations = $pathViews;
        $timber::render($context['use_tpl'], $context);
    }
}