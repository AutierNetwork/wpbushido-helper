<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

use DateTime;
use Exception;

class Data
{
    /**
     * Sanitize a value with the sanitizer passed as argument
     * @param  mixed $value Value to sanitize
     * @param  string $sanitizer Type of sanitizer to apply
     * @return mixed            Sanitized value
     */
    public static function sanitize($value, $sanitizer = 'string', $extraOptions = [])
    {
        switch ($sanitizer) {
            case 'string':
                $value = sanitize_text_field($value);
                break;

            case 'html':
                $value = wp_kses_post($value);
                break;

            case 'slug':
                $value = sanitize_title($value);
                break;

            case 'id':
                $value = sanitize_key($value);
                break;

            case 'email':
                $value = sanitize_email($value);
                break;

            case 'int':
                $value = absint($value);
                break;

            case 'intnull':
                $value = absint($value);
                if (empty($value)) {
                    $value = null;
                }
                break;

            case 'float':
                $value = floatval($value);
                break;

            case 'url':
                $value = esc_url($value, array('http', 'https'));
                break;

            case 'bool':
                $value = (is_string($value) ? ($value == 'true' || $value == '1' ? true : false) : ($value ? true : false));
                break;

            case 'bool_null':
                if ($value !== null) $value = (is_string($value) ? ($value == 'true' || $value == '1' ? true : false) : ($value ? true : false));
                break;

            case 'bool_string':
                $value = (is_string($value) ? ($value == 'true' || $value == '1' ? 'true' : 'false') : ($value ? 'true' : 'false'));
                break;

            case 'textarea':
                $value = explode("\r\n", $value);
                foreach ($value as $val) {
                    $values[] = sanitize_text_field($val);
                }
                $value = implode("<br>", $values);
                break;

            case 'logo':
                $value = wp_check_filetype($value);
                if (in_array($value['ext'], array('jpg', 'jpeg', 'png'))) {
                    $value = true;
                } else {
                    $value = false;
                }
                break;

            case 'datetime':
                if ($value instanceof DateTime === false) {
                    $result = false;
                    try {
                        $result = new DateTime($value);
                    } catch (Exception $e) {
                    }
                    $value = $result;
                }
                break;

            case 'phone':
                if (class_exists('\libphonenumber\PhoneNumberUtil')) {
                    $country = isset($extraOptions['country']) ? $extraOptions['country'] : 'FR';
                    try {
                        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                        $number = $phoneUtil->parse($value, $country);
                        if ($phoneUtil->isValidNumber($number)) {
                            $number = $phoneUtil->formatInOriginalFormat($number, $country);
                            $value = implode('.', explode(' ', $number));
                        }
                    } catch (Exception $e) {
                        // Exception
                    }
                }
                break;

            case false:
                break;
        }
        return $value;
    }

    /**
     * Remove non printable utf8 characters from given string
     *
     * @param string $text
     * @return string
     */
    public static function cleanNonPrintableUtf8($text)
    {
        return is_string($text) ? preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $text) : $text;
    }

    /**
     * Get the reading time of the given text
     *
     * @param string $text
     * @param integer $speed Reading speed in word by minute
     * @return array
     */
    public static function getReadingTime($text, $speed = 200)
    {
        $word = str_word_count(strip_tags($text));
        $raw_m = $word / $speed;
        $m = floor($raw_m);
        $s = floor($word % $speed / ($speed / 60));
        $est = $m . ' minute' . ($m == 1 ? '' : 's') . ', ' . $s . ' second' . ($s == 1 ? '' : 's');

        return [
            'minutes' => $m,
            'seconds' => $s,
            'total_seconds' => ($m * 60) + $s,
            'rounded_minutes' => ceil($raw_m),
        ];
    }
}
