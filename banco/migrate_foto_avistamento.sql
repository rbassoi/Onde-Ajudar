-- Migration: adiciona coluna de foto à tabela abordagem
-- Execute apenas se o banco já existia antes desta feature.

ALTER TABLE abordagem
    ADD COLUMN IF NOT EXISTS foto_avistamento VARCHAR(255);
