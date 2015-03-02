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
        if (isset(API::$module[1]) && !empty(API::$module[1])) {
            // we launch rounting method
            self::routing();
            
        } else {
            // we check if we have an argument asked by client
            if (!empty($_SERVER['QUERY_STRING'])) {
                $args = explode('&', $_SERVER['QUERY_STRING']);
                
                foreach ($args as $arg) {
                    $query = explode('=', $arg);
                    
                    if ($query[0] == 'search') { self::search($query[1]); }
                }
                
            // if we have any request, 404 Error
            } else {
                API::error(404, 'UnknownModule', 'Vous demandez un module qui n\'existe pas');
            }
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
        if (isset(API::$module[1]{0}) && is_numeric(API::$module[1]{0})) {
            // we check if client asked a submodule
            if (isset(API::$module[2])) {
                if (API::$module[2] == 'coordonnee') {
                    // we check if client ask one or more contact details informations
                    $details = explode(',', API::$module[3]);
                    
                    foreach ($details as $detail) {
                        self::contact_detail(API::$module[1], $detail);
                    }
                    
                } elseif (API::$module[2] == 'interaction') {
                    $events = explode(',', API::$module[3]);
                    
                    foreach ($events as $event) {
                        self::interaction(API::$module[1], $event);
                    }
                }
                
            } else {
                $contacts = explode(',', API::$module[1]);
                
                // we check that each contact asked is an id
                foreach ($contacts as $contact) { if (!is_numeric($contact)) { API::error(404, 'UnknownModule', 'Vous demandez un module qui n\'existe pas'); } }
                
                // we return contact informations for each asked contact
                foreach ($contacts as $contact) {
                    self::contact($contact);
                }
            }
            
        // else we return an 404 Error
        } else {
            API::error(404, 'UnknownModule', 'Vous demandez un module qui n\'existe pas');
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
            
            // we search all addresses for this contact
            $query = API::query('contact_addresses');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            
            // we prepare linked informations
            $data['links'] = array(
                'bureau' => $data['bureau_vote'],
                'adresse_officiel' => false,
                'adresse_reel' => false
            );
            
            // we unset old linked informations
            unset($data['bureau_vote']);
            
            if ($query->rowCount()) {
                $addresses = $query->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($addresses as $address) {
                    $data['links']['adresse_'.$address['type']] = true;
                }
            }
            
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
            
            // we search contact's interactions
            $query = API::query('contact_interactions');
            $query->bindParam(':contact', $id, PDO::PARAM_INT);
            $query->execute();
            
            // if we found at least one interaction
            if ($query->rowCount()) {
                $events = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($events as $event) { $data['links']['interactions'][] = $event['id']; }
            }
            
            // we prepare top-level links description
            API::link('contacts', 'adresse_officiel', 'adresse', 'contact/{contacts.id}/adresse/officiel', false);
            API::link('contacts', 'adresse_reel', 'adresse', 'contact/{contacts.id}/adresse/reel', false);
            API::link('contacts', 'bureau_vote', 'immeuble', 'cartographie/bureau/');
            API::link('contacts', 'email', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
            API::link('contacts', 'mobile', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
            API::link('contacts', 'fixe', 'coordonnee', 'contact/{contacts.id}/coordonnee/');
            API::link('contacts', 'interactions', 'interaction', 'contact/{contacts.id}/interaction/');
           
            // we add contact information to JSON response
            API::add('contacts', $data);
            
        } else {
            // we display an error
            API::error(404, 'ContactUnknown', 'Le contact demandé n\'existe pas.');
        }
    }
    
    
    /**
     * Return civil status of an asked contact
     *
     * @version 1.0
     * @param   int     $id         Contact ID
     * @return  array               Contact civil status
     */
    
    public static function civilstatus($id)
    {
        // we load informations from database
        $query = API::query('contact_civilstatus');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        
        // if we found a contact
        if ($query->rowCount() == 1) {
             // Yay! we have a contact!
            API::response(200);
            
            // we load contact informations
            $data = $query->fetch(PDO::FETCH_ASSOC);
            $data['url'] = Configuration::read('url').'contact/'.$id;
            
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
    
    
    /**
     * Contact search method
     *
     * @version 1.0
     * @param   string  $search     Search terms
     * @return  array               Found contacts ID
     */
    
    public static function search($search)
    {
        $date = DateTime::createFromFormat('d/m/Y', $search);
        
        // we check if search terms are or not a birth date
        if ($date) {
            // we format date
            $search = $date->format('Y-m-d');
            
            // we search all contacts with birthday on search date
            $query = API::query('contact_search_birth');
            $query->bindParam(':search', $search);
            $query->execute();
            
        } else {
            // search term formatting
            $search = '%'.preg_replace('#[^[:alpha:]]#u', '%', trim($search)).'%';
            
            // we search in database
            $query = API::query('contact_search');
            $query->bindParam(':search', $search);
            $query->execute();
        }
        
        if ($query->rowCount()) {
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $contact) {
                self::civilstatus($contact['id']);
            }
            
            API::response(200);
           
        } else {
            API::response(200);
        }
    }
    
    
    /**
     * Return informations about an interaction
     * 
     * @version 1.0
     * @param   int     $contact        Contact ID
     * @param   int     $id             Interaction ID
     * @return  void
     */
    
    public function interaction($contact, $id)
    {
        // we search interaction data
        $query = API::query('contact_interaction');
        $query->bindParam(':event', $id, PDO::PARAM_INT);
        $query->bindParam(':contact', $contact, PDO::PARAM_INT);
        $query->execute();
        
        // we check if wa have an answer
        if ($query->rowCount() == 1) {
            // Yay! we have a contact detail!
            API::response(200);
            
            // we load contact informations
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            // we put contact into links section
            $data['links']['contact'] = $data['contact'];
            API::link('interactions', 'contact', 'contact');
            unset($data['contact']);
            
            // we put directory data into links section, if exists
            if (!is_null($data['dossier'])) {
                $data['links']['dossier'] = $data['dossier'];
                API::link('interactions', 'dossier', 'dossier');
                unset($data['dossier']);
            } else {
                unset($data['dossier']);
            }
            
            // we put user data into links section
            $data['links']['utilisateur'] = $data['utilisateur'];
            API::link('interactions', 'utilisateur', 'utilisateur');
            unset($data['utilisateur']);
            
            // we add contact information to JSON response
            API::add('interactions', $data);
        } else {
            // we display an error
            API::error(404, 'EventUnknown', 'L\'élément d\'historique demandé n\'existe pas.');
        }
    }
}
