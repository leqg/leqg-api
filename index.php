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
 
// We load configuration file
$configuration = parse_ini_file('config.ini', true);

// Connecting to MySQL DB
$dsn = 'mysql:host=' . $configuration['db']['host'] . ';dbname=' . $configuration['db']['base'] . ';charset=utf8';
$link = new PDO($dsn, $configuration['db']['user'], $configuration['db']['pass']);

// We save in configuration class the SQL link
Configuration::write('db.link', $link);

// We initiate API processing
API::init();

// We parse API result to JSON format
API::parsing();

// We display API result and return HTTP response code
API::result();
