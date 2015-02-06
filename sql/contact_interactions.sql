SELECT      `historique_id` AS `id`
FROM        `historique`
WHERE       `contact_id` = :contact
ORDER BY    `historique_date` DESC,
            `historique_timestamp` DESC