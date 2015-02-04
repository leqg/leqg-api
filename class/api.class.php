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
     */
    private static $token, $user, $client, $json;
    private static $data = array();
    private static $errors = array();
    private static $success = true;
    private static $response = 202;
    
    
    /**
     * API initialization
     * 
     * Launch all needed services to response to API request, first by checking
     * authentification
     * 
     * @version 1.0
     * @return  void
     */
    
    public static function init()
    {
        return true;
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
        return Configuration::read('db.core')->prepare(file_get_contents("sql/$sql.sql"));
    }
    
    
    /**
     * Authentification method
     * 
     * @version 1.0
     * @return  bool    Result of authentification
     */
    
    public static function auth()
    {
        // we look at headers informations
        $headers = getallheaders();
        
        // if client wants to try an authentification
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) && !isset($headers['X-Debug'])) {
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
                        // we create a token
                        self::token();
                    }
                    
                } else {
                    // password not matching
                    self::error(403, 'BadPW', 'Wrong password');
                }
                
            } else {
                // no user found
                self::error(403, 'NoUser', 'User not found');
            }
            
        } elseif (!isset($headers['X-Debug'])) {
            // we look if client send a token in HTTP headers
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $tokenHTTP = explode(' ', $headers['Authorization']);
                $tokenComplet = explode(':', base64_decode($tokenHTTP[1]));
                $client = $tokenComplet[0];
                $token = $tokenComplet[1];
                
                // we check token validity
                $query = self::dbcore('auth_id_by_token');
                $query->bindParam(':token', $token);
                $query->execute();
                
                // si le token est valide
                if ($query->rowCount() == 1) {
                    $data = $query->fetch();
                    
                    // we store all known informations
                    self::client($client);
                    self::token($token);
                    self::user($data['id']);
                    
                } else {
                    // no valid token, please authentificate yourself
                    self::error(403, 'NonValidToken', 'No valid token, please authenticate yourself.');
                }
                
            } else {
                // if we have no token and no authentification try
                self::error(403, 'NoToken', 'No token, please send one or authenticate yourself.');
            }
            
        } else {
            // if a debug request is asked, we load request with dev user
            self::client('dev');
            self::user(1);
            self::token();
        }
    }
    
    
    /**
     * Store or create a token
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
            $token = uniqid(dechex(rand()));
            
            // we had the token to the core database
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
        
        // if we have a token, we add token in json response
        if (!empty(self::$token)) {
            // we create a chain by client hostname & token concatenation
            $chain = base64_encode(self::$client . ':' . self::$token);

            // we add this chain to the JSON result array
            $json['token'] = $chain;
        }
        
        switch (self::$success) {
            case true:
                $json['success'] = true;
            
                // we check if we have informations to send
                if (count(self::$data)) { $json['data'] = self::$data; }
                
                break;
            
            case false:
                // if API call returns an error, we parse error's informations in JSON
                $json['errors'] = self::$errors;
                $json['method'] = $_SERVER['REQUEST_METHOD'];
                
                // we check if we have informations to send
                if (count(self::$data)) { $json['data'] = self::$data; }
                
                break;
        }
        
        // We look if a debug information is asked
        $headers = getallheaders();
        if (isset($headers['X-Debug']) && $headers['X-Debug']) {
            // we initiate json debug result
            $json['debug'] = array();
            
            // we check if we have some HTTP request data
            if (!empty($_REQUEST)) {
                $json['debug']['request_data'] = $_REQUEST;
            }
            
            // we check if we have HTTP header for authorization
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $json['debug']['auth']['user'] = $_SERVER['PHP_AUTH_USER'];
                $json['debug']['auth']['pass'] = $_SERVER['PHP_AUTH_PW'];
            }
            
            // we get all headers
            $json['debug']['headers'] = getallheaders();
            
            // we check if we have a token send by HTTP Authorization system
            if (isset($json['debug']['headers']['Authorization'])) {
                $token = explode(' ', $json['debug']['headers']['Authorization']);
                $json['debug']['token'] = $token[1];
            }
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
        // we send http response code
        http_response_code(self::$response);

        // we display json string
        print_r(self::$json);
    }
}
