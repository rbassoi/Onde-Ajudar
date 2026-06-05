-- Cardápio semanal — Restaurantes Populares Belo Horizonte
-- Semanas: 01/06/2026 a 14/06/2026
-- Fonte: https://prefeitura.pbh.gov.br/seguranca-alimentar-nutricional/equipamentos/restaurantes-populares

INSERT INTO cardapio_semanal (cidade, estado, data_cardapio, refeicao, itens, apenas_pop_rua)
VALUES

-- === SEMANA 1: 01 a 07/06/2026 ===

-- Segunda 01/06
('Belo Horizonte','MG','2026-06-01','almoco',
 'Arroz · Tutu de Feijão · Pernil em Cubos ao Molho · Omelete / Hambúrguer de Grão-de-Bico · Macarrão Parafuso ao Alho e Óleo com Cenoura · Repolho com Tomate · Doce',
 FALSE),
('Belo Horizonte','MG','2026-06-01','jantar',
 'Sopa de Macarrão com Legumes',
 FALSE),

-- Terça 02/06
('Belo Horizonte','MG','2026-06-02','almoco',
 'Arroz · Feijão · Pescoço de Peru ao Molho · Ovo Mexido / Almôndega de Lentilha · Virado de Couve · Acelga · Fruta',
 FALSE),
('Belo Horizonte','MG','2026-06-02','jantar',
 'Arroz e Creme de Milho',
 FALSE),

-- Quarta 03/06
('Belo Horizonte','MG','2026-06-03','almoco',
 'Arroz · Feijão · Frango Assado ao Molho Vermelho · Ovo Cozido / Almôndega de Grão-de-Bico · Moranga Acebolada · Tabule com Pepino · Canjica',
 FALSE),
('Belo Horizonte','MG','2026-06-03','jantar',
 'Mugica',
 FALSE),

-- Quinta 04/06 — apenas pop. rua
('Belo Horizonte','MG','2026-06-04','almoco',
 'Arroz · Linguiça Assada · Virado de Feijão Branco com Bacon · Couve',
 TRUE),

-- Sexta 05/06
('Belo Horizonte','MG','2026-06-05','almoco',
 'Arroz · Feijão · Carne Moída com Quiabo · Ovo Mexido / Hambúrguer de Lentilha · Angu · Couve · Fruta',
 FALSE),
('Belo Horizonte','MG','2026-06-05','jantar',
 'Bambá de Couve',
 FALSE),

-- Sábado 06/06 — apenas pop. rua
('Belo Horizonte','MG','2026-06-06','almoco',
 'Arroz ao Alho e Cenoura · Estrogonofe de Frango',
 TRUE),

-- Domingo 07/06 — apenas pop. rua
('Belo Horizonte','MG','2026-06-07','almoco',
 'Arroz · Linguiça Assada ao Molho · Farofa de Cenoura e Vagem',
 TRUE),

-- === SEMANA 2: 08 a 14/06/2026 ===

-- Segunda 08/06
('Belo Horizonte','MG','2026-06-08','almoco',
 'Arroz · Feijão · Pernil em Cubos ao Molho · Ovo Cozido / Almôndega de Lentilha · Batata Palito Frita · Couve · Doce',
 FALSE),
('Belo Horizonte','MG','2026-06-08','jantar',
 'Arroz e Pirão de Carne',
 FALSE),

-- Terça 09/06
('Belo Horizonte','MG','2026-06-09','almoco',
 'Arroz · Feijão · Falsa Bacalhoada · Ovo Mexido / Almôndega de Grão-de-Bico · Almeirão ao Alho e Óleo · Beterraba Ralada com Ervilha · Fruta',
 FALSE),
('Belo Horizonte','MG','2026-06-09','jantar',
 'Creme de Lentilha',
 FALSE),

-- Quarta 10/06
('Belo Horizonte','MG','2026-06-10','almoco',
 'Arroz · Feijão · Frango Assado ao Molho de Manjericão · Ovo Cozido / Quibe de Grão-de-Bico com Moranga e Hortelã · Cenoura Vick · Mix de Repolhos com Laranja · Canjica',
 FALSE),
('Belo Horizonte','MG','2026-06-10','jantar',
 'Canjiquinha',
 FALSE),

-- Quinta 11/06
('Belo Horizonte','MG','2026-06-11','almoco',
 'Arroz · Feijão · Pescoço de Peru ao Molho · Ovo Mexido / Hambúrguer de Feijão · Purê de Mandioquinha · Acelga com Agrião · Canjica',
 FALSE),
('Belo Horizonte','MG','2026-06-11','jantar',
 'Arroz e Creme de Mandioquinha',
 FALSE),

-- Sexta 12/06
('Belo Horizonte','MG','2026-06-12','almoco',
 'Arroz · Tutu de Feijão · Bisteca Assada ao Molho Vermelho · Omelete / Hambúrguer de Grão-de-Bico · Macarrão Espaguete ao Sugo · Alface com Rúcula · Fruta',
 FALSE),
('Belo Horizonte','MG','2026-06-12','jantar',
 'Sopa de Macarrão com Legumes',
 FALSE),

-- Sábado 13/06 — apenas pop. rua
('Belo Horizonte','MG','2026-06-13','almoco',
 'Arroz · Frango Assado ao Molho · Purê de Batata',
 TRUE),

-- Domingo 14/06 — apenas pop. rua
('Belo Horizonte','MG','2026-06-14','almoco',
 'Arroz · Bisteca Assada ao Molho · Feijão Enriquecido com Couve',
 TRUE)

ON CONFLICT (cidade, estado, data_cardapio, refeicao) DO UPDATE
    SET itens          = EXCLUDED.itens,
        apenas_pop_rua = EXCLUDED.apenas_pop_rua;
