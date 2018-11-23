<?php
error_reporting(E_ALL);
ignore_user_abort(true);

if (php_sapi_name() != 'cli' || defined('DOING_COMMAND')) {
    die();
}

/*----------  Functions  ----------*/
function frame($task) {
    $length = 0;
    $middle = '';
    if (is_array($task)) {
        $lengths = array_map('strlen', $task);
        $length = max($lengths);
        foreach ($task as $i => $singletask) {
            $diff = str_repeat(' ', $length - $lengths[$i]);
            $middle .= '│ ' . $singletask . $diff . ' │' . PHP_EOL;
        }
        $length += 2;
    } else {
        $length = strlen($task) + 2;
        $middle = '│ ' . $task . ' │' . PHP_EOL;
    }

    $top = '┌' . str_repeat('─', $length) . '┐' . PHP_EOL;
    $bottom = '└' . str_repeat('─', $length) . '┘' . PHP_EOL;

    return $top . $middle . $bottom;
}

/*----------  Statistics  ----------*/
$timestart = microtime(true);
ob_implicit_flush(true);

/**
 * Tell WordPress we are doing the CRON task.
 *
 * @var bool
 */
define('DOING_COMMAND', true);

/*----------  Default  ----------*/
$verbose = false;

/*----------  Options  ----------*/
$options = getopt(
    "",
    array('task:', 'verbose', 'host:')
);

foreach ($options as $key => $option) {
    if ($option === false) {
        $options[$key] = true;
    }
}

if (isset($options['verbose']) && $options['verbose'] === true) {
    $verbose = true;
}

if (isset($options['host'])) {
    $_SERVER['HTTP_HOST'] = $options['host'];
    unset($options['host']);
} else {
    echo chr(27) . "[41m" . "Missing the --host parameter (no http://)" . chr(27) . "\033[0m" . PHP_EOL;
    die();
}

/*----------  Wordpress load  ----------*/
if (!defined('ABSPATH')) {
    /** Set up WordPress environment */
    require_once (dirname(__FILE__) . '/wp-load.php');
}

/*----------  Task running  ----------*/
if (isset($options['task'])) {
    $task   = $options['task'];
    $filter = 'wpcmdopt__' . $task;
    $action = 'wpcmd__' . $task;
    unset($options['task']);

    if (has_action($action)) {
        echo frame('Starting task: ' . $task);

        // Get Task specific options
        if (has_filter($filter)) {
            $taskopts = apply_filters($filter, null);
            $taskopts = getopt("", $taskopts);
            $options = array_merge($options, $taskopts);
        } else if ($verbose) {
            echo chr(27) . "[43m" . "No specific options found (filter <" . $filter . ">), ignoring…" . chr(27) . "\033[0m" . PHP_EOL;
        }

        do_action($action, $options);

        /*----------  Statistics  ----------*/
        $duration = microtime(true) - $timestart;
        echo frame(array(
            'Memory usage: ' . number_format((memory_get_usage() / 1024 / 1024), 3) . ' Mo',
            'Task duration: ' . $duration . ' seconds',
            'Finished task: ' . $task
        ));
    } else {
        echo chr(27) . "[41m" . "Sorry, no WP Action with name <" . $action . ">" . chr(27) . "\033[0m" . PHP_EOL;
    }
} else {
    echo chr(27) . "[41m" . "No task found. Did you miss the --task parameter ?" . chr(27) . "\033[0m" . PHP_EOL;
}
