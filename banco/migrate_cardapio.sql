CREATE TABLE IF NOT EXISTS cardapio_semanal (
    id              SERIAL PRIMARY KEY,
    cidade          VARCHAR(100) NOT NULL,
    estado          VARCHAR(2)   NOT NULL,
    data_cardapio   DATE         NOT NULL,
    refeicao        VARCHAR(10)  NOT NULL CHECK (refeicao IN ('cafe', 'almoco', 'jantar')),
    itens           TEXT         NOT NULL,
    apenas_pop_rua  BOOLEAN      NOT NULL DEFAULT FALSE,
    criado_em       TIMESTAMP    NOT NULL DEFAULT NOW(),
    UNIQUE(cidade, estado, data_cardapio, refeicao)
);
