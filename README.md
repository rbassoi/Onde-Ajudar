# Moradores de Rua — Sistema de Gestão

Sistema web/mobile de cuidado comunitário para registro e acompanhamento de pessoas em situação de rua. Conecta cidadãos, voluntários, ONGs e assistência social — qualquer pessoa pode registrar um avistamento, acompanhar o feed e ajudar a coordenar o cuidado.

---

## Funcionalidades

- **Cadastro de moradores** — dados pessoais, documentos, histórico criminal, deficiências e situação de rua
- **Registro de abordagens** — data, local, encaminhamentos realizados (Centro POP, CAPS, albergue, emprego, passagem etc.)
- **Busca e filtragem** — por nome, RG, CPF, cidade ou perfil; indicador visual de dias desde o último contato
- **Upload de arquivos** — fotos e documentos vinculados ao cadastro
- **Relatórios** — estatísticas por faixa etária
- **Gestão de usuários** — cadastro, edição, bloqueio e controle de perfis de acesso

---

## Perfis de Acesso

### Autoridades

| Perfil        | Acesso                                                                 |
|---------------|------------------------------------------------------------------------|
| Administrador | Total — gerencia usuários, cadastros, relatórios e configurações       |
| Comandante    | Operacional completo — cadastros, abordagens, relatórios               |
| MP            | Ministério Público — visualização e relatórios                         |
| Guarnição     | Registro de abordagens e avistamentos em campo                         |
| Central       | Somente visualização e consultas                                       |

### Público geral

| Perfil     | Acesso                                                                  |
|------------|-------------------------------------------------------------------------|
| Cidadão    | Registra avistamentos, acompanha o feed e visualiza o mapa              |
| Voluntário | Registra avistamentos e pode marcar casos como atendidos                |
| ONG        | Organização parceira — visualiza, registra e atualiza status de casos   |

---

## Stack Tecnológica

- **Backend:** PHP (procedural, PDO)
- **Banco de dados:** PostgreSQL 13+
- **Frontend:** HTML, CSS, JavaScript, jQuery
- **UI:** Bootstrap 3 / Bootstrap 4
- **Servidor:** Apache (XAMPP / LAMP)

---

## Estrutura do Banco de Dados

### Tabelas principais

| Tabela       | Descrição                                        |
|--------------|--------------------------------------------------|
| `cadastro`   | Registro principal de cada morador               |
| `abordagem`  | Histórico de abordagens policiais                |
| `usuarios`   | Contas do sistema                                |
| `arquivos`   | Arquivos binários (fotos/documentos)             |

### Tabelas de referência

`cidade`, `estados`, `funcao`, `escolaridade`, `deficiencia`, `passagem`, `situacao_rua`, `intervalo`, `abordado_perfil`, `cor`

---

## Instalação

### Pré-requisitos

- PHP 7.4+ com extensão `pdo_pgsql` habilitada
- PostgreSQL 13+
- Apache com `mod_rewrite` habilitado (ex.: XAMPP)

### Passos

1. Clone o repositório na pasta do servidor web:
   ```
   git clone <url-do-repositorio> moradores-de-rua
   ```

2. Crie o banco de dados e aplique o schema:
   ```bash
   psql -U postgres -c "CREATE DATABASE moradores_de_rua;"
   psql -U postgres -d moradores_de_rua -f banco/schema.sql
   psql -U postgres -d moradores_de_rua -f banco/seeds.sql
   ```

3. Configure a conexão em [conexao.php](conexao.php):
   ```php
   define('PG_HOST',     'localhost');
   define('PG_PORT',     '5432');
   define('PG_USER',     'postgres');
   define('PG_PASSWORD', 'sua_senha');
   define('PG_DB_NAME',  'moradores_de_rua');
   ```

4. Acesse pelo navegador:
   ```
   http://localhost/moradores-de-rua/login.php
   ```

5. Login padrão (criado pelo seeds.sql):
   - **Usuário:** `admin`
   - **Senha:** `admin1234`

---

## Estrutura de Arquivos

### Páginas principais

| Arquivo                     | Função                                         |
|-----------------------------|------------------------------------------------|
| `login.php`                 | Autenticação (design system novo)              |
| `index.php`                 | Dashboard com listagem e busca de cadastros    |
| `avistamentos.php`          | Feed / Mapa / Registro rápido de avistamentos  |
| `ver_cadastro.php`          | Visualização completa do cadastro              |
| `cad_editar.php`            | Formulário de edição do cadastro               |
| `relatorio_idade.php`       | Relatório por faixa etária                     |
| `usuarios_cadastro.php`     | Gestão de usuários do sistema                  |

### Frontend & Design

| Caminho                  | Conteúdo                                               |
|--------------------------|--------------------------------------------------------|
| `estilos/css/app.css`    | Design system — tokens, componentes, utilitários       |
| `estilos/js/app.js`      | JS: tabs, chips, contador de pessoas, mapa Leaflet     |
| `includes/header.php`    | Navbar compartilhada (importada por todas as páginas)  |
| `includes/footer.php`    | Rodapé e scripts compartilhados                        |
| `Front/`                 | Wireframes React de baixa fidelidade (documentação UX) |

### Backend & Banco

| Caminho                              | Conteúdo                                 |
|--------------------------------------|------------------------------------------|
| `conexao.php`                        | Conexão PDO PostgreSQL                   |
| `banco/schema.sql`                   | DDL completo (PostgreSQL)                |
| `banco/seeds.sql`                    | Dados de referência iniciais             |
| `banco/migrate_avistamentos.sql`     | Migration para adicionar colunas novas   |
| `avistamentos_processa.php`          | Handler de registro e atualização de status |

---

## Avisos de Segurança

Este sistema foi desenvolvido para uso interno em rede controlada. Para uso em produção exposta, os seguintes pontos devem ser corrigidos antes do deploy:

- Substituir SHA1 por `password_hash()` / `password_verify()` para senhas
- Usar _prepared statements_ em todas as queries para prevenir SQL Injection
- Adicionar tokens CSRF nos formulários
- Mover credenciais do banco para variáveis de ambiente (`.env`)
- Reforçar validação no upload de arquivos

---

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).
