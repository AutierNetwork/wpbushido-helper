<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

class Entries
{
    /**
     * Get the entry permalink with given endpoint
     *
     * @param string $endpoint
     * @param string $value
     * @param boolean $post
     * @return string
     */
    public static function getEndpointUrl( $endpoint, $value = '', $post = false ) {
        if ($post) {
            $permalink = get_permalink($post);
        } else {
            $permalink = get_permalink();
        }

        if (get_option( 'permalink_structure')) {
            if (strstr($permalink, '?')) {
                $query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
                $permalink    = current( explode( '?', $permalink ) );
            } else {
                $query_string = '';
            }
            $url = trailingslashit($permalink) . $endpoint .'/'. $value . $query_string;
        } else {
            $url = add_query_arg($endpoint, $value, $permalink);
        }

        return $url;
    }
