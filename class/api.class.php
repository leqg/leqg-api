<?php
/**
 * @package     LeQG
 * @author      Damien Senger <tech@leqg.info>
 * @copyright   2014-2015 MSQG SAS – LeQG
 */

class API
{
    /**
     * @val     string  $token      Auth token
     * @val     int     $user       Authentificated user ID
     * @val     string  $client     Client hostname 
     * @val     string  $json       JSON array to send
     * @val     bool    $errors     Errors informations
     * @val     array   $data       Informations to send, before JSON formatting
     * @val     bool    $success    API call status (true success, false error)
     * @val     int     $response   HTTP response code to send with JSON
     * @val     array   $headers    HTTP header request
     * @val     array   $body       HTTP body request
     * @val     array   $module     Module and methods asked by client
     */
    private static $token, $user, $client, $json;
    private static $data = array();
    private static $errors = array();
    private static $success = true;
    private static $response = 202;
    private static $headers = array();
    private static $body = array();
    public  static $module = array();
    
    
    /**
     * API initialization
     * 
     * Launch all needed services to response to API request, first by checking
     * token and doing authentication if asked
     * 
     * @version 1.0
     * @return  void
     */
    
    public static function init()
    {
        // we store HTTP header & body content
        self::$headers = getallheaders();
        self::$body = json_decode(file_get_contents('php://input'));
        
        // we check if request method is OPTIONS to send just headers without content
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            self::parsing();
            self::result();
        }
        
        // we search asked module & methods
        if (isset($_SERVER['PATH_INFO'])) {
            $path = substr($_SERVER['PATH_INFO'], 1);
            self::$module = explode('/', $path);
        } else {
            self::$module = array();
        }
        
        // we check if a token exist and we store it
        if (isset(self::$headers['Authorization']) && substr(self::$headers['Authorization'], 0, 4) == Configuration::read('token')) {
            $token = explode(' ', self::$headers['Authorization']);
            self::$token = $token[1];
        }
        
        // we check if an authenticate is asked or if client have a valid token
        if (isset(self::$module[0]) && self::$module[0] == 'authenticate') {
            self::auth(); break;
        } else {
            self::token_auth();
        }
        
        // we try connection to bdd client
        $dsn['client'] = 'mysql:host=' . Configuration::read('db.host') . ';port=' . Configuration::read('db.port') . ';dbname=leqg_' . self::$client . ';charset=utf8';

        // We try to connect the script to the LeQG Core MySQL DB
        try {
            $dbh['client'] = new PDO($dsn['client'], Configuration::read('db.user'), Configuration::read('db.pass'));
        
            // We save in configuration class the SQL link
            Configuration::write('db.client', $dbh['client']);
            
        } catch (PDOException $e) {
            // We store SQL connection error into the API result
            API::error(503, 'SQLConnectionFail', 'Can not connect to the client database server.');
            
            // We stop script execution
            exit;
        }
        
        // we launch client asked module 
        if (!empty(self::$module[0]) && class_exists(self::$module[0])) {
            $module = self::$module[0];
            $$module = new $module();
        }
    }
    
    
    /**
     * Query to LeQG Central Database preparation
     * 
     * @version 1.0
     * @param   string  $sql    SQL query filename
     * @result  object
     */
    
    public static function dbcore($sql)
    {
        return Configuration::read('db.core')->prepare(file_get_contents("sql/$sql.sql"));
    }
    
    
    /**
     * Query to LeQG Client Database preparation
     * 
     * @version 1.0
     * @param   string  $sql    SQL query filename
     * @result  object
     */
    
    public static function query($sql)
    {
        return Configuration::read('db.client')->prepare(file_get_contents("sql/$sql.sql"));
    }
    
    
    /**
     * Authentification method
     * 
     * @version 1.0
     * @return  bool    Result of authentification
     */
    
    public static function auth()
    {
        // if client wants to try an authentification
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            // we check if this user exist in LeQG Central Auth DB
            $query = self::dbcore('auth_login');
            $query->bindParam(':mail', $_SERVER['PHP_AUTH_USER']);
            $query->execute();
            
            if ($query->rowCount() == 1) {
                // we load user data
                $data = $query->fetch();
                
                // we verify both password
                if (password_verify($_SERVER['PHP_AUTH_PW'], $data['password'])) {
                    // we check if we already have a recent connection token
                    $query = self::dbcore('auth_token_looking_by_id');
                    $query->bindParam(':id', $data['id']);
                    $query->execute();
                    
                    // we store user id & client hostname
                    self::client($data['client']);
                    self::user($data['id']);
                    
                    // we check if we found a token
                    if ($query->rowCount() == 1) {
                        // we charge actuel token data
                        $data = $query->fetch();
                        
                        // we store and send actual token
                        self::token($data['token']);
                        
                    } else {
                        // we create and send a new token
                        self::token();
                    }
                    
                } else {
                    // password not matching
                    self::error(401, 'BadPW', 'Wrong password');
                }
                
            } else {
                // no user found
                self::error(401, 'NoUser', 'User not found');
            }
            
        } else {
            // no connection data found
            self::error(401, 'NoUserPW', 'We did not receive user and password information');
        }
    }
    
    
    /**
     * Token validation
     * 
     * Check if a token is valid
     *
     * @version 1.0
     * @param   string  $token      Token to check
     * @result  void
     */
    
    public static function token_auth()
    {
        // we look if client send a token in HTTP headers
        if (!empty(self::$token)) {
            // we check token validity
            $query = self::dbcore('auth_id_by_token');
            $query->bindParam(':token', self::$token);
            $query->execute();
            
            // si le token est valide
            if ($query->rowCount() == 1) {
                $data = $query->fetch();
                
                // we store user id
                self::user($data['id']);
                self::client($data['client']);
                
            } else {
                // no valid token, please authenticate yourself
                self::error(403, 'NonValidToken', 'No valid token, please authenticate yourself.');
            }
            
        } else {
            // if we have no token and no authenticate try
            self::error(403, 'NoToken', 'No token, please send one or authenticate yourself.');
        }
    }
    
    
    /**
     * Store or create a token and send it to client before stop execution of the script
     * 
     * This method can store a token send by client or create a new token.
     * For a new token, we need to first set static user id property.
     * 
     * @version 1.0
     * @param   string  $token      Token to store
     * @result  mixed               Token in case of a creation
     */
    
    public static function token($token = null)
    {
        // if we have to create a token
        if (is_null($token)) {
            // we generate an uniqid token
            $token = uniqid(bin2hex(openssl_random_pseudo_bytes(8)), true);
            
            // we add the token to the core database
            $query = self::dbcore('auth_token_storage');
            $query->bindParam(':token', $token);
            $query->bindParam(':id', self::$user);
            $query->execute();
            
            // we store the token
            self::$token = $token;
            
        } else {
            // else, we store the token
            self::$token = $token;
        }
        
        // we add it to the JSON return
        $tokens = array(
            '0' => array(
                'id' => self::$token
            )
        );
        self::add('tokens', $tokens);
        
        // we parse and return JSON
        self::parsing();
        self::result();
    }
    
    
    /**
     * Store client hostname
     * 
     * @version 1.0
     * @param   string  $client     Client to store
     * @result  void
     */
    
    public static function client($client)
    {
        self::$client = $client;
    }
    
    
    /**
     * Store user id
     * 
     * @version 1.0
     * @param   string  $user       User ID to store
     * @result  void
     */
    
    public static function user($user)
    {
        self::$user = $user;
    }
    
    
    /**
     * Add a top level object to the result JSON
     * 
     * @version 1.0
     * @param   string  $name       Name of the ressource added
     * @param   string  $value      Value of the ressource added
     * @result  void
     */
    
    public static function add($name, $value) {
        self::$data[$name] = $value;
    }
    
    
    /**
     * Store an error into the API result, display it and stop script execution
     * 
     * @version 1.0
     * @param   string  $http_response  HTTP Error code to send with JSON
     * @param   string  $code           Error code
     * @param   string  $message        Error message
     * @result  void
     */
    
    public static function error($http_response, $code, $message)
    {
        // we store error data
        self::$success = false;
        self::$response = $http_response;
        self::$errors[] = array(
            'status' => $http_response,
            'code' => $code,
            'title' => $message
        );
        
        // we parse JSON
        self::parsing();
        
        // we load JSON result to client
        self::result();
        
        // we stop script execution
        exit;
    }
    
    
    /**
     * API result parsing
     * 
     * Parse result array to JSON format before sending it to the user
     * 
     * @version 1.0
     * @return  void
     */
    
    public static function parsing()
    {
        // we initiate JSON array
        $json = array();
        
        switch (self::$success) {
            case true:
                // we check if we have informations to send
                if (count(self::$data)) { $json = self::$data; }
                
                break;
            
            case false:
                // we check if we have informations to send
                if (count(self::$data)) { $json = self::$data; }
                
                // if API call returns an error, we parse error's informations in JSON
                $json['errors'] = self::$errors;
                                
                break;
        }
        
        // We look if a debug information is asked
        if (isset(self::$headers['X-Debug']) && self::$headers['X-Debug']) {
            // we initiate json debug result
            $json['debug'] = array();
            $json['debug']['method'] = $_SERVER['REQUEST_METHOD'];
            
            // if we stored a token
            if (!empty(self::$token)) {
                $json['debug']['token'] = self::$token;
            }
            
            // we get all headers
            $json['debug']['headers'] = self::$headers;
            $json['debug']['body'] = self::$body;
        }
        
        // we parse json array
        self::$json = json_encode($json);
    }
    
    
    /**
     * API displaying result method
     * 
     * @version 1.0
     * @return  void
     */
    
    public static function result()
    {
        // we send an authorization demand
        if (self::$response == 401) { header('WWW-Authenticate: Basic realm="LeQG App Authorization"'); }
        
        // ETag header
        $etag = md5(self::$json);
        
        // check if client send ETag information & if content etag = client etag
        if (isset(self::$headers['If-None-Match']) && $etag == self::$headers['If-None-Match']) {
            http_response_code(304);
            
        } else {
            // we send http response code
            http_response_code(self::$response);
            
            // we send ETag information
            header('ETag: ' . md5(self::$json));
    
            // we display json string
            print(self::$json.PHP_EOL);
            
            // we stop script execution
            exit;
        }
    }
}
