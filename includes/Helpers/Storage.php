<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

use WP_Error;
use DateTime;
use Exception;

class Storage
{
    public static $defaultCookieExpiration = 604800;

    /**
     * Get a Session or Cookie var
     * @param  string $var Name of session/cookie var
     * @param  mixed $default Default value if session/cookie var doesn't exists
     * @return mixed          Value of session/cookie var or default
     */
    public static function getCookie($var, $prefix = false, $default = null)
    {
        $var = strtolower($var);
        $value = new WP_Error('get_cookie_var', __('Aucune valeur existante en cookie/session', WPTM_LANGUAGE_DOMAIN));

        if (!empty($prefix)) {
            $prefix = strtolower($prefix);

            if (!isset($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }

            if (isset($_SESSION[$prefix][$var])) {
                $value = $_SESSION[$prefix][$var];
            } else if (isset($_COOKIE[$prefix . '_' . $var])) {
                $value = $_SESSION[$prefix][$var] = $_COOKIE[$prefix . '_' . $var];
            } else {
                $value = $default;
            }
        } else {
            if (isset($_SESSION[$var])) {
                $value = $_SESSION[$var];
            } else if (isset($_COOKIE[$var])) {
                $value = $_SESSION[$var] = $_COOKIE[$var];
            } else {
                $value = $default;
            }
        }

        return $value;
    }

    /**
     * Set a var in Session & Cookie
     * @param string $var Var name
     * @param mixed $value Var value
     * @param int $expiration Number of seconds for cookie expiration date
     */
    public static function setCookie($var, $value, $prefix = false, $expiration = self::defaultCookieExpiration)
    {
        $setCookie = ($expiration !== false);

        if ($setCookie) {
            $expiration = new DateTime('+' . $expiration . ' seconds');
            $expiration = $expiration->getTimestamp();
        }

        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');

        $var = strtolower($var);

        try {
            if (!empty($prefix)) {
                $prefix = strtolower($prefix);
                if (!isset($_SESSION[$prefix])) {
                    $_SESSION[$prefix] = array();
                }
                $_SESSION[$prefix][$var] = $value;

                if ($setCookie) {
                    setcookie($prefix . '_' . $var, $value, $expiration, '/', COOKIE_DOMAIN, $secure, false);
                }
            } else {
                $_SESSION[$var] = $value;

                if ($setCookie) {
                    setcookie($var, $value, $expiration, '/', COOKIE_DOMAIN, $secure, false);
                }
            }

            return true;
        } catch (Exception $e) {
            // Exception ?
        }

        return false;
    }

    /**
     * Remove a var from Session & Cookies
     *
     * @param string $var
     * @param string $prefix
     * @return boolean
     */
    public static function removeCookie($var, $prefix = false)
    {
        $expiration = new DateTime('-3600 seconds');
        $expiration = $expiration->getTimestamp();

        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');

        $var = strtolower($var);

        try {
            if (!empty($prefix)) {
                $prefix = strtolower($prefix);
                if (isset($_SESSION[$prefix]) && isset($_SESSION[$prefix][$var])) {
                    unset($_SESSION[$prefix][$var]);
                }
                if (isset($_COOKIE[$prefix . '_' . $var])) {
                    setcookie($prefix . '_' . $var, null, $expiration, '/', COOKIE_DOMAIN, $secure, false);
                }
            } else {
                unset($_SESSION[$var]);
                if (isset($_COOKIE[$prefix . '_' . $var])) {
                    setcookie($var, null, $expiration, '/', COOKIE_DOMAIN, $secure, false);
                }
            }

            return true;
        } catch (Exception $e) {
            // Exception ?
        }

        return false;
    }
}
