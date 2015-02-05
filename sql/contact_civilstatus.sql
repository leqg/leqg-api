SELECT  `contact_id` AS `id`,
        `contact_nom` AS `nom`,
        `contact_nom_usage` AS `nom_usage`,
        `contact_prenoms` AS `prenoms`,
        `contact_naissance_date` AS `date_naissance`
FROM    `contacts` 
WHERE   `contact_id` = :id