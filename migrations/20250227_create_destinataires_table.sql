CREATE TABLE destinataires (
    id SERIAL PRIMARY KEY,
    insee VARCHAR(5) NOT NULL,
    telephone VARCHAR(15) NOT NULL
);

ALTER TABLE destinataires
ADD CONSTRAINT unique_insee_telephone UNIQUE (insee, telephone);

CREATE INDEX idx_destinataires_insee ON destinataires (insee);