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
     * @val     string  $json       JSON array to send
     * @val     bool    $errors     Errors informations
     * @val     array   $data       Informations to send, before JSON formatting
     * @val     bool    $success    API call status (true success, false error)
     * @val     int     $response   HTTP response code to send with JSON
     */
    private static $token, $user, $json, $error;
    private static $data = array();
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
     * Authentification method
     * 
     * @version 1.0
     * @return  bool    Result of authentification
     */
    
    public static function auth()
    {
        // we charge login data
        //$user = $_SERVER['PHP_AUTH_USER'];
        //$pass = $_SERVER['PHP_AUTH_PW'];
        $user = 'mail@damiensenger.me'; $pass = 'evecsanobi-67';
        
        // we check if this user exist in LeQG Central Auth DB
        $query = self::dbcore('auth_login');
        $query->bindParam(':mail', $user);
        $query->execute();
        
        if ($query->rowCount() == 1) {
            // we load user data
            $data = $query->fetch();
            
            // we verify both password
            if (password_verify($pass, $data['password'])) {
                // we check if we already have a recent connection token
                $query = self::dbcore('auth_token_looking');
                $query->bindParam(':id', $data['id']);
                $query->execute();
                
                // we check if we found a token
                if ($query->rowCount() ==1) {
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
                self::error(403, 'Wrong password');
            }
        } else {
            // no user found
            self::error(403, 'User not found');
        }
        
        
        Core::debug($query->rowCount());
        
        return true;
    }
    
    
    /**
     * Store an error into the API result, display it and stop script execution
     * 
     * @version 1.0
     * @param   string  $code       HTTP Error code to send with JSON
     * @param   string  $message    Error message
     * @result  void
     */
    
    public static function error($code, $message)
    {
        // we store error data
        self::$success = false;
        self::$response = $code;
        self::$error = $message;
        
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
        $json = array('success' => self::$success);
        
        switch (self::$success) {
            case true:                
                // we check if we have informations to send
                if (count(self::$data)) { $json['data'] = self::$data; }
                
                // we parse data in a json format
                self::$json = json_encode($json);
                
                break;
            
            case false:
                // if API call returns an error, we parse error's informations in JSON
                $json = array(
                    'error' => self::$error,
                    'debug' => array(
                        'request_method' => $_SERVER['REQUEST_METHOD']
                    )
                );
                
                // we check if we have some HTTP request data
                if (!empty($_REQUEST)) {
                    $json['debug']['request_data'] = $_REQUEST;
                }
                
                // we check if we have HTTP header for authorization
                if (isset($_SERVER['PHP_AUTH_USER'])) {
                    $json['debug']['auth']['user'] = $_SERVER['PHP_AUTH_USER'];
                    $json['debug']['auth']['pass'] = $_SERVER['PHP_AUTH_PW'];
                }
                
                // we check if we have informations to send
                if (count(self::$data)) { $json['data'] = self::$data; }
                
                // we parse data in a json format
                self::$json = json_encode($json);
                
                break;
        }
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
