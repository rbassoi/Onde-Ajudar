-- =============================================================
-- Schema PostgreSQL — Sistema de Moradores de Rua (PMSC)
-- Banco: moradores_de_rua
-- =============================================================

-- Tabelas de referência (lookup)

CREATE TABLE IF NOT EXISTS sexo (
    id   SERIAL PRIMARY KEY,
    sexo VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS estados (
    id     SERIAL PRIMARY KEY,
    estado VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS cidade (
    id       SERIAL PRIMARY KEY,
    cidade   VARCHAR(255) NOT NULL,
    estado_id INTEGER REFERENCES estados(id)
);

CREATE TABLE IF NOT EXISTS cadastro_situacao (
    id       SERIAL PRIMARY KEY,
    situacao VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS cadastro_escolaridade (
    id           SERIAL PRIMARY KEY,
    escolaridade VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS deficiencia (
    id         SERIAL PRIMARY KEY,
    deficiente VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS passagem (
    id       SERIAL PRIMARY KEY,
    passagem VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS situacao_rua (
    id           SERIAL PRIMARY KEY,
    situacao_rua VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS cor (
    id  SERIAL PRIMARY KEY,
    cor VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS abordado_perfil (
    id     SERIAL PRIMARY KEY,
    perfil VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS funcao (
    id     SERIAL PRIMARY KEY,
    funcao VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS perfil (
    id     SERIAL PRIMARY KEY,
    perfil VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS usuarios_situacao (
    id       SERIAL PRIMARY KEY,
    situacao VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS encaminhamento (
    id       SERIAL PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS local (
    id    SERIAL PRIMARY KEY,
    local VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS ambiente (
    id      SERIAL PRIMARY KEY,
    condicao VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS intervalo (
    id           SERIAL PRIMARY KEY,
    descricao    VARCHAR(100) NOT NULL,
    idademinima  INTEGER NOT NULL,
    idademaxima  INTEGER NOT NULL
);

-- Tipo de usuário (cadastro.usuario FK)
CREATE TABLE IF NOT EXISTS usuario (
    id      SERIAL PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL
);

-- =============================================================
-- Tabelas principais
-- =============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id               SERIAL PRIMARY KEY,
    login            VARCHAR(100) NOT NULL UNIQUE,
    senha            VARCHAR(255) NOT NULL,
    email            VARCHAR(255),
    nome             VARCHAR(255) NOT NULL,
    bloqueado        SMALLINT     NOT NULL DEFAULT 1,
    data_cadastro    TIMESTAMP    DEFAULT NOW(),
    perfil           INTEGER      REFERENCES perfil(id),
    usuario_cadastro VARCHAR(100),
    sexo             INTEGER      REFERENCES sexo(id),
    matricula        VARCHAR(50),
    funcao           INTEGER      REFERENCES funcao(id)
);

CREATE TABLE IF NOT EXISTS cadastro (
    id               SERIAL PRIMARY KEY,
    nome             VARCHAR(255) NOT NULL,
    rg               VARCHAR(30),
    cpf              VARCHAR(20),
    situacao         INTEGER      DEFAULT 1,
    data_nascimento  DATE,
    sexo             INTEGER      REFERENCES sexo(id),
    estado           INTEGER      REFERENCES estados(id),
    cidade           INTEGER      REFERENCES cidade(id),
    escolaridade     INTEGER      REFERENCES cadastro_escolaridade(id),
    deficiencia      INTEGER      REFERENCES deficiencia(id),
    tipo_deficiencia TEXT,
    motivo           TEXT,
    usuario          INTEGER,
    tipo_usuario     INTEGER,
    situacao_rua     INTEGER      REFERENCES situacao_rua(id),
    passagem         INTEGER      REFERENCES passagem(id),
    tipo_passagem    TEXT,
    complemento      TEXT,
    cor              INTEGER      REFERENCES cor(id),
    data_cadastro    TIMESTAMP    DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS abordagem (
    id                    SERIAL PRIMARY KEY,
    id_morador            INTEGER   NOT NULL REFERENCES cadastro(id) ON DELETE CASCADE,
    data_abordagem        TIMESTAMP DEFAULT NOW(),
    cidade                INTEGER   REFERENCES cidade(id),
    bairro                VARCHAR(255),
    endereco              VARCHAR(255),
    tempo_cidade          VARCHAR(100),
    tempo_ficar           VARCHAR(100),
    aceitou_encaminhamento SMALLINT DEFAULT 0,
    tipo_encaminhamento   INTEGER   REFERENCES encaminhamento(id),
    tipo_curso            VARCHAR(255),
    porta_objetos         SMALLINT  DEFAULT 0,
    objetos               TEXT,
    relato                TEXT,
    local_abordagem       INTEGER   REFERENCES local(id),
    condicao_ambiente     INTEGER   REFERENCES ambiente(id),
    limpeza_ambiente      VARCHAR(100),
    complemento           TEXT,
    responsavel_abordagem VARCHAR(255),
    usuario_registro      VARCHAR(255),
    funcao                INTEGER,
    matricula             VARCHAR(50),
    cor                   SMALLINT,
    -- Campos de avistamento rápido (Front/)
    pessoas_count         SMALLINT    DEFAULT 1,
    necessidades          TEXT,
    latitude              DECIMAL(10,7),
    longitude             DECIMAL(10,7),
    contato_registrante   VARCHAR(255),
    status_avistamento    VARCHAR(20)  DEFAULT 'pendente' CHECK (status_avistamento IN ('urgente','pendente','atendido'))
);

CREATE TABLE IF NOT EXISTS fotos (
    id           SERIAL PRIMARY KEY,
    id_morador   INTEGER      NOT NULL REFERENCES cadastro(id) ON DELETE CASCADE,
    foto_nome    VARCHAR(255),
    foto_perfil  VARCHAR(10),
    foto_tipo    VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS arquivos (
    codigo     SERIAL PRIMARY KEY,
    nmarquivo  VARCHAR(255),
    descricao  TEXT,
    arquivo    BYTEA,
    tipo       VARCHAR(100),
    tamanho    INTEGER,
    dthrenvio  TIMESTAMP DEFAULT NOW(),
    idvitima   INTEGER   REFERENCES cadastro(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reset_tokens (
    id         SERIAL PRIMARY KEY,
    usuario_id INTEGER   NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expira_em  TIMESTAMP NOT NULL,
    usado      BOOLEAN   DEFAULT FALSE,
    criado_em  TIMESTAMP DEFAULT NOW()
);

-- =============================================================
-- Índices para performance
-- =============================================================

CREATE INDEX IF NOT EXISTS idx_reset_tokens_token ON reset_tokens(token);

CREATE INDEX IF NOT EXISTS idx_cadastro_nome        ON cadastro(nome);
CREATE INDEX IF NOT EXISTS idx_cadastro_cpf         ON cadastro(cpf);
CREATE INDEX IF NOT EXISTS idx_cadastro_rg          ON cadastro(rg);
CREATE INDEX IF NOT EXISTS idx_cadastro_situacao    ON cadastro(situacao);
CREATE INDEX IF NOT EXISTS idx_abordagem_id_morador ON abordagem(id_morador);
CREATE INDEX IF NOT EXISTS idx_abordagem_data       ON abordagem(data_abordagem);
CREATE INDEX IF NOT EXISTS idx_arquivos_idvitima    ON arquivos(idvitima);
