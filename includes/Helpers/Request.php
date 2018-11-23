<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

use WP_Error;

class Request
{

    /**
     * Sanitize a request array from fields list
     * @param  array $request POST, GET, etc.
     * @param  array $fields Fields list and details (id, required, sanitize)
     * @return mixed          Array of sanitized values or WP_Error
     */
    public static function sanitize($request, $fields = array(), $useACF = false)
    {
        $sanitizedRequest = array();
        $requiredFields = array();
        $defaultSanitizer = 'string';

        // Required fields
        foreach ($fields as $key => $value) {
            if (is_array($value) && isset($value['required']) && $value['required'] === true) {
                if ($useACF && isset($fields[$key]['acf'])) {
                    $key = $fields[$key]['acf'];
                }
                $requiredFields[$key] = (isset($value['label'])) ? $value['label'] : ucfirst($key);
            }
        }

        // Filter request datas
        foreach ($request as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $sanitizer = (is_array($fields[$key]) && isset($fields[$key]['sanitize'])) ? $fields[$key]['sanitize'] : $defaultSanitizer;

                if (!is_object($value)) {
                    if (is_array($value)) {
                        /*----------  Sanitize a group of subfields, required or not  ----------*/
                        if (isset($fields[$key]['fields'])) {
                            $value = self::sanitize($value, $fields[$key]['fields']);

                            if (is_wp_error($value)) {
                                $missingSubFields = $value->get_error_data('sanitize_request');
                                if (isset($missingSubFields['missing_fields'])) {
                                    $missingSubFields = $missingSubFields['missing_fields'];
                                    $parentLabel = (isset($fields[$key]['label'])) ? $fields[$key]['label'] : ucfirst($key);
                                    foreach ($missingSubFields as $subField => $subLabel) {
                                        $missingFields[$key . '.' . $subField] = $parentLabel . ' : ' . $subLabel;
                                    }
                                }
                            }
                        } else {
                            $value = array_map('WPBushido\Helpers\Data::sanitize', $value);
                        }
                    } else {
                        $value = Data::sanitize($value, $sanitizer);
                    }
                }

                // If if we have an empty value for a required field, we do not store it
                if (in_array($key, $requiredFields) && empty($value)) {
                    continue;
                }
                if ($useACF && isset($fields[$key]['acf'])) {
                    $key = $fields[$key]['acf'];
                }
                $sanitizedRequest[$key] = $value;
            }
        }

        foreach ($requiredFields as $reqField => $fieldLabel) {
            if (empty($sanitizedRequest[$reqField]))
                $missingFields[$reqField] = $fieldLabel;
        }

        if (!empty($missingFields)) {
            return new WP_Error('sanitize_request', __('Champs obligatoires manquants', WPTM_LANGUAGE_DOMAIN) . ' — [' . implode(', ', $missingFields) . ']', array('missing_fields' => $missingFields));
        }

        return $sanitizedRequest;
    }

    /**
     * Send a response as JSON with success or error handler
     * @param  mixed $response Response array or WP_Error
     * @param  boolean $success To avoid success if error but not WP_Error
     * @return mixed            @see wp_send_json_error @see wp_send_json_success
     */
    public static function sendResponse($response, $success = true)
    {
        if (is_wp_error($response) || !$success) {

            return wp_send_json_error($response);

        }
        return wp_send_json_success($response);
    }

    /**
     * is XHR Request
     *
     * @return boolean
     */
    public static function isXhr()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Return the referer
     *
     * @return string|null
     */
    public static function getReferer()
    {
        return (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
    }
}
