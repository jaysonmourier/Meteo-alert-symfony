-- Ajout d'une contrainte d'unicité sur la combinaison (insee, telephone)
ALTER TABLE destinataires
ADD CONSTRAINT unique_insee_telephone UNIQUE (insee, telephone);

-- Création d'un index sur le champ insee pour améliorer les performances des requêtes
CREATE INDEX idx_destinataires_insee ON destinataires (insee);