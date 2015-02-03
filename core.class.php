<?php
/**
 * @package     LeQG
 * @author      Damien Senger <tech@leqg.info>
 * @copyright   2014-2015 MSQG SAS – LeQG
 */

class Core
{
    /**
     * Debuging script method
     *
     * This method displays an object's content through a var_dump() function and
     * preformatted HTML format and can easily stop script execution.
     * 
     * @version 1.0
     * @param   mixed   $object     Analysed object
     * @param   bool    $end        True to end script execution
     * @return  void
     */
    
    public static function debug($object, $end = true)
    {
        // First we display a pre tag
        echo '<pre class="nowrap">';
        
        // We search the best way to display object's content by its type
        $type = gettype($object);
        
        switch ($type) {
            case "array":
                print_r($object);
                break;
            
            case "object":
                print_r($object);
                break;
            
            default:
                var_dump($object);
        }
        
        // We close the pre tag
        echo '</pre>';
        
        // If asked, we end script execution
        if ($end) die;
    }
}
