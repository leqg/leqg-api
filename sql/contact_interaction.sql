SELECT      `historique_id` AS `id`,
            `contact_id` AS `contact`,
            `compte_id` AS `utilisateur`, 
            `dossier_id` AS `dossier`,
            `historique_type` AS `type`,
            `historique_date` AS `date`, 
            `historique_lieu` AS `lieu`,
            `historique_objet` AS `objet`,
            `historique_notes` AS `notes`
FROM        `historique`
WHERE       `historique_id` = :event
AND         `contact_id` = :contact