<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Client;

class Client extends \Timber\Site
{

    public function __construct() {
        \Timber::$dirname = 'templates';
        /*if (WP_TIMBER_USE_CACHE == true) {
            \Timber::$cache = true;
        }*/
        add_filter('timber_context', array($this, 'addToContext'));
        add_filter('get_twig', array($this, 'addToTwig'));
        add_action('wp_ajax_nopriv_timber_ajax', array($this, 'timberAjax'));
        add_action('wp_ajax_timber_ajax', array($this, 'timberAjax'));
        //add_filter('timber/cache/location', array($this, 'customTimberTwigCache'));
        parent::__construct();
    }

    /*public function customTimberTwigCache() {
        return WP_TIMBER_CACHE_DIR . '/timber';
    }*/

    public function addToContext($context) {

        $pageId = get_queried_object_id();
        // get level of the page
        $context['site'] = $this;

        if (isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id'])) {
            switch_to_blog(intval($_REQUEST['site_id']));
            $context['site'] = new \Timber\Site(intval($_REQUEST['site_id']));
        }

        if ($pageId) {
            $context['page_id'] = $pageId;
            $context['page_object'] = new \Timber\Post($pageId);
            $context['page_acf'] = get_fields($pageId);
            $context['is_home_page'] = is_front_page();
        }
        $context['options'] = get_fields('option');

        return $context;
    }

    public function addToTwig(\Twig_Environment $twig) {
        $twig->addExtension(new \Twig_Extension_StringLoader() );
        $twig->addFunction(new \Timber\Twig_Function('getTemplateAssetFunction', array($this, 'getTemplateAssetFunction')));
        $twig->addFunction(new \Timber\Twig_Function('getStylePathFile', array($this, 'getStylePathFile')));
        $twig->addFunction(new \Timber\Twig_Function('loadAcfImage', array($this, 'loadAcfImage')));
        $twig->addFunction(new \Timber\Twig_Function('getTranslation', array($this, 'getTranslation')));
        $twig->addFunction(new \Timber\Twig_Function('getTimberProcessUrl', array($this, 'getTimberProcessUrl')));
        $twig->addFunction(new \Timber\Twig_Function('renderInclude', array($this, 'renderInclude')));
        $twig->addFunction(new \Timber\Twig_Function('displayMoreRead', array($this, 'displayMoreRead')));
        $twig->addFunction(new \Timber\Twig_Function('getHash', array($this, 'getHash')));
        return $twig;
    }

    public function timberAjax()
    {
        if (isset($_REQUEST['render'])) {
            $include = sanitize_text_field($_REQUEST['render']);
            $ajaxController = get_stylesheet_directory().'/controller/ajax/'.$include.'.php';
            if (file_exists($ajaxController)) {
                include_once $ajaxController;
            }
        }
        die();
    }

    /**
     * Get content of given asset from theme directory
     *
     * @param string $asset
     * @param string $imgAttributes
     * @param boolean $imgFallback
     * @return string
     */
    public function getTemplateAssetFunction($asset, $imgAttributes = '', $imgFallback = false, $forceId = false)
    {
        try {
            $ctx = stream_context_create(array('http'=>
                array(
                    'timeout' => 15, // In seconds
                )
            ));

            $newForceId = false;
            if (!empty($forceId)) {
                $this->clientSvgInclude++;
                if (\is_array($forceId)) {
                    $clientSvgInclude = $this->clientSvgInclude;
                    $newForceId = array_map(function($val) use ($clientSvgInclude) {
                        return $val.$clientSvgInclude;
                    }, $forceId);
                } else {
                    $newForceId = $forceId.'-'.$this->clientSvgInclude;
                }
            }

            $file = file_get_contents(get_stylesheet_directory() . $asset, false, $ctx);

            $file = str_ireplace('<svg', '<svg '. $imgAttributes, $file);

            if ($newForceId) {
                $file = str_ireplace($forceId , $newForceId, $file);
            }

            return $file;

        } catch (\Exception $e) {
            // File not found ?
        }

        if ($imgFallback) {
            return '<img src='. get_stylesheet_directory_uri() . $asset .'" '. $imgAttributes .' />';
        }

        return '';
    }

    public function getStylePathFile($file)
    {
        echo get_stylesheet_directory_uri().$file;
    }

    public function loadAcfImage($fileAcf, $class = '', $alt='')
    {
        echo \WPBushido\Helpers\Media::getImageFromACFData($fileAcf, false, [
            'class' => $class,
            'alt' => $alt
        ]);
    }

    /**
     * Get translation
     *
     * @param string $key
     * @param string $domain
     * @return string
     */
    public function getTranslation($key, $domain = null)
    {
        if (empty($domain)) {
            $domain = WPTM_LANGUAGE_DOMAIN;
        }
        return __($key, $domain);
    }

    public function renderInclude($url, $params = array(), $entry = '', $defaultcontent = '', $defaulttwig = '')
    {
        $returnTag = '';
        if (!empty($url)) {
            $returnTag.= '<hx:include ';
            if (!empty($entry)) {
                $returnTag.= 'entry="'.$entry.'" ';
            }
            $paramsAdd = '';
            if (count($params) > 0) {
                $paramsAdd = '&'.http_build_query($params);
            }
            $returnTag.= 'src="'.\TimberHelper::ob_function(array($this, 'getTimberProcessUrl'), [$url]).$paramsAdd.'">';
            if (!empty($defaulttwig)) {
                $context = \Timber::get_context();
                $returnTag.= \Timber::compile($defaulttwig, $context);
            }
            else if (!empty($defaultcontent)) {
                $context = \Timber::get_context();
                $returnTag.= $defaultcontent;
            }
            $returnTag.= '</hx:include>';
        }
        echo $returnTag;
    }

    public function displayMoreRead($content)
    {
        $newContent = [];
        if (!empty($content)) {
            $content = strip_tags($content, '<a>');
            $newContent['before'] = '';
            $newContent['after'] = '';

            $cutContent = wordwrap($content, 400, '<!--custom-->', false);
            $split = explode('<!--custom-->', $cutContent);
            foreach ($split as $ind => $text) {

                if ($ind == 0) {
                    $newContent['before'] = $text;
                }
                else {
                    $newContent['after'] .= $text . ' ';
                }
            }
        }

        return $newContent;
    }

    public static function getHash($algo = 'sha512', $string)
    {
        return hash($algo, $string);
    }

    /**
     * @param $process
     * @param array $params
     */
    public function getTimberProcessUrl($process, $params = array())
    {
        if (substr($process, 0,1) != '/') {
            echo self::returnTimberProcessUrl($process, $params);
        }
        else {
            echo $process;
        }
    }

    public static function returnTimberProcessUrl($process, $params = array())
    {
        $paramsAdd = '';
        if (!isset($params['site_id'])) {
            $params['site_id'] = get_current_blog_id();
        }
        if (count($params) > 0) {
            $paramsAdd = '&'.http_build_query($params);
        }
        if (substr($process, 0,1) != '/') {
            // Clean Process String
            $process = str_replace('?', '&', $process);
            $pageParams = '';
            $pageId = get_queried_object_id();
            if ($pageId && !empty($pageId)) {
                $pageParams = '&post_id='.$pageId;
            }
            return '/wp-admin/' . ('admin-ajax.php') . '?action=timber_ajax&render=' . $process.$paramsAdd.$pageParams;
        }
        else {
            return $process;
        }
    }

    public static function getTimberAjaxProcessUrl($process)
    {
        if (substr($process, 0,1) != '/') {
            // Clean Process String
            $process = str_replace('?', '&', $process);
            $processURL = '/wp-admin/' . ('admin-ajax.php') . '?action=timber_ajax&render=' . $process;
            if (!strstr($processURL, 'site_id')) {
                $processURL.= '&site_id='.get_current_blog_id();
            }
            return $processURL;
        }
        else {
            return $process;
        }
    }
}
