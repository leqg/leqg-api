SELECT  `id`,
        `nom`,
        `nom_usage`,
        `prenoms`,
        `date_naissance`,
        `sexe`,
        `electeur` AS `est_electeur`,
        `bureau` AS `bureau_vote`,
        `organisme`,
        `fonction`,
        `tags`
FROM    `people` 
WHERE   `id` = :id