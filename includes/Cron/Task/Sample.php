<?php
/**
 * Task extension for WP-Command
 *
 * @package WPPI
 */

namespace WPBushido\Cron\Task;

use WPBushido\Cron\Task;

class Sample extends Task
{
    const NAME = 'sample_task';

    public static function registerHooks()
    {
        add_action('wpcmd__'. self::NAME, array('WPBushido\Cron\Task\Sample', 'run'));
        add_filter('wpcmdopt__'. self::NAME, array('WPBushido\Cron\Task\Sample', 'getOptions'));
    }

    public static function run($arguments = array())
    {
        // php wp-command.php --task=sample_task --host=localhost.dev [--index=?] [--reset]
        return self::execute('sync_cars', $arguments);
    }

    public static function getOptions()
    {
        return array('index:', 'reset');
    }

    public static function execute($type = null, $arguments = array())
    {

    }
}
