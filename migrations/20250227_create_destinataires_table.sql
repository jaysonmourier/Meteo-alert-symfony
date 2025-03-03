CREATE TABLE destinataires (
    id SERIAL PRIMARY KEY,
    insee VARCHAR(5) NOT NULL,
    telephone VARCHAR(15) NOT NULL
);