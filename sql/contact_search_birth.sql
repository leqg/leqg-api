SELECT      `contact_id` AS `id`
FROM        `contacts`
WHERE       `contact_naissance_date` = :search
ORDER BY    `contact_nom` ASC,
            `contact_nom_usage` ASC,
            `contact_prenoms` ASC