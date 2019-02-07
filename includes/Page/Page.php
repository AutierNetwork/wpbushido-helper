<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Client;

class Page
{
    /**
     * Render Template to check 404 error
     *
     * @param string $template
     * @param array $context
     * @param boolean $type
     * @return array $context
     */
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

    /**
     * Check equal conditional value
     *
     * @param mixed $valueToTest
     * @param mixed $value
     * @return boolean (true or false)
     */
    public static function conditionEqual($valueToTest, $value)
    {
        return $valueToTest == $value;
    }

    /**
     * Check inferior conditional value
     *
     * @param int $valueToTest
     * @param int $value
     * @return boolean (true or false)
     */
    public static function conditionInferior($valueToTest, $value)
    {
        return $valueToTest < $value;
    }

    /**
     * Check superior conditional value
     *
     * @param int $valueToTest
     * @param int $value
     * @return boolean (true or false)
     */
    public static function conditionSuperior($valueToTest, $value)
    {
        return $valueToTest > $value;
    }

    /**
     * Parse and clean incoming REQUEST (post and get) parameters
     *
     * @return array $return - cleaned request data
     */
    public static function checkIncomingAjax()
    {
        $escapeParams = array(
            'action',
            'render'
        );
        $request = $_REQUEST;
        $return = array();
        foreach ($request as $reqK => $reqV) {
            if (!in_array($reqK, $escapeParams)) {
                $return[$reqK] = self::cleanIncomingArg($reqV);
            }
        }
        return $return;
    }

    /**
     * Check superior conditional value
     *
     * @param int $valueToTest
     * @param int $value
     * @return boolean (true or false)
     */
    public static function cleanIncomingArg($arg)
    {
        $argToClean = $arg;
        if (!is_array($argToClean)) {
            $arg = sanitize_text_field($argToClean);
        }
        else {
            $arg = array_map('sanitize_text_field', $argToClean);
        }
        $arg = $argToClean;
        return $arg;
    }

    public static function getMenuPages($parent = 0)
    {
        // Get Main Page
        $args = array(
            'sort_order' => 'asc',
            'post_parent' => $parent,
            'post_type' => 'page',
            'numberposts' => -1,
            'post_status' => 'publish',
        );
        /*if (!$noMenu) {
            $args['meta_query'] = array(
                'relation'    => 'OR',
                array(
                    'key'   => 'header',
                    'value'     => '1',
                    'compare'   => '=',
                ),
                array(
                    'key'     => 'footer',
                    'value'     => '1',
                    'compare'   => '=',
                ),
            );
        }*/
        $pages = get_posts($args);
        $returnPages = array();
        foreach ($pages as $pageK => $pageV) {
            $tplName = self::getTplName($pageV->ID);
            $returnPages[$pageV->post_name] = array(
                'template' => $tplName,
                'page_id' => $pageV->ID,
                'url' => get_permalink($pageV->ID)
            );
        }
        return $returnPages;
    }

    public static function getTplName($post_id)
    {
        $tpl = get_page_template_slug($post_id);
        if (!empty($tpl)) {
            $tpl = str_replace(array('controller/', '.php'), array('', ''), $tpl);
        }
        else {
            $tpl = false;
        }
        return $tpl;
    }
}