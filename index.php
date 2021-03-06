<?php
/**
 * Routing file for LeQG API
 * 
 * @package    LeQG
 * @author     Damien Senger <tech@leqg.info>
 * @copyright  2014-2015 MSG SAS – LeQG.info
 * @link       https://doc.leqg.info/
 */
 
// We set locales data
setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');

// We set encodage
header('Content-Type: application/json; charset=utf-8');

// We allow cross domain request
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');

// We set autoload class
function __autoload($class)
{
    // We load all file classes
    $classes = scandir('class/');
    unset($classes[0], $classes[1]);
    if (in_array('index.html', $classes)) { $cle = array_search('index.html', $classes); unset($classes[$cle]); }
    
    if (in_array(strtolower($class) . '.class.php', $classes)) {
        include 'class/' . strtolower($class) . '.class.php';
    } else {
        return false;
    }
}
 
// We load configuration file
$configuration = parse_ini_file('config.ini', true);

// We store token name into Configuration datas
Configuration::write('token', $configuration['token']['name']);
Configuration::write('url', $configuration['url']['base']);
Configuration::write('db.host', $configuration['core']['host']);
Configuration::write('db.port', $configuration['core']['port']);
Configuration::write('db.user', $configuration['core']['user']);
Configuration::write('db.pass', $configuration['core']['pass']);

// We prepare the data source name information for LeQG Core MySQL DB
$dsn['core'] = 'mysql:host=' . $configuration['core']['host'] . ';port=' . $configuration['core']['port'] . ';dbname=leqg_core;charset=utf8';

// We try to connect the script to the LeQG Core MySQL DB
try {
    $dbh['core'] = new PDO($dsn['core'], $configuration['core']['user'], $configuration['core']['pass']);

    // We save in configuration class the SQL link
    Configuration::write('db.core', $dbh['core']);
    
} catch (PDOException $e) {
    // We store SQL connection error into the API result
    API::error(503, 'CentralAuthSystemCanConnect', 'Can not connect to the central authentication server.');
    
    // We stop script execution
    exit;
}

// We initiate API processing
API::init();

// We parse API result to JSON format
API::parsing();

// We display API result and return HTTP response code
API::result();
