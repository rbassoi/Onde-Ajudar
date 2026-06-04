-- =============================================================
-- Seeds — dados de referência iniciais
-- Sistema de Moradores de Rua (PMSC)
-- =============================================================

-- Sexo
INSERT INTO sexo (sexo) VALUES ('Masculino'), ('Feminino'), ('Outro') ON CONFLICT DO NOTHING;

-- Estados brasileiros
INSERT INTO estados (estado) VALUES
    ('Acre'), ('Alagoas'), ('Amapá'), ('Amazonas'), ('Bahia'),
    ('Ceará'), ('Distrito Federal'), ('Espírito Santo'), ('Goiás'),
    ('Maranhão'), ('Mato Grosso'), ('Mato Grosso do Sul'), ('Minas Gerais'),
    ('Pará'), ('Paraíba'), ('Paraná'), ('Pernambuco'), ('Piauí'),
    ('Rio de Janeiro'), ('Rio Grande do Norte'), ('Rio Grande do Sul'),
    ('Rondônia'), ('Roraima'), ('Santa Catarina'), ('São Paulo'),
    ('Sergipe'), ('Tocantins')
ON CONFLICT DO NOTHING;

-- Cidades de Santa Catarina (exemplos)
INSERT INTO cidade (cidade, estado_id) VALUES
    ('Florianópolis', (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Joinville',     (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Blumenau',      (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('São José',      (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Criciúma',      (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Chapecó',       (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Itajaí',        (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Lages',         (SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Jaraguá do Sul',(SELECT id FROM estados WHERE estado = 'Santa Catarina')),
    ('Palhoça',       (SELECT id FROM estados WHERE estado = 'Santa Catarina'))
ON CONFLICT DO NOTHING;

-- Situação do cadastro
INSERT INTO cadastro_situacao (situacao) VALUES
    ('Ativo'), ('Inativo'), ('Encaminhado'), ('Falecido')
ON CONFLICT DO NOTHING;

-- Escolaridade
INSERT INTO cadastro_escolaridade (escolaridade) VALUES
    ('Sem escolaridade'),
    ('Fundamental incompleto'),
    ('Fundamental completo'),
    ('Médio incompleto'),
    ('Médio completo'),
    ('Superior incompleto'),
    ('Superior completo')
ON CONFLICT DO NOTHING;

-- Deficiência
INSERT INTO deficiencia (deficiente) VALUES
    ('Não possui'),
    ('Física'),
    ('Visual'),
    ('Auditiva'),
    ('Intelectual'),
    ('Múltipla')
ON CONFLICT DO NOTHING;

-- Passagem (histórico criminal)
INSERT INTO passagem (passagem) VALUES
    ('Não possui'),
    ('Furto/Roubo'),
    ('Uso de drogas'),
    ('Tráfico'),
    ('Violência doméstica'),
    ('Outros')
ON CONFLICT DO NOTHING;

-- Situação de rua
INSERT INTO situacao_rua (situacao_rua) VALUES
    ('Mora nas ruas'),
    ('Pernoita nas ruas'),
    ('Usa serviços de acolhimento'),
    ('Situação transitória')
ON CONFLICT DO NOTHING;

-- Cores de status (indicador visual)
INSERT INTO cor (cor) VALUES
    ('Verde'),    -- 1: contato recente (≤ 7 dias)
    ('Vermelho'), -- 2: alerta
    ('Amarelo'),  -- 3: contato moderado
    ('Branco')    -- 4: sem abordagem
ON CONFLICT DO NOTHING;

-- Perfil do abordado
INSERT INTO abordado_perfil (perfil) VALUES
    ('Adulto'), ('Idoso'), ('Jovem'), ('Criança/Adolescente'),
    ('Família'), ('Com deficiência'), ('Usuário de drogas')
ON CONFLICT DO NOTHING;

-- Perfis de usuário — autoridades
INSERT INTO perfil (perfil) VALUES
    ('Administrador'), ('Comandante'), ('MP'), ('Guarnição'), ('Central')
ON CONFLICT DO NOTHING;

-- Perfis de usuário — público geral
INSERT INTO perfil (perfil) VALUES
    ('Cidadão'),    -- registra avistamentos e acompanha o feed
    ('Voluntário'), -- pode marcar casos como atendidos
    ('ONG')         -- organização parceira, visualiza e atualiza status
ON CONFLICT DO NOTHING;

-- Situação de usuário
INSERT INTO usuarios_situacao (situacao) VALUES ('Ativo'), ('Bloqueado') ON CONFLICT DO NOTHING;

-- Funções / tipos de atuação
INSERT INTO funcao (funcao) VALUES
    ('Autoridade Pública'),       -- polícia, guarda municipal, defesa civil
    ('Ministério Público'),
    ('Assistência Social'),       -- CRAS, CREAS, Secretaria Social
    ('Saúde'),                    -- UBS, CAPS, hospital, agente comunitário
    ('ONG / Voluntariado'),
    ('Cidadão'),
    ('Pesquisador / Academia'),
    ('Imprensa / Comunicação')
ON CONFLICT DO NOTHING;

-- Encaminhamentos
INSERT INTO encaminhamento (descricao) VALUES
    ('Centro POP'),
    ('CAPS'),
    ('Albergue / Casa de Acolhida'),
    ('Emprego / CRAS'),
    ('Curso Profissionalizante'),
    ('Passagem Intermunicipal'),
    ('Hospital / UPA'),
    ('Não aceitou encaminhamento')
ON CONFLICT DO NOTHING;

-- Locais de abordagem
INSERT INTO local (local) VALUES
    ('Calçada / Passeio Público'),
    ('Praça / Parque'),
    ('Embaixo de Ponte/Viaduto'),
    ('Ponto de Ônibus'),
    ('Comércio / Centro'),
    ('Área Industrial'),
    ('Beira de Rio'),
    ('Outros')
ON CONFLICT DO NOTHING;

-- Condições de ambiente
INSERT INTO ambiente (condicao) VALUES
    ('Bom'), ('Regular'), ('Precário'), ('Insalubre')
ON CONFLICT DO NOTHING;

-- Intervalos de faixa etária
INSERT INTO intervalo (descricao, idademinima, idademaxima) VALUES
    ('0 a 17 anos',   0,  17),
    ('18 a 29 anos', 18,  29),
    ('30 a 39 anos', 30,  39),
    ('40 a 49 anos', 40,  49),
    ('50 a 59 anos', 50,  59),
    ('60 anos ou mais', 60, 150)
ON CONFLICT DO NOTHING;

-- Tipo de usuário (referência para cadastro.usuario)
INSERT INTO usuario (usuario) VALUES
    ('Familiar'), ('Assistência Social'), ('Policial'), ('Voluntário')
ON CONFLICT DO NOTHING;

-- Usuário administrador padrão (senha: admin1234 em SHA1)
-- SHA1('admin1234') = 4e7afebcfbae000b22c7c85e5560f89a2a0280b4
INSERT INTO usuarios (login, senha, email, nome, bloqueado, perfil, funcao)
VALUES (
    'admin',
    '4e7afebcfbae000b22c7c85e5560f89a2a0280b4',
    'admin@pmsc.sc.gov.br',
    'Administrador',
    1,
    (SELECT id FROM perfil WHERE perfil = 'Administrador'),
    (SELECT id FROM funcao WHERE funcao = 'Autoridade Pública')
) ON CONFLICT (login) DO NOTHING;
