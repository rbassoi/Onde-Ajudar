-- Migration: adiciona colunas de avistamentos à tabela abordagem
-- Execute apenas se o banco já existia antes desta feature.

ALTER TABLE abordagem
    ADD COLUMN IF NOT EXISTS pessoas_count       SMALLINT    DEFAULT 1,
    ADD COLUMN IF NOT EXISTS necessidades        TEXT,
    ADD COLUMN IF NOT EXISTS latitude            DECIMAL(10,7),
    ADD COLUMN IF NOT EXISTS longitude           DECIMAL(10,7),
    ADD COLUMN IF NOT EXISTS contato_registrante VARCHAR(255),
    ADD COLUMN IF NOT EXISTS status_avistamento  VARCHAR(20)  DEFAULT 'pendente';

ALTER TABLE abordagem
    ADD CONSTRAINT IF NOT EXISTS chk_status_avistamento
    CHECK (status_avistamento IN ('urgente','pendente','atendido'));
