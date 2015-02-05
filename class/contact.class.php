<?php
/**
 * @package     LeQG
 * @author      Damien Senger <tech@leqg.info>
 * @copyright   2014-2015 MSQG SAS – LeQG
 */

class Contact
{
    /**
     * Contact class init
     * 
     * This method can call every asked submodule of contact and launch every API request
     *
     * @version 1.0
     * @return  void
     */
    
    public function __construct()
    {
        // we check if a submodule is asked
        if (isset(API::$module[1])) {
            // we launch rounting method
            self::routing();
        }
    }
    
    
    /**
     * Intern routing method
     * 
     * @version 1.0
     * @return  void
     */
    
    private function routing()
    {
        // we check if one contact is asked (contact/{id})
        if (is_numeric(API::$module[1])) {
            // we check if client asked a submodule
            if (isset(API::$module[2])) {
                if (API::$module[2] == 'coordonnee') {
                    // we check if client ask one or more contact details informations
                    $details = explode(',', API::$module[3]);
                    
                    foreach ($details as $detail) {
                        self::contact_detail(API::$module[1], $detail);
                    }
                }
                
            } else {
                // we return contact informations
                self::contact(API::$module[1]);
            }
        }
    }
    
    
    /**
     * Return contact data informations
     *
     * @version 1.0
     * @param   int     $id         Contact ID
     * @return  array               Contact informations
     */
    
    public static function contact($id)
    {
        // we load informations from database
        $query = API::query('contact_informations');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        
        // if we found a contact
        if ($query->rowCount() == 1) {
            // Yay! we have a contact!
            API::response(200);
            
            // we load contact informations
            $data = $query->fetch(PDO::FETCH_ASSOC);
             
            // we prepare id & url informations
            $data['url'] = Configuration::read('url').'contact/'.$data['id'];
            
            // we prepare linked informations
            $data['links'] = array(
                'adresse_electorale' => $data['adresse_electorale'],
                'adresse_declaree' => $data['adresse_declaree'],
                'bureau' => $data['bureau_vote']
            );
            
            // we unset old linked informations
            unset($data['adresse_electorale'], $data['adresse_declaree'], $data['bureau_vote']);
            
            // we search contact details
            $query = API::query('contact_details');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            
            // if we found a contact details
            if ($query->rowCount()) {
                $details = $query->fetchAll(PDO::FETCH_ASSOC);
                $detail_data['email'] = array();
                $detail_data['mobile'] = array();
                $detail_data['fixe'] = array();
                
                foreach ($details as $detail) { 
                    $detail_data[$detail['type']][] = $detail['id'];
                }
                
                if (count($detail_data['email'])) { $data['links']['email'] = $detail_data['email']; }
                if (count($detail_data['mobile'])) { $data['links']['mobile'] = $detail_data['mobile']; }
                if (count($detail_data['fixe'])) { $data['links']['fixe'] = $detail_data['fixe']; }
            }
            
            // we prepare top-level links description
            API::link('contacts', 'adresse_electorale', 'immeuble', 'cartographie/adresse/');
            API::link('contacts', 'adresse_declaree', 'immeuble', 'cartographie/adresse/');
            API::link('contacts', 'bureau_vote', 'immeuble', 'cartographie/bureau/');
            API::link('contacts', 'email', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
            API::link('contacts', 'mobile', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
            API::link('contacts', 'fixe', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
           
            // we add contact information to JSON response
            API::add('contacts', $data);
            
        } else {
            // we display an error
            API::error(404, 'ContactUnknown', 'Le contact demandé n\'existe pas.');
        }
    }
    
    
    /**
     * Return contact detail data informations
     *
     * @version 1.0
     * @param   int     $contact    Contact ID
     * @param   int     $id         Contact detail ID
     * @return  array               Contact detail informations
     */
    
    public static function contact_detail($contact, $id)
    {
        // we load informations from database
        $query = API::query('contact_detail');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':contact', $contact, PDO::PARAM_INT);
        $query->execute();
        
        // if we found a contact detail
        if ($query->rowCount() == 1) {
            // Yay! we have a contact detail!
            API::response(200);
            
            // we load contact informations
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            // we purge numero or email
            if ($data['type'] == 'email') { unset($data['numero']); } else { unset($data['email']); }
            
            // we put contact into links section
            $data['links']['contact'] = $data['contact'];
            API::link('coordonnees', 'contact', 'contact');
            unset($data['contact']);
             
            // we add contact information to JSON response
            API::add('coordonnees', $data);
        } else {
            // we display an error
            API::error(404, 'ContactDetailUnknown', 'L\'élément de contact demandé n\'existe pas.');
        }
    }
}
