<?php
/**
 * Task extension for WP-Command
 *
 * @package WPPI
 */

namespace WPBushido\Cron;

use WP_Error;
use Exception;

class Task
{

    /**
     * Display one message as with new line after
     * @param  string $message Message to display
     */
    public static function writeln($message)
    {
        global $isPHP;
        echo $message . ($isPHP ? nl2br(PHP_EOL) : PHP_EOL);
    }

    /**
     * Display one message as success
     * @param  string $message Message to display
     */
    public static function success($message)
    {
        echo self::colorize($message, 'SUCCESS');
    }

    /**
     * Display one message as info
     * @param  string $message Message to display
     */
    public static function info($message)
    {
        echo self::colorize($message, 'NOTE');
    }

    /**
     * Display one message as warning
     * @param  string $message Message to display
     */
    public static function warning($message)
    {
        echo self::colorize($message, 'WARNING');
    }

    /**
     * Display one single error with failure color
     * @param  string $message Error message
     */
    public static function error($message)
    {
        echo self::colorize($message, 'FAILURE');
    }

    /**
     * Display one or several errors with failure color
     * @param  array $errors Array of errors
     */
    public static function errors($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                echo self::colorize($error, 'FAILURE');
            }
        } else {
            echo self::colorize($errors, 'FAILURE');
        }
    }

    /**
     * Show a progressbar in CLI
     *
     * @param integer $cur
     * @param integer $max
     * @param string $full
     * @param string $empty
     * @return string
     */
    public static function progressBar($cur = 0, $max = 0, $full = '░', $empty = ' ')
    {
        $p = (int)($cur / $max * 100);

        return $max < 100 || $cur % (int)($max / 100) == 0 || $p == 100 ?
            sprintf("\r│%s%s│ %d%% %d/%d", str_repeat($full, $p), str_repeat($empty, 100 - $p), $p, $cur, $max) : '';
    }

    /**
     * Utility class to return colorized text
     * @param  string $text   Text to colorize
     * @param  string $status Type of color (success, failure, etc.)
     * @return string         Colorized text
     */
    public static function colorize($text, $status) {
        global $isPHP;
        $out = "";
        switch($status) {
            case "SUCCESS":
                $out = "\033[0;32m"; //Green background
            break;
            case "FAILURE":
                $out = "\033[0;31m"; //Red background
            break;
            case "WARNING":
                $out = "\033[1;33m"; //Yellow background
            break;
            case "NOTE":
            case "INFO":
                $out = "\033[0;34m"; //Blue background
            break;
            default:
                throw new Exception("Invalid status: " . $status);
            }
        return $isPHP ? $text . nl2br(PHP_EOL) : ("$out" . "$text" . "\033[0m" . PHP_EOL);
    }

    public static function log($type, array $errors = array())
    {
        global $isPHP;

        $logfile = ABSPATH . 'wp-logs/cron_' . $type . '.log';

        if (!file_exists($logfile)) {
            touch($logfile);
        }

        $errors = array_map(function($item) {
            if ($item instanceof WP_Error) {
                $item = $item->get_error_message();
            } elseif ($item instanceof Exception) {
                $item = $item->getMessage();
            }

            if (is_string($item)) {
                return $item;
            }
            return (string)$item;
        }, $errors);

        foreach ($errors as $error) {
            if ($error && is_string($error)) {
                file_put_contents(
                    $logfile,
                    sprintf(__("%s: %s - %s"), '[' . date('Y-m-d H:i:s') . '] Error', strtoupper($type), $error . ($isPHP ? nl2br(PHP_EOL) : PHP_EOL)),
                    FILE_APPEND
                );
            }
        }
    }
}
