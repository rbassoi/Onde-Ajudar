# Onde Ajudar — Sistema de Avistamentos e Gestão

Sistema web/mobile de cuidado comunitário para registro e acompanhamento de pessoas em situação de rua. Qualquer cidadão pode registrar um avistamento rapidamente; autoridades e voluntários acompanham o feed, gerenciam cadastros e coordenam o atendimento.

---

## Funcionalidades

### Avistamentos (página principal)

- **Registro rápido** — Formulário acessível direto ao entrar no sistema ou clicar no logo "Onde Ajudar"
- **Geolocalização automática** — Detecta a posição do usuário via browser e preenche Estado/Cidade/Bairro via Nominatim (reverse geocoding)
- **Cascata de localização** — Seleção de Estado → Cidade (carregada via AJAX) → Bairro (datalist com sugestões do banco)
- **Foto do avistamento** — Captura via câmera do dispositivo (`capture="environment"`) ou upload de arquivo
- **Contador de pessoas** — Controle +/− com valor mínimo de 1
- **Chips de necessidades** — Seleção visual de necessidades (alimentação, roupa, saúde etc.)
- **Modal de confirmação de localização** — Se a posição do usuário não puder ser verificada, exibe alerta informando que falsa informação pode prejudicar as autoridades antes de salvar
- **Feed de avistamentos** — Listagem com filtros por status (urgente / pendente / atendido) e localização (Estado / Cidade / Bairro)
- **Mapa interativo** — Pins com cores por status, agrupados em clusters; alternância para mapa de calor; localização do usuário com pulsação animada e zoom automático para avistamentos próximos

### Cadastro de moradores

- Dados pessoais, documentos, histórico criminal, deficiências e situação de rua
- Upload de fotos e documentos
- Histórico de abordagens
- Busca por nome, RG, CPF, cidade ou perfil

### Sistema

- **Relatórios** — Estatísticas por faixa etária
- **Gestão de usuários** — Cadastro, edição, bloqueio e controle de perfis de acesso
- **Setup de banco** — Página de administração para aplicar migrations e importar cidades brasileiras via browser

---

## Perfis de Acesso

### Autoridades

| Perfil        | Acesso                                                           |
|---------------|------------------------------------------------------------------|
| Administrador | Total — gerencia usuários, cadastros, relatórios e configurações |
| Comandante    | Operacional completo — cadastros, abordagens, relatórios         |
| MP            | Ministério Público — visualização e relatórios                   |
| Guarnição     | Registro de abordagens e avistamentos em campo                   |
| Central       | Somente visualização e consultas                                  |

### Público geral

| Perfil     | Acesso                                                                |
|------------|-----------------------------------------------------------------------|
| Cidadão    | Registra avistamentos, acompanha o feed e visualiza o mapa            |
| Voluntário | Registra avistamentos e pode marcar casos como atendidos              |
| ONG        | Organização parceira — visualiza, registra e atualiza status de casos |

---

## Stack Tecnológica

| Camada      | Tecnologia                                                   |
|-------------|--------------------------------------------------------------|
| Backend     | PHP 8.2, PDO, procedural                                     |
| Banco       | PostgreSQL 16                                                |
| Frontend    | HTML5, CSS3, JavaScript (ES2020+), jQuery                    |
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
   Clique em **"Aplicar migrations e importar cidades"**. Isso adiciona a coluna `foto_avistamento` na tabela `abordagem` e popula a tabela `cidade` com ~185 cidades de todos os 27 estados.

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

2. Configure a conexão em [conexao.php](conexao.php):
   ```php
   define('PG_HOST',     'localhost');
   define('PG_PORT',     '5432');
   define('PG_USER',     'postgres');
   define('PG_PASSWORD', 'sua_senha');
   define('PG_DB_NAME',  'moradores_de_rua');
   ```

3. Acesse `http://localhost/onde-ajudar/login.php` e visite `setup_cidades.php` para aplicar as migrations.

---

## Estrutura de Arquivos

### Páginas principais

| Arquivo                   | Função                                                   |
|---------------------------|----------------------------------------------------------|
| `login.php`               | Autenticação                                             |
| `avistamentos.php`        | **Página inicial** — Feed, Mapa e Registro de avistamentos |
| `index.php`               | Cadastro de moradores — listagem e busca                 |
| `ver_cadastro.php`        | Visualização completa do cadastro                        |
| `cad_editar.php`          | Formulário de edição do cadastro                         |
| `relatorio_idade.php`     | Relatório por faixa etária                               |
| `usuarios_cadastro.php`   | Gestão de usuários do sistema                            |
| `setup_cidades.php`       | Admin: aplica migrations e importa cidades               |

### Processadores (POST handlers)

| Arquivo                        | Função                                               |
|--------------------------------|------------------------------------------------------|
| `avistamentos_processa.php`    | Registra avistamento e faz upload de foto            |
| `busca_localidade.php`         | AJAX: retorna cidades por estado / bairros por cidade |
| `login_valida.php`             | Valida login e redireciona para `avistamentos.php#registrar` |
| `cad_processa.php`             | Processa cadastro de morador                         |

### Frontend & Design

| Caminho                | Conteúdo                                                       |
|------------------------|----------------------------------------------------------------|
| `estilos/css/app.css`  | Design system — tokens, componentes, clusters de mapa, animações |
| `estilos/js/app.js`    | Tabs, chips, contador, mapa Leaflet (clusters + heatmap), filtros |
| `includes/header.php`  | Navbar compartilhada — logo aponta para `avistamentos.php#registrar` |
| `includes/footer.php`  | Rodapé e scripts compartilhados                                |

### Banco de dados

| Caminho                              | Conteúdo                                          |
|--------------------------------------|---------------------------------------------------|
| `banco/schema.sql`                   | DDL completo (PostgreSQL)                         |
| `banco/seeds.sql`                    | Dados de referência iniciais e usuário admin      |
| `banco/migrate_avistamentos.sql`     | Adiciona colunas de avistamento na tabela abordagem |
| `banco/migrate_funcao.sql`           | Adiciona coluna `funcao` na tabela usuarios       |
| `banco/migrate_foto_avistamento.sql` | Adiciona coluna `foto_avistamento` em abordagem   |
| `banco/seeds_cidades_brasil.sql`     | ~185 cidades de todos os 27 estados               |

---

## Estrutura do Banco de Dados

### Tabelas principais

| Tabela      | Descrição                                              |
|-------------|--------------------------------------------------------|
| `cadastro`  | Registro principal de cada morador                     |
| `abordagem` | Avistamentos e abordagens (inclui lat/lng, foto, status) |
| `usuarios`  | Contas do sistema                                      |
| `arquivos`  | Arquivos binários (fotos/documentos do cadastro)       |
| `cidade`    | Municípios brasileiros (~185 cidades pré-carregadas)   |
| `estados`   | 27 estados brasileiros                                 |

### Colunas adicionadas por migrations

| Coluna                        | Tabela      | Tipo           | Descrição                              |
|-------------------------------|-------------|----------------|----------------------------------------|
| `latitude`, `longitude`       | `abordagem` | `DECIMAL(9,6)` | Coordenadas GPS do avistamento         |
| `status_avistamento`          | `abordagem` | `VARCHAR`      | `urgente`, `pendente` ou `atendido`    |
| `pessoas_count`               | `abordagem` | `INT`          | Número de pessoas avistadas            |
| `necessidades`                | `abordagem` | `TEXT`         | Lista de necessidades (chips)          |
| `contato_registrante`         | `abordagem` | `VARCHAR`      | Contato opcional de quem registrou     |
| `relato`                      | `abordagem` | `TEXT`         | Descrição livre do avistamento         |
| `cor`                         | `abordagem` | `VARCHAR`      | Identificação visual                   |
| `foto_avistamento`            | `abordagem` | `VARCHAR(255)` | Caminho da foto tirada no registro     |

---

## Fluxo do usuário

```
Login → avistamentos.php#registrar (aba Registrar aberta)
          │
          ├─ Clica logo "Onde Ajudar" → volta para #registrar (sem reload)
          ├─ Aba "Feed" → lista de avistamentos com filtros de status e localização
          └─ Aba "Mapa" → mapa Leaflet com clusters, heatmap e localização do usuário
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
