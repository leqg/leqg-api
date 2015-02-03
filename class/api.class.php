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
    private $data = array();
    private $success = true;
    private $response = 202;
    private $json;
    private $errors = array();
    
    
    /**
     * API initialization
     * 
     * Launch all needed services to response to API request, first by checking
     * authentification
     * 
     * @version 1.0
     * @return  void
     */
    
    public function __construct()
    {
        
    }
    
    
    /**
     * API result parsing
     * 
     * Parse result array to JSON format before sending it to the user
     * 
     * @version 1.0
     * @return  void
     */
    
    public function parsing()
    {
        switch ($this->success) {
            case true:
                // if API call is a success, we merge system informations and datas
                $json = array('success' => true);
                
                // we check if we have informations to send
                if ($this->data) { $json['data'] = $this->data; }
                
                // we parse data in a json format
                $this->json = json_encode($json);
                
                break;
            
            case false:
                // if API call returns an error, we parse error's informations in JSON
                $result = array(
                    'success' => false,
                    'error' => $this->errors
                );
                
                // we check if we have informations to send
                if ($this->data) { $json['data'] = $this->data; }
                
                break;
        }
    }
    
    
    /**
     * API displaying result method
     * 
     * @version 1.0
     * @return  void
     */
    
    public function result()
    {
        // we send http response code
        http_response_code($this->response);
        
        // we display json string
        print_r($this->json);
    }
}
