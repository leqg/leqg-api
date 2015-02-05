SELECT      `coordonnee_id` AS `id`,
            `coordonnee_type` AS `type`
FROM        `coordonnees`
WHERE       `contact_id` = :id
ORDER BY    `coordonnee_id` ASC