<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Page;

class Page
{

    /**
     *
     * @var $checkSeoFields
     */
    public static $checkSeoFields = array('title', 'description', 'keywords', 'image', 'main_title');

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

    /**
     * Build menu from WP Page
     *
     * @param int $parent
     * @return array $returnPages with all menu items
     */
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

    /**
     * Get Post template name
     *
     * @param int $post_id
     * @return string $tpl name of the page template
     */
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

    /**
     * Get Seo information
     *
     * @param array $context : timber context
     * @param int $forceParent
     * @return array $return all seo information
     */
    public static function getSeo($context, $forceParent = 0)
    {
        if (!isset($context['page_seo'])) {
            $return = array();
        }
        else {
            $return = $context['page_seo'];
        }
        $pageId = false;
        if ($forceParent) {
            $pageId = $forceParent;
        }
        else if (isset($context['page_id']) && !empty($context['page_id'])) {
            $pageId = $context['page_id'];
        }
        if ($pageId) {
            $acfFields = get_fields($pageId);
            if (isset($acfFields['seo_parent_synchronisation']) && $acfFields['seo_parent_synchronisation'] == true && $pageId != intval(get_option('page_on_front'))) {
                $parentID = wp_get_post_parent_id($pageId);
                if ($parentID == 0) {
                    $parentID = intval(get_option('page_on_front'));
                }
                $return = self::getSeo($context, $parentID);
            }
            else {
                foreach (self::$checkSeoFields as $field) {
                    if (isset($acfFields['seo_' . $field]) && !empty($acfFields['seo_' . $field])) {
                        $return['seo_' . $field] = self::checkSeoString($acfFields['seo_' . $field], $context);
                    }
                    if (isset($acfFields['seo_' . $field . '_detail']) && !empty($acfFields['seo_' . $field . '_detail'])) {
                        $return['seo_' . $field . '_detail'] = self::checkSeoString($acfFields['seo_' . $field . '_detail'], $context);
                    }
                    if (isset($acfFields['seo_' . $field . '_form']) && !empty($acfFields['seo_' . $field . '_form'])) {
                        $return['seo_' . $field . '_form'] = self::checkSeoString($acfFields['seo_' . $field . '_form'], $context);
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Process on SEO data
     *
     * @param string $string : string SEO to proceed
     * @param array $context : timber context
     * @return string $string string with all changes
     */
    public static function checkSeoString($string, $context)
    {
        $arrayStopWords = array(
            'item'
        );
        $findStepWordsExpression = array();
        preg_match_all('(\#[^#]+\#)', $string, $matches);
        if (isset($matches[0])) {
            $matches = $matches[0];
        }
        foreach ($matches as $match) {
            $cleanStepWords = $match;
            $cleanStepClean = '';
            preg_match('(\(.*\))', $match, $matchesInto);
            foreach ($matchesInto as $matchesIntoItem) {
                $cleanStepWords = str_replace(array($matchesIntoItem, '#'), array('',''), $match);
                $cleanStepClean = str_replace(array('(',')'), array('', ''), $matchesIntoItem);
            }
            $findStepWordsExpression[] = array('stepword' => $cleanStepWords, 'expression' => $cleanStepClean, 'original' => $match);
        }
        foreach ($findStepWordsExpression as $stepWordK) {
            $stepWord = $stepWordK['stepword'];
            $expressionWord = $stepWordK['expression'];
            $originalWords = $stepWordK['original'];
            $replaceValue = '';
            if (in_array($stepWord, $arrayStopWords)) {
                $value = '';
                if (!empty($value)) {
                    if (!empty($expressionWord)) {
                        $replaceValue = str_replace('%s', $value, $expressionWord);
                    } else {
                        $replaceValue = $value;
                    }
                }
            }
            $string = str_replace($originalWords, $replaceValue, $string);
        }
        $string = trim($string);
        $string = str_replace('  ', ' ', $string);
        return $string;
    }
}