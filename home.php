<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

$page_title = 'Início — Onde Ajudar';
require_once('includes/header.php');
?>

<style>
.home-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}
.home-greeting {
    text-align: center;
    margin-bottom: 40px;
}
.home-greeting h1 {
    font-family: var(--hand);
    font-size: clamp(1.8rem, 5vw, 2.8rem);
    margin-bottom: 8px;
}
.home-greeting p {
    color: var(--muted);
    font-size: 1.05rem;
    margin: 0;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 18px;
}

.cat-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 32px 20px;
    border-radius: var(--radius-lg);
    border: 2.5px solid transparent;
    text-decoration: none;
    font-family: var(--hand);
    font-size: 1.15rem;
    font-weight: 400;
    text-align: center;
    cursor: pointer;
    transition: transform .13s, box-shadow .13s, background .13s;
    position: relative;
    min-height: 160px;
}
.cat-btn .cat-icon {
    font-size: 2.8rem;
    line-height: 1;
}
.cat-btn .cat-label {
    line-height: 1.3;
}
.cat-btn:hover {
    text-decoration: none;
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
.cat-btn:active {
    transform: translateY(-1px);
}

/* Active category */
.cat-btn.cat-active {
    background: var(--accent);
    border-color: var(--accent-dark);
    color: #fff;
    box-shadow: 0 4px 18px rgba(207,106,68,.35);
}
.cat-btn.cat-active:hover {
    background: var(--accent-dark);
}

/* Soon category */
.cat-btn.cat-soon {
    background: var(--paper);
    border-color: var(--line);
    color: var(--muted);
    cursor: default;
    pointer-events: none;
    opacity: .65;
}

.cat-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--warn-soft);
    color: var(--warn);
    font-family: var(--body);
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    letter-spacing: .04em;
}
.cat-btn.cat-active .cat-badge {
    background: rgba(255,255,255,.25);
    color: #fff;
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .cat-btn {
        padding: 24px 12px;
        min-height: 130px;
        font-size: 1rem;
        gap: 10px;
    }
    .cat-btn .cat-icon { font-size: 2.2rem; }
}
</style>

<div class="home-wrap">
    <div class="home-greeting">
        <h1>O que você quer registrar?</h1>
        <p>Escolha uma categoria para começar</p>
    </div>

    <div class="categories-grid">

        <a href="avistamentos.php#registrar" class="cat-btn cat-active">
            <span class="cat-icon">🏠</span>
            <span class="cat-label">População em Situação de Rua</span>
        </a>

        <a href="desaparecidos.php" class="cat-btn cat-active">
            <span class="cat-icon">🔍</span>
            <span class="cat-label">Pessoas Desaparecidas</span>
        </a>

        <span class="cat-btn cat-soon">
            <span class="cat-badge">Em breve</span>
            <span class="cat-icon">🍽️</span>
            <span class="cat-label">Restaurantes Populares</span>
        </span>

        <span class="cat-btn cat-soon">
            <span class="cat-badge">Em breve</span>
            <span class="cat-icon">📦</span>
            <span class="cat-label">Acumuladores</span>
        </span>

        <span class="cat-btn cat-soon">
            <span class="cat-badge">Em breve</span>
            <span class="cat-icon">🐾</span>
            <span class="cat-label">Animais Perdidos</span>
        </span>

        <span class="cat-btn cat-soon">
            <span class="cat-badge">Em breve</span>
            <span class="cat-icon">🎒</span>
            <span class="cat-label">Achados e Perdidos</span>
        </span>

    </div>
</div>

<?php require_once('includes/footer.php'); ?>
