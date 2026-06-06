<?php
session_start();

// Usuário já logado vai direto pro dashboard
if (isset($_SESSION['id_usuario'])) {
    header('Location: home.php');
    exit;
}

// Detecta mobile para redirecionar ao onboarding
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua);
if ($is_mobile && !isset($_GET['web'])) {
    header('Location: onboarding.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Onde Ajudar — Plataforma de cuidado coletivo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        /* ── Landing exclusivo ───────────────────────── */
        body { background: var(--paper); }

        /* Nav */
        .land-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; height: 64px;
            background: rgba(247,242,233,.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(58,52,44,.1);
        }
        .land-nav .logo {
            font-family: var(--hand); font-size: 1.4rem; color: var(--ink);
            display: flex; align-items: center; gap: 8px;
        }
        .land-nav .logo span { color: var(--accent); }
        .land-nav .nav-links { display: flex; gap: 8px; align-items: center; }

        /* Hero */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 100px 24px 60px;
            text-align: center;
            background: var(--paper);
            background-image:
                radial-gradient(ellipse 80% 60% at 50% 20%, rgba(207,106,68,.08) 0%, transparent 60%),
                radial-gradient(rgba(58,52,44,.05) 1px, transparent 1.1px);
            background-size: auto, 22px 22px;
        }
        .hero-inner { max-width: 720px; }
        .hero-kicker {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 14px; color: var(--accent); font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase;
            border: 1.5px solid var(--accent);
            border-radius: 20px; padding: 4px 14px;
            margin-bottom: 24px;
        }
        .hero h1 {
            font-family: var(--hand); font-size: clamp(2.4rem, 6vw, 4.2rem);
            font-weight: 400; line-height: 1.1; margin-bottom: 20px; color: var(--ink);
        }
        .hero h1 em {
            font-style: normal; color: var(--accent);
            text-decoration: underline; text-decoration-color: var(--accent);
            text-underline-offset: 4px; text-decoration-thickness: 3px;
        }
        .hero p {
            font-size: clamp(1rem, 2.5vw, 1.2rem); color: #5a534a;
            max-width: 540px; margin: 0 auto 36px; line-height: 1.6;
        }
        .hero-ctas { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        /* Stats bar */
        .stats-bar {
            background: var(--paper-2);
            border-top: 1.5px solid rgba(58,52,44,.1);
            border-bottom: 1.5px solid rgba(58,52,44,.1);
            padding: 20px 24px;
            display: flex; justify-content: center; gap: 48px; flex-wrap: wrap;
        }
        .stat { text-align: center; }
        .stat .num { font-family: var(--hand); font-size: 2rem; color: var(--accent); }
        .stat .lbl { font-size: 13px; color: var(--muted); }

        /* Features */
        .section { padding: 80px 24px; max-width: 1100px; margin: 0 auto; }
        .section-title {
            font-family: var(--hand); font-size: clamp(1.6rem, 4vw, 2.4rem);
            text-align: center; margin-bottom: 8px;
        }
        .section-sub { text-align: center; color: var(--muted); margin-bottom: 48px; font-size: 1rem; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .feat-card {
            background: var(--paper);
            border: 2px solid rgba(58,52,44,.12);
            border-radius: var(--radius-lg);
            padding: 28px 24px;
            transition: box-shadow .15s, transform .12s;
        }
        .feat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
        .feat-icon { font-size: 2.4rem; margin-bottom: 14px; }
        .feat-card h3 { font-family: var(--hand); font-size: 1.3rem; margin-bottom: 8px; }
        .feat-card p { font-size: 15px; color: #5a534a; line-height: 1.6; margin: 0; }

        /* Steps */
        .steps-section { background: var(--board); padding: 80px 24px; }
        .steps-inner { max-width: 860px; margin: 0 auto; }
        .steps { display: flex; gap: 0; counter-reset: step; }
        .step {
            flex: 1; text-align: center; padding: 0 20px; position: relative;
        }
        .step::after {
            content: '';
            position: absolute; top: 28px; right: -1px;
            width: 2px; height: 20px;
            background: rgba(58,52,44,.15);
        }
        .step:last-child::after { display: none; }
        .step-num {
            width: 56px; height: 56px;
            border-radius: 50%;
            border: 2.5px solid var(--line);
            background: var(--paper);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--hand); font-size: 1.6rem; color: var(--accent);
            margin: 0 auto 14px;
            box-shadow: 3px 3px 0 rgba(44,38,32,.12);
        }
        .step h3 { font-family: var(--hand); font-size: 1.1rem; margin-bottom: 6px; }
        .step p  { font-size: 14px; color: var(--muted); line-height: 1.5; margin: 0; }

        /* Who it's for */
        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .profile-card {
            background: var(--paper-2);
            border-radius: var(--radius);
            padding: 20px 18px;
            border: 1.5px solid rgba(58,52,44,.1);
            text-align: center;
        }
        .profile-card .emoji { font-size: 2rem; margin-bottom: 10px; }
        .profile-card h4 { font-family: var(--hand); font-size: 1.05rem; margin-bottom: 4px; }
        .profile-card p  { font-size: 13px; color: var(--muted); margin: 0; }

        /* CTA strip */
        .cta-strip {
            background: var(--ink); color: var(--paper);
            padding: 70px 24px; text-align: center;
        }
        .cta-strip h2 {
            font-family: var(--hand); font-size: clamp(1.6rem, 4vw, 2.6rem);
            color: var(--paper); margin-bottom: 10px;
        }
        .cta-strip p { color: rgba(247,242,233,.7); margin-bottom: 30px; font-size: 1.05rem; }
        .cta-strip .btn-primary {
            background: var(--accent); border-color: var(--accent);
            font-size: 1.1rem; padding: 14px 32px;
        }
        .cta-strip .btn-ghost {
            color: var(--paper); border-color: rgba(247,242,233,.4);
        }

        /* Footer */
        footer {
            background: var(--paper-2);
            border-top: 1.5px solid rgba(58,52,44,.1);
            padding: 28px 24px; text-align: center;
            color: var(--muted); font-size: 13px;
        }
        footer a { color: var(--accent); }

        @media (max-width: 640px) {
            .land-nav { padding: 0 16px; }
            .land-nav .nav-links .btn { display: none; }
            .land-nav .nav-links .btn:last-child { display: inline-flex; }
            .stats-bar { gap: 24px; }
            .steps { flex-direction: column; gap: 28px; }
            .step::after { display: none; }
        }
    </style>
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="land-nav">
    <div class="logo">🤝 <span>Onde</span> Ajudar</div>
    <div class="nav-links">
        <a href="#como-funciona" class="btn btn-ghost btn-sm">Como funciona</a>
        <a href="#quem-usa"      class="btn btn-ghost btn-sm">Para quem é</a>
        <a href="login.php"      class="btn btn-primary btn-sm">Entrar</a>
    </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-kicker">🤝 Plataforma de cuidado coletivo</div>
        <h1>Um lugar só para<br>quem quer <em>ajudar</em></h1>
        <p>Avistamentos de pessoas em situação de rua, desaparecidos, animais perdidos e restaurantes populares — tudo em um feed vivo da sua cidade, mobilizando quem pode agir.</p>
        <div class="hero-ctas">
            <a href="registro.php" class="btn btn-primary btn-lg">Criar minha conta →</a>
            <a href="login.php"    class="btn btn-ghost btn-lg">Entrar na plataforma</a>
        </div>
        <p style="margin-top:14px;font-size:13px;color:var(--muted)">
            Já tem conta? <a href="login.php">Entrar</a>
        </p>
    </div>
</section>

<!-- ── Stats ──────────────────────────────────────────────── -->
<div class="stats-bar">
    <div class="stat"><div class="num">4</div><div class="lbl">módulos ativos</div></div>
    <div class="stat"><div class="num">🗺</div><div class="lbl">mapa em tempo real</div></div>
    <div class="stat"><div class="num">🤝</div><div class="lbl">rede de cuidado</div></div>
    <div class="stat"><div class="num">❤</div><div class="lbl">cada vida importa</div></div>
</div>

<!-- ── Features ───────────────────────────────────────────── -->
<div class="section">
    <h2 class="section-title">Quatro módulos, uma só plataforma</h2>
    <p class="section-sub">Cada situação tem seu espaço. Registre, acompanhe e mobilize a comunidade.</p>
    <div class="features-grid">
        <div class="feat-card">
            <div class="feat-icon">🏠</div>
            <h3>População em Situação de Rua</h3>
            <p>Registre avistamentos com localização, veja o mapa em tempo real, acompanhe o histórico de cuidados e encaminhe para Centro POP, albergues e saúde.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🔍</div>
            <h3>Pessoas Desaparecidas</h3>
            <p>Cadastre ou busque pessoas desaparecidas, compartilhe o alerta na rede, registre avistamentos e marque quando a pessoa for encontrada.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🍽️</div>
            <h3>Restaurantes Populares</h3>
            <p>Encontre restaurantes com refeições a preço popular na sua cidade, veja cardápios, horários e localização no mapa — ideal para quem coordena distribuição de alimentos.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🐾</div>
            <h3>Animais Desaparecidos</h3>
            <p>Registre animais perdidos ou encontrados com foto e localização, divulgue na comunidade e ajude a reunir pets com suas famílias.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon">📊</div>
            <h3>Dados que orientam</h3>
            <p>Relatórios por módulo, faixa etária, frequência de casos e encaminhamentos realizados. Informação para políticas de cuidado.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🔒</div>
            <h3>Privacidade e ética</h3>
            <p>Dados sensíveis protegidos. Acesso por perfil de usuário. Cada pessoa e animal é tratado com dignidade, não como número.</p>
        </div>
    </div>
</div>

<!-- ── How it works ───────────────────────────────────────── -->
<div class="steps-section" id="como-funciona">
    <div class="steps-inner">
        <h2 class="section-title">Como funciona</h2>
        <p class="section-sub" style="margin-bottom:48px">Três passos. Qualquer pessoa pode ajudar, em qualquer módulo.</p>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Você identifica</h3>
                <p>Viu alguém precisando de ajuda, um animal perdido, um desaparecido ou um restaurante popular? Acesse o módulo certo.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Você registra</h3>
                <p>Preencha as informações básicas — localização, foto, descrição. Em poucos toques o registro está no feed da comunidade.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>A rede age</h3>
                <p>Voluntários, ONGs, assistência social e outros usuários veem o caso e se mobilizam para ajudar.</p>
            </div>
        </div>
    </div>
</div>

<!-- ── Who it's for ───────────────────────────────────────── -->
<div class="section" id="quem-usa">
    <h2 class="section-title">Para quem é</h2>
    <p class="section-sub">Qualquer pessoa que queira fazer a diferença — em qualquer causa</p>
    <div class="profiles-grid">
        <div class="profile-card">
            <div class="emoji">🚶</div>
            <h4>Cidadão</h4>
            <p>Viu algo, registrou. Simples assim. Seja um avistamento, um animal perdido ou um desaparecido.</p>
        </div>
        <div class="profile-card">
            <div class="emoji">🤝</div>
            <h4>Voluntário</h4>
            <p>Acompanha o feed da sua cidade, vai até o local e marca o caso como atendido.</p>
        </div>
        <div class="profile-card">
            <div class="emoji">🏢</div>
            <h4>ONG / Organização</h4>
            <p>Coordena equipes, visualiza o mapa por módulo e registra encaminhamentos com histórico completo.</p>
        </div>
        <div class="profile-card">
            <div class="emoji">🏥</div>
            <h4>Assistência Social</h4>
            <p>Acessa histórico de cada pessoa, gera relatórios e acompanha casos entre serviços.</p>
        </div>
        <div class="profile-card">
            <div class="emoji">🛡</div>
            <h4>Autoridades</h4>
            <p>Visão geral da cidade por módulo — dados para políticas públicas e distribuição de recursos.</p>
        </div>
        <div class="profile-card">
            <div class="emoji">❤</div>
            <h4>Você</h4>
            <p>Qualquer pessoa com vontade de ajudar, seja qual for a causa, é bem-vinda aqui.</p>
        </div>
    </div>
</div>

<!-- ── CTA final ──────────────────────────────────────────── -->
<div class="cta-strip">
    <h2>Cada vida conta.<br>Comece agora.</h2>
    <p>Crie sua conta e acesse todos os módulos da plataforma gratuitamente.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
        <a href="registro.php" class="btn btn-primary btn-lg">Criar minha conta</a>
        <a href="login.php"    class="btn btn-ghost    btn-lg">Entrar na plataforma</a>
    </div>
    <p style="margin-top:14px;font-size:14px;color:rgba(247,242,233,.6)">
        Já tem conta? <a href="login.php" style="color:rgba(247,242,233,.85)">Entrar</a>
    </p>
</div>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer>
    <p style="margin-bottom:6px">
        🤝 <strong>Onde Ajudar</strong> — Plataforma de cuidado coletivo
    </p>
    <p>
        <a href="landing.php?web=1">Versão web</a> ·
        <a href="onboarding.php">Ver no celular</a> ·
        <a href="login.php">Entrar</a>
    </p>
</footer>

<script src="estilos/js/app.js"></script>
</body>
</html>
