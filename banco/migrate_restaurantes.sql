CREATE TABLE IF NOT EXISTS restaurantes_populares (
    id              SERIAL PRIMARY KEY,
    nome            VARCHAR(200)    NOT NULL,
    endereco        VARCHAR(300)    NOT NULL,
    bairro          VARCHAR(100),
    cidade          VARCHAR(100)    NOT NULL,
    estado          VARCHAR(2)      NOT NULL,
    horario_cafe    VARCHAR(80),
    horario_almoco  VARCHAR(80),
    horario_jantar  VARCHAR(80),
    preco_cafe      DECIMAL(5,2),
    preco_almoco    DECIMAL(5,2),
    preco_jantar    DECIMAL(5,2),
    observacoes     TEXT,
    status          VARCHAR(20)     NOT NULL DEFAULT 'ativo',
    ativo           BOOLEAN         NOT NULL DEFAULT TRUE,
    criado_em       TIMESTAMP       NOT NULL DEFAULT NOW()
);
