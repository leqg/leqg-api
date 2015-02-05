SELECT  `contact_id` AS `id`,
        `contact_nom` AS `nom`,
        `contact_nom_usage` AS `nom_usage`,
        `contact_prenoms` AS `prenoms`,
        `contact_naissance_date` AS `date_naissance`,
        `contact_sexe` AS `sexe`,
        `contact_electeur` AS `est_electeur`,
        `immeuble_id` AS `adresse_electorale`,
        `bureau_id` AS `bureau_vote`,
        `adresse_id` AS `adresse_declaree`,
        `contact_organisme` AS `organisme`,
        `contact_fonction` AS `fonction`,
        `contact_tags` AS `tags`
FROM    `contacts` 
WHERE   `contact_id` = :id