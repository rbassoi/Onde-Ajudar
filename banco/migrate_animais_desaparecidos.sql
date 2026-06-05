CREATE TABLE IF NOT EXISTS animais_desaparecidos (
    id               SERIAL PRIMARY KEY,
    especie          VARCHAR(50)  NOT NULL,
    raca             VARCHAR(100),
    nome_animal      VARCHAR(100),
    cor              VARCHAR(100) NOT NULL,
    porte            VARCHAR(20),
    sexo             VARCHAR(20)  NOT NULL DEFAULT 'desconhecido',
    ultimo_local     VARCHAR(300) NOT NULL,
    telefone_contato VARCHAR(30)  NOT NULL,
    recompensa       DECIMAL(8,2),
    descricao        TEXT,
    foto             VARCHAR(255),
    status           VARCHAR(20)  NOT NULL DEFAULT 'perdido',
    latitude         DECIMAL(9,6),
    longitude        DECIMAL(9,6),
    registrado_por   INT REFERENCES usuarios(id),
    criado_em        TIMESTAMP NOT NULL DEFAULT NOW()
);
