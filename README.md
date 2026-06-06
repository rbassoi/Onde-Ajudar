# Onde Ajudar — Plataforma de Cuidado Coletivo

Sistema web/mobile de cuidado comunitário com múltiplos módulos de ajuda. Cidadãos, voluntários, ONGs e autoridades colaboram em um feed vivo da cidade — registrando avistamentos, buscando desaparecidos, localizando animais perdidos e encontrando restaurantes populares.

---

## Módulos

### 🏠 População em Situação de Rua

- **Registro rápido de avistamentos** — Formulário acessível com geolocalização automática via browser (reverse geocoding Nominatim)
- **Cascata de localização** — Estado → Cidade (AJAX) → Bairro (datalist com sugestões do banco)
- **Foto do avistamento** — Captura via câmera (`capture="environment"`) ou upload
- **Contador de pessoas** — Controle +/− com mínimo de 1
- **Chips de necessidades** — Seleção visual (alimentação, roupa, saúde etc.)
- **Modal de confirmação de localização** — Alerta quando a posição não pode ser verificada
- **Feed com filtros** — Por status (urgente / pendente / atendido) e localização
- **Mapa interativo** — Pins coloridos por status, clusters, mapa de calor e localização do usuário com pulsação animada
- **Cadastro completo** — Dados pessoais, documentos, histórico de abordagens, deficiências e upload de fotos/documentos

### 🔍 Pessoas Desaparecidas

- Registro de desaparecidos com foto, descrição, último local e contato
- Feed de casos com status **aberto** / **encontrado**
- Busca e compartilhamento de alertas
- Atualização de status pelo registrador ou por administradores

### 🍽️ Restaurantes Populares

- Listagem de restaurantes com refeições a preço acessível
- Filtros por estado e cidade
- Informações de cardápio, horários (café / almoço / jantar) e preços
- Mapa com localização de cada restaurante (Leaflet)
- Cardápio detalhado por refeição

### 🐾 Animais Desaparecidos

- Registro de animais perdidos ou encontrados com foto, espécie, raça, cor, porte e localização
- Feed com status **perdido** / **encontrado** e recompensa opcional
- Mapa com pins por localização do avistamento
- Atualização de status ao reunir o animal com a família

### 📊 Dashboard

- **Cards de resumo** — Total de avistamentos, urgentes, pendentes, atendidos, desaparecidos em aberto, animais perdidos, restaurantes ativos e cadastros PSR
- **Gráfico de linha** — Avistamentos diários nos últimos 30 dias
- **Gráficos de donut** — Status de avistamentos, desaparecidos e animais
- **Gráfico de barras** — Distribuição por faixa etária (PSR)
- **Gráfico de rosca** — Distribuição por sexo (PSR)
- **Tabelas recentes** — Últimos avistamentos e desaparecidos registrados

---

## Perfis de Acesso

### Autoridades

| Perfil        | Acesso                                                           |
|---------------|------------------------------------------------------------------|
| Administrador | Total — gerencia usuários, cadastros, dashboard e configurações  |
| Comandante    | Operacional completo — cadastros, abordagens, dashboard          |
| MP            | Ministério Público — visualização e relatórios                   |
| Guarnição     | Registro de abordagens e avistamentos em campo                   |
| Central       | Somente visualização e consultas                                  |

### Público geral

| Perfil     | Acesso                                                                     |
|------------|----------------------------------------------------------------------------|
| Cidadão    | Registra avistamentos e desaparecidos, acompanha o feed e visualiza o mapa |
| Voluntário | Registra e pode marcar casos como atendidos em qualquer módulo             |
| ONG        | Organização parceira — visualiza, registra e atualiza status de casos      |

---

## Stack Tecnológica

| Camada      | Tecnologia                                                   |
|-------------|--------------------------------------------------------------|
| Backend     | PHP 8.2, PDO, procedural                                     |
| Banco       | PostgreSQL 16                                                |
| Frontend    | HTML5, CSS3, JavaScript (ES2020+)                            |
| Gráficos    | Chart.js 4.4                                                 |
| Mapas       | Leaflet 1.9, Leaflet.markercluster, Leaflet.heat             |
| Geocoding   | Nominatim (OpenStreetMap) — reverse geocoding sem API key    |
| Servidor    | Apache 2.4 (PHP 8.2 official Docker image)                   |
| Infra       | Docker Compose (app PHP + PostgreSQL)                        |

---

## Instalação com Docker (recomendado)

### Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e rodando

### Passos

1. Clone o repositório:
   ```bash
   git clone <url-do-repositorio> onde-ajudar
   cd onde-ajudar
   ```

2. Suba os containers:
   ```bash
   docker compose up -d --build
   ```
   O banco é criado automaticamente (`schema.sql` e `seeds.sql` são montados como init scripts do PostgreSQL).

3. Acesse no navegador:
   ```
   http://localhost:8088/login.php
   ```

4. Aplique migrations e importe as cidades brasileiras:
   ```
   http://localhost:8088/setup_cidades.php
   ```
   Clique em **"Aplicar migrations e importar cidades"**. Isso cria as tabelas dos módulos novos e popula `cidade` com ~185 cidades de todos os 27 estados.

5. Login padrão (criado pelo `seeds.sql`):
   - **Usuário:** `admin`
   - **Senha:** `admin1234`

---

## Instalação manual (XAMPP / LAMP)

### Pré-requisitos

- PHP 7.4+ com extensão `pdo_pgsql` habilitada
- PostgreSQL 13+
- Apache com `mod_rewrite` habilitado

### Passos

1. Clone na pasta do servidor web e crie o banco:
   ```bash
   psql -U postgres -c "CREATE DATABASE moradores_de_rua;"
   psql -U postgres -d moradores_de_rua -f banco/schema.sql
   psql -U postgres -d moradores_de_rua -f banco/seeds.sql
   ```

2. Aplique as migrations dos módulos adicionais:
   ```bash
   psql -U postgres -d moradores_de_rua -f banco/migrate_avistamentos.sql
   psql -U postgres -d moradores_de_rua -f banco/migrate_desaparecidos.sql
   psql -U postgres -d moradores_de_rua -f banco/migrate_animais_desaparecidos.sql
   psql -U postgres -d moradores_de_rua -f banco/migrate_restaurantes.sql
   psql -U postgres -d moradores_de_rua -f banco/migrate_restaurantes_latlng.sql
   psql -U postgres -d moradores_de_rua -f banco/migrate_cardapio.sql
   psql -U postgres -d moradores_de_rua -f banco/seeds_cidades_brasil.sql
   ```

3. Configure a conexão em `conexao.php`:
   ```php
   define('PG_HOST',     'localhost');
   define('PG_PORT',     '5432');
   define('PG_USER',     'postgres');
   define('PG_PASSWORD', 'sua_senha');
   define('PG_DB_NAME',  'moradores_de_rua');
   ```

4. Acesse `http://localhost/onde-ajudar/login.php`.

---

## Estrutura de Arquivos

### Páginas principais

| Arquivo                   | Módulo / Função                                              |
|---------------------------|--------------------------------------------------------------|
| `landing.php`             | Landing page pública — apresenta todos os módulos           |
| `onboarding.php`          | Tela de boas-vindas mobile                                   |
| `login.php`               | Autenticação                                                 |
| `home.php`                | Hub central — escolha do módulo após login                   |
| `avistamentos.php`        | PSR — Feed, mapa e registro de avistamentos                  |
| `index.php`               | PSR — Listagem e busca de cadastros                          |
| `ver_cadastro.php`        | PSR — Visualização completa do cadastro                      |
| `cad_editar.php`          | PSR — Edição do cadastro                                     |
| `desaparecidos.php`       | Pessoas desaparecidas — feed e registro                      |
| `animais_desaparecidos.php` | Animais desaparecidos — feed, mapa e registro              |
| `restaurantes.php`        | Restaurantes populares — listagem, mapa e cardápio           |
| `dashboard.php`           | Dashboard — cards e gráficos de todos os módulos            |
| `usuarios_cadastro.php`   | Gestão de usuários do sistema                                |
| `setup_cidades.php`       | Admin: aplica migrations e importa cidades                   |

### Processadores (POST handlers)

| Arquivo                        | Função                                                |
|--------------------------------|-------------------------------------------------------|
| `avistamentos_processa.php`    | Registra avistamento e faz upload de foto             |
| `desaparecidos_processa.php`   | Registra / atualiza pessoas desaparecidas             |
| `animais_desaparecidos_processa.php` | Registra / atualiza animais desaparecidos       |
| `admin_restaurantes_processa.php` | Cria e edita restaurantes populares               |
| `busca_localidade.php`         | AJAX: cidades por estado / bairros por cidade         |
| `login_valida.php`             | Valida login e redireciona                            |
| `cad_processa.php`             | Processa cadastro de morador PSR                      |

### Frontend & Design

| Caminho                | Conteúdo                                                         |
|------------------------|------------------------------------------------------------------|
| `estilos/css/app.css`  | Design system — tokens, componentes, clusters de mapa, animações |
| `estilos/js/app.js`    | Tabs, chips, contador, mapa Leaflet, filtros                     |
| `includes/header.php`  | Navbar compartilhada com todos os módulos                        |
| `includes/footer.php`  | Rodapé e scripts compartilhados                                  |

### Banco de dados

| Caminho                                  | Conteúdo                                              |
|------------------------------------------|-------------------------------------------------------|
| `banco/schema.sql`                       | DDL completo — tabelas base do sistema PSR            |
| `banco/seeds.sql`                        | Dados de referência e usuário admin                   |
| `banco/migrate_avistamentos.sql`         | Colunas de avistamento rápido na tabela `abordagem`   |
| `banco/migrate_desaparecidos.sql`        | Tabela `desaparecidos`                                |
| `banco/migrate_animais_desaparecidos.sql`| Tabela `animais_desaparecidos`                        |
| `banco/migrate_restaurantes.sql`         | Tabela `restaurantes_populares`                       |
| `banco/migrate_restaurantes_latlng.sql`  | Colunas de geolocalização em `restaurantes_populares` |
| `banco/migrate_cardapio.sql`             | Tabela de cardápio dos restaurantes                   |
| `banco/migrate_funcao.sql`               | Coluna `funcao` em `usuarios`                         |
| `banco/migrate_foto_avistamento.sql`     | Coluna `foto_avistamento` em `abordagem`              |
| `banco/seeds_cidades_brasil.sql`         | ~185 cidades de todos os 27 estados                   |
| `banco/seeds_restaurantes_bh.sql`        | Restaurantes populares de Belo Horizonte              |
| `banco/seeds_cardapio_bh_jun2026.sql`    | Cardápio dos restaurantes de BH                       |

---

## Estrutura do Banco de Dados

### Tabelas principais

| Tabela                   | Descrição                                                 |
|--------------------------|-----------------------------------------------------------|
| `cadastro`               | Registro principal de cada morador PSR                    |
| `abordagem`              | Avistamentos e abordagens (lat/lng, foto, status)         |
| `desaparecidos`          | Registro de pessoas desaparecidas                         |
| `animais_desaparecidos`  | Registro de animais perdidos ou encontrados               |
| `restaurantes_populares` | Restaurantes com refeições a preço popular                |
| `usuarios`               | Contas do sistema                                         |
| `arquivos`               | Arquivos binários (fotos/documentos do cadastro PSR)      |
| `cidade`                 | Municípios brasileiros (~185 cidades pré-carregadas)      |
| `estados`                | 27 estados brasileiros                                    |

---

## Fluxo do usuário

```
Landing (landing.php)
    │
    ├─ Mobile → Onboarding (onboarding.php)
    └─ Criar conta / Entrar → Login (login.php)
                                    │
                                    └─ Home (home.php) — escolha do módulo
                                            │
                                            ├─ 🏠 Avistamentos → avistamentos.php (feed / mapa / registro)
                                            ├─ 🔍 Desaparecidos → desaparecidos.php
                                            ├─ 🍽️ Restaurantes → restaurantes.php
                                            ├─ 🐾 Animais → animais_desaparecidos.php
                                            └─ (nav) Dashboard → dashboard.php
```

---

## Avisos de Segurança

Este sistema foi desenvolvido para uso interno em rede controlada. Para deploy em produção exposta, os seguintes pontos devem ser corrigidos:

- Substituir SHA1 por `password_hash()` / `password_verify()` para senhas
- Adicionar tokens CSRF nos formulários
- Mover credenciais do banco para variáveis de ambiente (`.env`)
- Reforçar validação e sanitização no upload de arquivos

---

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).
