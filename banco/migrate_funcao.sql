-- Migration: substitui cargos militares por funções inclusivas
-- Execute em bancos criados antes desta mudança.

-- 1. Insere novos valores primeiro (sem apagar os antigos ainda)
INSERT INTO funcao (funcao) VALUES
    ('Autoridade Pública'),
    ('Ministério Público'),
    ('Assistência Social'),
    ('Saúde'),
    ('ONG / Voluntariado'),
    ('Cidadão'),
    ('Pesquisador / Academia'),
    ('Imprensa / Comunicação')
ON CONFLICT DO NOTHING;

-- 2. Migra todos os usuários que apontavam para cargos militares
UPDATE usuarios
SET funcao = (SELECT id FROM funcao WHERE funcao = 'Autoridade Pública')
WHERE funcao IN (
    SELECT id FROM funcao
    WHERE funcao IN ('Soldado','Cabo','Sargento','Subtenente',
                     'Tenente','Capitão','Major','Tenente-Coronel','Coronel')
);

-- 3. Remove os cargos militares
DELETE FROM funcao
WHERE funcao IN ('Soldado','Cabo','Sargento','Subtenente',
                 'Tenente','Capitão','Major','Tenente-Coronel','Coronel');
