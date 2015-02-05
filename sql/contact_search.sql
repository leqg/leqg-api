SELECT      `contact_id` AS `id`
FROM        `contacts`
WHERE       CONCAT_WS(" ", contact_prenoms, contact_nom, contact_nom_usage, contact_nom, contact_prenoms) LIKE :search
ORDER BY    `contact_nom` ASC,
            `contact_nom_usage` ASC,
            `contact_prenoms` ASC