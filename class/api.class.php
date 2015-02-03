<?php
/**
 * @package     LeQG
 * @author      Damien Senger <tech@leqg.info>
 * @copyright   2014-2015 MSQG SAS – LeQG
 */

class API
{
    /**
     * @val     array   $data       Informations to send, before JSON formatting
     * @val     bool    $success    API call status (true success, false error)
     * @val     int     $response   HTTP response code to send with JSON
     * @val     array   $json       JSON array to send
     * @val     bool    $errors     Errors informations
     */
    private static $data = array();
    private static $success = true;
    private static $response = 202;
    private static $json;
    private static $errors = array();
    
    
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
     * API result parsing
     * 
     * Parse result array to JSON format before sending it to the user
     * 
     * @version 1.0
     * @return  void
     */
    
    public static function parsing()
    {
        switch (self::$success) {
            case true:
                // if API call is a success, we merge system informations and datas
                $json = array('success' => true);
                
                // we check if we have informations to send
                if (self::$data) { $json['data'] = self::$data; }
                
                // we parse data in a json format
                self::$json = json_encode($json);
                
                break;
            
            case false:
                // if API call returns an error, we parse error's informations in JSON
                $result = array(
                    'success' => false,
                    'error' => self::$errors
                );
                
                // we check if we have informations to send
                if (self::$data) { $json['data'] = self::$data; }
                
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
