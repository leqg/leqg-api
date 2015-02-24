SELECT      `id`
FROM        `people`
WHERE       `date_naissance` = :search
ORDER BY    `nom` ASC,
            `nom_usage` ASC,
            `prenoms` ASC