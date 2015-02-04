<?php
/**
 * Routing file for LeQG API
 * 
 * @package    LeQG
 * @author     Damien Senger <tech@leqg.info>
 * @copyright  2014-2015 MSG SAS – LeQG.info
 * @link       https://doc.leqg.info/
 */
 
// We set up locales data
setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');

// We set up encodage
header('Content-Type: application/json; charset=utf-8');

// We setting up autoload class system
function __autoload($class)
{
    require_once 'class/' . strtolower($class) . '.class.php';
}

// We initiate API processing
API::init();
 
// We load configuration file
$configuration = parse_ini_file('config.ini', true);

// We prepare the data source name information for LeQG Core MySQL DB
$dsn['core'] = 'mysql:host=' . $configuration['core']['host'] . ';port=' . $configuration['core']['port'] . ';dbname=' . $configuration['core']['base'] . ';charset=utf8';

// We try to connect the script to the LeQG Core MySQL DB
try {
    $dbh['core'] = new PDO($dsn['core'], $configuration['core']['user'], $configuration['core']['pass']);

    // We save in configuration class the SQL link
    Configuration::write('db.core', $dbh['core']);
} catch (PDOException $e) {
    // We store SQL connection error into the API result
    API::error(503, 'Can not connect to the central authentication server.');
    
    // We parse and send JSON API content
    API::parsing();
    API::result();
    
    exit;
}

// We check if authorization is asked
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    // if it is, we check authentification information
    API::auth();
}

// We parse API result to JSON format
API::parsing();

// We display API result and return HTTP response code
API::result();
