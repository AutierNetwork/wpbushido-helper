<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Client;

class Pages
{
    public static function getRenderTwig($template, $context, $type = false)
    {
        $context['use_tpl'] = $template;
        //$context = self::renderSeo($template, $context, $type);
        if (isset($context['error_404']) && !empty($context['error_404'])) {
            $context['use_tpl'] = '404.html.twig';
        }
        // 404 ERROR
        if ($context['use_tpl'] == '404.html.twig') {
            header_remove('Cache-Control');
            header_remove('Pragma');
            header_remove('Expires');
            header_remove('Status');
            status_header(404);

        }
        return $context;
    }
}