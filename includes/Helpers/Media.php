<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

use WP_Query;
use Exception;

class Media
{
    /**
     * Get an attachment ID given a URL.
     *
     * @param string $url
     *
     * @return int Attachment ID on success, 0 on failure
     */
    public static function getAttachmentId($url)
    {
        $attachment_id = 0;
        $dir = wp_upload_dir();

        if (false !== strpos($url, $dir['baseurl'] . '/')) { // Is URL in uploads directory?
            $file = basename($url);
            $query_args = array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'meta_query'  => array(
                    array(
                        'value'   => $file,
                        'compare' => 'LIKE',
                        'key'     => '_wp_attachment_metadata',
                    ),
                )
            );

            $query = new WP_Query( $query_args );

            if ($query->have_posts()) {
                foreach ($query->posts as $post_id) {
                    $meta = wp_get_attachment_metadata($post_id);
                    $original_file       = basename($meta['file']);
                    $cropped_image_files = wp_list_pluck($meta['sizes'], 'file');
                    if ($original_file === $file || in_array($file, $cropped_image_files)) {
                        $attachment_id = $post_id;
                        break;
                    }
                }
            }
        }

        return $attachment_id;
    }

    /**
     * Get Attachement metadata from URL
     *
     * @param string $url
     * @return void
     */
    public static function getAttachmentMetadataFromUrl($url)
    {
        $error = '';

        try {
            if (is_array($url) && isset($url['id'], $url['url'])) {
                return $url;
            }

            $id = self::getAttachmentId($url);
            if (!empty($id)) {
                $metadata = wp_get_attachment_metadata($id);
                $metadata['url'] = $url;

                return $metadata;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return [
            'helper_has_error' => true,
            'helper_error_message' => (!empty($error)) ? $error : 'No exception, attachment not found ?',
            'url' => $url,
            'width' => 'auto',
            'height' => 'auto'
        ];
    }

    /**
     * Get Image URL or Tag from ACF Data array
     *
     * @param array $imgData
     * @param boolean $urlOnly
     * @return string
     */
    public static function getImageFromACFData($imgData, $urlOnly = false, $attributes = [])
    {
        if (empty($imgData)) {
            return false;
        }

        if (!is_array($imgData)) {
            if (filter_var($imgData, FILTER_VALIDATE_URL)) {
                $imgData = self::getAttachmentMetadataFromUrl($imgData);
            } elseif (is_numeric($imgData)) {
                $id = $imgData;
                $imgData = wp_get_attachment_metadata($id);
                if (is_array($imgData)) {
                    $imgData['id'] = $id;
                    $imgData['url'] = wp_get_attachment_url($id);
                }
            }
        }

        /** @return string URL only **/
        if ($urlOnly === true && isset($imgData['url'])) {
            return $imgData['url'];
        }

        $filetype = wp_check_filetype($imgData['url']);

        /** @return string SVG Content */
        if ($filetype['ext'] == 'svg' && ($filePath = get_attached_file($imgData['id'])) !== false) {
            return file_get_contents($filePath);
        }

        $defaultAttributes = [
            'src' => $imgData['url'],
            'width' => $imgData['width'],
            'height' => $imgData['height'],
            'alt' => (isset($imgData['alt'])) ? $imgData['alt'] : ''
        ];

        $srcSet = wp_get_attachment_image_srcset($imgData['id'], 'full');
        $imgSizes = wp_get_attachment_image_sizes($imgData['id'], 'full');

        if ($srcSet !== false) {
            $defaultAttributes['srcset'] = $srcSet;
        }
        if ($imgSizes !== false) {
            $defaultAttributes['sizes'] = $imgSizes;
        }

        $attributes = array_merge($defaultAttributes, $attributes);

        $img = '<img '.join(' ', array_map(function($key) use ($attributes) {
           if (is_bool($attributes[$key])) {
              return $attributes[$key]?$key:'';
           }
           return $key.'="'.$attributes[$key].'"';
        }, array_keys($attributes))).' />';

        return $img;
    }
}
