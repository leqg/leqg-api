SELECT  `id`,
        `nom`,
        `nom_usage`,
        `prenoms`,
        `date_naissance`
FROM    `people` 
WHERE   `id` = :id