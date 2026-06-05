CREATE TABLE IF NOT EXISTS desaparecidos (
    id               SERIAL PRIMARY KEY,
    nome             VARCHAR(200)    NOT NULL,
    idade            INT,
    origem           VARCHAR(200),
    ultimo_local     VARCHAR(300)    NOT NULL,
    telefone_contato VARCHAR(30)     NOT NULL,
    foto             VARCHAR(255),
    descricao        TEXT,
    status           VARCHAR(20)     NOT NULL DEFAULT 'aberto',
    registrado_por   INT             REFERENCES usuarios(id),
    criado_em        TIMESTAMP       NOT NULL DEFAULT NOW()
);
