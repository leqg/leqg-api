SELECT  `coordonnee_type` AS `type`,
        `coordonnee_numero` AS `numero`,
        `coordonnee_email` AS `email`,
        `contact_id` AS `contact`
FROM    `coordonnees`
WHERE   `contact_id` = :contact
AND     `coordonnee_id` = :id