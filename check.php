<?php
/**
 * Amazon Warehouse Deal notifier
 *
 * @copyright Dennis Stücken <dstuecken@me.com>
 * @license MIT
 */
use dstuecken\Notify\NotificationCenter;
use Symfony\Component\Yaml\Yaml;
use dstuecken\WdAlerts\Config;
use dstuecken\Notify\Handler\MacOSHandler;
use dstuecken\WdAlerts\Crawler\Crawler;
use dstuecken\WdAlerts\Crawler\Amazon\Engine as AmazonEngine;

// Get article id from arguments
$articleId = isset($argv[1]) ? $argv[1] : 'B00ULWWFIC';

// Include composer
require 'vendor/autoload.php';

// Load configuration
$config = new Config(Yaml::parse(
    file_get_contents(__DIR__ . '/.config.yml')
));

// Prevent timezone warnings
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Berlin');
}

// Disable speech for Windows and Linux by default
if (php_uname('s') !== 'Darwin') {
    $config->override('options', 'enableMacOsSpeechOutput', false);
}

// Override php ini setting allow_url_fopen
ini_set('allow_url_fopen', 1);

// Check for requirements
if (!extension_loaded('curl') && !ini_get('allow_url_fopen')) {
    printf('You either need php-curl extension to be installed and activated, or allow_url_fopen = 1 in your php.ini.');
    printf('php.ini location is: ' . php_ini_loaded_file());
    die;
}

// Get notification center instance
$notificationCenter = new NotificationCenter();

if ($config->getOption('enableMacOsNotificationHandler')) {
    $notificationCenter->addHandler(new MacOSHandler());
}

// Identify correct engine to use
$engineClass = '\\dstuecken\\WdAlerts\\Crawler\\' . $config->getOption('engine') . '\\Engine';
if (class_exists($engineClass)) {
    $engine = new $engineClass($articleId);
} else {
    // Default engine is amazon:
    $engine = new AmazonEngine($articleId);
}

// Initialize crawler
$crawler = new Crawler(
    $engine,
    $notificationCenter,
    $config
);

// Start crawling process
$crawler->crawl();