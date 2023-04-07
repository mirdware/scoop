<?php

$_matches = array();

/**
 * Set important environment variables and re-parse the query string.
 * @return boolean
 */
function finalize()
{
    if (defined('REWRITER_FINALIZED')) return false;
    define('REWRITER_FINALIZED', true);
    if (is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'];
    }
    if (isset($_SERVER['QUERY_STRING'])) {
        $_GET = [];
        parse_str($_SERVER['QUERY_STRING'], $_GET);
    }
    $_SERVER['PHP_SELF'] = '';
    $_SERVER['REQUEST_URI'] = '/' . str_replace('index.php?route=', '', $_SERVER['REQUEST_URI']);
    $queryStringPosition = strpos($_SERVER['REQUEST_URI'], '&');
    if ($queryStringPosition) {
        $_SERVER['REQUEST_URI'][$queryStringPosition] = '?';
    }
    return true;
}

/**
 * Adjust the server environment variables to match a given URL.
 * @param string $url
 */
function set_environment($url)
{
    $url = rtrim($url, '&?');
    $request_uri = $script_name = $url;
    $query_string = null;
    if (strpos($url, '?') > 0) {
        $script_name = substr($url, 0, strpos($url, '?'));
        $query_string = substr($url, 1 + strpos($url, '?'));
    }
    $_SERVER['REQUEST_URI'] = $request_uri;
    $_SERVER['SCRIPT_NAME'] = $script_name;
    $_SERVER['QUERY_STRING'] = $query_string;
}

/**
 * Parse regular expression matches. eg. $0 or $1
 * @param string $url
 * @return string
 */
function parse_matches($url)
{
    return preg_replace_callback('/\$([0-9]+)/', function ($bit) {
        global $matches;
        return isset($matches[$bit[1]]) ? $matches[$bit[1]] : null;
    }, $url);
}

/**
 * Parse Apache style rewrite parameters. eg. %{QUERY_STRING}
 * @param string $url
 * @return string
 */
function parse_parameters($url)
{
    return preg_replace_callback('/%\{([A-Z_+]+)\}/', function ($bit) {
        return isset($_SERVER[$bit[1]]) ? $_SERVER[$bit[1]] : null;
    }, $url);
}

/**
 * Change the internal url to a different url.
 * @param string $from Regular expression to match current url, or optional when used in conjunction with `test`.
 * @param string $to URL to redirect to.
 * @return boolean
 */
function rewrite($from, $to = null)
{
    if (defined('REWRITER_FINALIZED')) return false;
    $url = isset($to) ? preg_replace($from, $to, $_SERVER['SCRIPT_NAME']) : parse_matches($from);
    set_environment(parse_parameters($url));
    return true;
}

/**
 * Compare a regular expression against the current request, store the matches for later use.
 * @return boolean
 */
function test($expression)
{
    global $matches;
    if (defined('REWRITER_FINALIZED')) return false;
    return 0 < (integer)preg_match($expression, $_SERVER['SCRIPT_NAME'], $matches);
}

set_environment($_SERVER['REQUEST_URI']);
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
$page = __dir__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . trim($uri, '/');
if (file_exists($page) && is_file($page)) {
    return false;
}
if (substr($uri, -1) !== '/') {
    header('Location: ' . $uri . '/');
    exit;
}
// Your rewrite rules here.
test('%^/(.*)$%') && rewrite('index.php?route=$1&%{QUERY_STRING}') && finalize();
include 'index.php';
