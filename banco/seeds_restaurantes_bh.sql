-- Restaurantes Populares de Belo Horizonte / MG
INSERT INTO restaurantes_populares
    (nome, endereco, bairro, cidade, estado, horario_cafe, horario_almoco, horario_jantar,
     preco_cafe, preco_almoco, preco_jantar, status, observacoes)
SELECT * FROM (VALUES
    (
        'Restaurante Popular I – Herbert de Souza',
        'Avenida do Contorno, 11.484',
        'Centro',
        'Belo Horizonte', 'MG',
        '7h às 8h', '11h às 14h', '17h às 21h',
        0.75::DECIMAL, 3.00::DECIMAL, 1.50::DECIMAL,
        'ativo',
        NULL
    ),
    (
        'Restaurante Popular II – Josué de Castro',
        'Rua Ceará, 490',
        'Santa Efigênia',
        'Belo Horizonte', 'MG',
        '7h às 8h', '11h às 13h', '17h às 18h',
        0.75::DECIMAL, 3.00::DECIMAL, 1.50::DECIMAL,
        'obras',
        'Em obras. Funcionamento exclusivo para a população em situação de rua, com entrega de marmitex. Usuários devem se dirigir ao Restaurante Popular I – Herbert de Souza (Av. do Contorno, 11.484 – Centro).'
    ),
    (
        'Restaurante Popular III – Maria Regina Nabuco',
        'Rua Padre Pedro Pinto, 2.277',
        'Venda Nova',
        'Belo Horizonte', 'MG',
        NULL, '11h às 13h', NULL,
        0.75::DECIMAL, 3.00::DECIMAL, 1.50::DECIMAL,
        'obras',
        'Em obras. Distribuição do almoço com entrega de marmitex. Estação BHBus – Venda Nova.'
    ),
    (
        'Restaurante Popular IV – Dom Mauro Bastos',
        'Rua Afonso Vaz de Melo, 1.001',
        'Barreiro',
        'Belo Horizonte', 'MG',
        NULL, '11h às 14h', NULL,
        0.75::DECIMAL, 3.00::DECIMAL, 1.50::DECIMAL,
        'ativo',
        NULL
    ),
    (
        'Refeitório Popular João Bosco Murta Lages',
        'Avenida dos Andradas, 3100',
        'Santa Efigênia',
        'Belo Horizonte', 'MG',
        NULL, '11h às 14h', NULL,
        0.75::DECIMAL, 3.00::DECIMAL, 1.50::DECIMAL,
        'ativo',
        'Acesso pela Portaria 3, na Avenida Churchill.'
    )
) AS v(nome, endereco, bairro, cidade, estado,
       horario_cafe, horario_almoco, horario_jantar,
       preco_cafe, preco_almoco, preco_jantar, status, observacoes)
WHERE NOT EXISTS (
    SELECT 1 FROM restaurantes_populares
    WHERE nome = v.nome AND cidade = v.cidade
);
