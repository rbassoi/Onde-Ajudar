<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Onde Ajudar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:    #2c2620;
            --paper:  #f7f2e9;
            --board:  #e9e1d2;
            --muted:  #8d8579;
            --accent: #cf6a44;
            --accent-dark: #a0512f;
            --hand:   'Patrick Hand', cursive;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%; overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Slider ─────────────────────────────────── */
        .slider {
            height: 100dvh;
            display: flex;
            overflow: hidden;
            touch-action: pan-y pinch-zoom;
        }
        .slides-track {
            display: flex;
            width: 300%;
            height: 100%;
            transition: transform .38s cubic-bezier(.4,0,.2,1);
            will-change: transform;
        }
        .slide {
            width: calc(100% / 3);
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: env(safe-area-inset-top, 20px) 32px
                     calc(env(safe-area-inset-bottom, 0px) + 24px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* slide backgrounds */
        .slide-1 { background: var(--paper); }
        .slide-2 { background: #f0ece3; }
        .slide-3 { background: var(--board); }

        /* ── Illustration area ───────────────────────── */
        .illus {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 24px 0 12px;
        }
        .illus-inner {
            width: min(280px, 80vw);
            aspect-ratio: 1;
            position: relative;
        }

        /* Slide 1 — mapa */
        .s1-map {
            width: 100%; height: 100%;
            border: 2.5px solid rgba(58,52,44,.2);
            border-radius: 24px;
            background: #ede8de;
            background-image:
                repeating-linear-gradient(0deg, transparent 0 33px, rgba(58,52,44,.08) 33px 34px),
                repeating-linear-gradient(90deg, transparent 0 33px, rgba(58,52,44,.08) 33px 34px);
            position: relative;
            overflow: hidden;
        }
        .s1-road-v { position:absolute; background: rgba(58,52,44,.14); top:0; bottom:0; }
        .s1-road-h { position:absolute; background: rgba(58,52,44,.14); left:0; right:0; }
        .s1-pin {
            position: absolute;
            width: 28px; height: 28px;
            background: var(--accent);
            border: 2.5px solid rgba(58,52,44,.6);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 3px 3px 0 rgba(44,38,32,.2);
        }
        .s1-pin::after {
            content: '';
            position: absolute; inset: 0; margin: auto;
            width: 10px; height: 10px;
            background: #fff7ef;
            border-radius: 50%;
            transform: rotate(45deg);
        }
        .s1-pin.ghost { background: #d1ccc3; }
        .s1-fab {
            position: absolute; right: 16px; bottom: 16px;
            background: var(--accent); color: #fff7ef;
            border: 2.5px solid rgba(58,52,44,.5);
            border-radius: 14px; padding: 10px 16px;
            font-family: var(--hand); font-size: 1rem;
            box-shadow: 3px 3px 0 rgba(44,38,32,.2);
        }

        /* Slide 2 — form steps */
        .s2-phone {
            width: 70%; aspect-ratio: 9/16;
            border: 2.5px solid rgba(58,52,44,.2);
            border-radius: 28px;
            background: var(--paper);
            padding: 16px 14px;
            box-shadow: 6px 8px 0 rgba(44,38,32,.1);
            display: flex; flex-direction: column; gap: 8px;
        }
        .s2-topbar {
            display: flex; justify-content: space-between;
            font-family: var(--hand); font-size: .9rem; color: var(--muted);
        }
        .s2-field {
            border: 2px solid rgba(58,52,44,.15);
            border-radius: 10px; padding: 8px 10px;
            font-size: .8rem; color: #5a534a;
        }
        .s2-field .lbl { font-size: .7rem; color: var(--muted); margin-bottom: 2px; }
        .s2-chips { display: flex; flex-wrap: wrap; gap: 5px; }
        .s2-chip {
            font-size: .72rem; border: 1.5px solid rgba(58,52,44,.2);
            border-radius: 12px; padding: 3px 8px; background: var(--paper);
        }
        .s2-chip.on { background: rgba(207,106,68,.15); border-color: var(--accent); }
        .s2-btn {
            margin-top: auto;
            background: var(--accent); color: #fff7ef;
            border: 2px solid rgba(58,52,44,.4);
            border-radius: 10px; padding: 10px;
            font-family: var(--hand); font-size: .95rem; text-align: center;
            box-shadow: 2px 2px 0 rgba(44,38,32,.15);
        }
        .s2-steps { display: flex; gap: 6px; justify-content: center; margin-top: 4px; }
        .s2-steps i {
            width: 8px; height: 8px;
            border-radius: 50%; background: rgba(58,52,44,.2);
        }
        .s2-steps i.on { background: var(--accent); }

        /* Slide 3 — feed cards */
        .s3-feed { display: flex; flex-direction: column; gap: 10px; width: 100%; }
        .s3-card {
            background: var(--paper);
            border: 2px solid rgba(58,52,44,.15);
            border-radius: 14px; padding: 12px 14px;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .s3-card .loc  { font-family: var(--hand); font-size: 1rem; }
        .s3-card .meta { font-size: .75rem; color: var(--muted); margin-top: 2px; }
        .s3-badge {
            font-size: .72rem; border-radius: 10px; padding: 2px 8px;
            border: 1.5px solid; white-space: nowrap; margin-left: 8px;
        }
        .s3-badge.urg  { background: var(--accent); color: #fff7ef; border-color: rgba(58,52,44,.3); }
        .s3-badge.ok   { background: #d9e7d2; color: #3d6b4a; border-color: #3d6b4a; }
        .s3-badge.wait { background: var(--board); color: var(--muted); border-color: rgba(58,52,44,.2); }
        .s3-heart {
            font-size: 4rem; line-height: 1;
            filter: drop-shadow(0 4px 12px rgba(207,106,68,.25));
        }

        /* ── Text content ────────────────────────────── */
        .slide-text { padding: 0 4px 8px; }
        .slide-text h2 {
            font-family: var(--hand); font-size: clamp(1.6rem, 5vw, 2rem);
            margin-bottom: 10px; line-height: 1.2;
        }
        .slide-text h2 em {
            font-style: normal; color: var(--accent);
        }
        .slide-text p {
            font-size: .95rem; color: #5a534a; line-height: 1.55;
        }

        /* ── Bottom controls ─────────────────────────── */
        .controls {
            width: 100%;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px;
        }
        .dots {
            display: flex; gap: 8px; align-items: center;
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 4px;
            background: rgba(58,52,44,.2);
            transition: width .25s, background .2s;
        }
        .dot.active { width: 24px; background: var(--accent); }

        .btn-skip {
            background: transparent; border: none;
            font-size: .9rem; color: var(--muted);
            cursor: pointer; padding: 10px 0;
            font-family: 'Inter', sans-serif;
        }
        .btn-next, .btn-start {
            background: var(--accent); color: #fff7ef;
            border: 2px solid rgba(58,52,44,.3);
            border-radius: 14px; padding: 12px 24px;
            font-family: var(--hand); font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 3px 3px 0 rgba(44,38,32,.15);
            transition: background .15s, transform .1s;
            white-space: nowrap;
        }
        .btn-next:active, .btn-start:active { transform: translateY(1px); }
        .btn-start { display: none; }
        .btn-start.show, .btn-next.hide { display: none; }
        .btn-start.show { display: block; }
    </style>
</head>
<body>

<div class="slider" id="slider">
    <div class="slides-track" id="track">

        <!-- ── Slide 1: Mapa ──────────────────────── -->
        <div class="slide slide-1">
            <div class="illus">
                <div class="illus-inner">
                    <div class="s1-map">
                        <div class="s1-road-v" style="left:28%;width:4px"></div>
                        <div class="s1-road-v" style="left:66%;width:5px"></div>
                        <div class="s1-road-h" style="top:40%;height:4px"></div>
                        <div class="s1-road-h" style="top:70%;height:3px"></div>
                        <div class="s1-pin" style="left:22%;top:25%"></div>
                        <div class="s1-pin" style="left:54%;top:18%"></div>
                        <div class="s1-pin" style="left:42%;top:52%"></div>
                        <div class="s1-pin ghost" style="left:68%;top:62%"></div>
                        <div class="s1-pin ghost" style="left:16%;top:68%"></div>
                        <div class="s1-fab">＋ Registrar</div>
                    </div>
                </div>
            </div>
            <div class="slide-text">
                <h2>Veja onde<br><em>a ajuda é</em> necessária</h2>
                <p>Mapa em tempo real com avistamentos da sua cidade. Pinos laranja: casos abertos. Pinos claros: já atendidos.</p>
            </div>
            <div class="controls">
                <button class="btn-skip" onclick="goTo(2)">Pular</button>
                <div class="dots">
                    <div class="dot active"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
                <button class="btn-next" onclick="goTo(1)">Próximo →</button>
            </div>
        </div>

        <!-- ── Slide 2: Registrar ─────────────────── -->
        <div class="slide slide-2">
            <div class="illus">
                <div class="illus-inner" style="display:flex;align-items:center;justify-content:center">
                    <div class="s2-phone">
                        <div class="s2-topbar">
                            <span>Novo avistamento</span>
                        </div>
                        <div class="s2-field">
                            <div class="lbl">local (GPS)</div>
                            R. das Flores, 120 — Centro
                        </div>
                        <div class="s2-field">
                            <div class="lbl">pessoas no local</div>
                            <strong>2</strong> &nbsp; ⊝ ⊕
                        </div>
                        <div class="s2-chips">
                            <div class="s2-chip on">frio</div>
                            <div class="s2-chip">comida</div>
                            <div class="s2-chip on">saúde</div>
                            <div class="s2-chip">água</div>
                            <div class="s2-chip">criança</div>
                        </div>
                        <div class="s2-btn">Enviar avistamento →</div>
                        <div class="s2-steps">
                            <i class="on"></i><i class="on"></i><i></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slide-text">
                <h2><em>3 toques</em><br>e está no feed</h2>
                <p>Local, quantas pessoas, o que precisam. Simples. Qualquer pessoa pode registrar — sem burocracia.</p>
            </div>
            <div class="controls">
                <button class="btn-skip" onclick="goTo(2)">Pular</button>
                <div class="dots">
                    <div class="dot"></div>
                    <div class="dot active"></div>
                    <div class="dot"></div>
                </div>
                <button class="btn-next" onclick="goTo(2)">Próximo →</button>
            </div>
        </div>

        <!-- ── Slide 3: Feed / Rede ───────────────── -->
        <div class="slide slide-3">
            <div class="illus">
                <div class="illus-inner" style="display:flex;flex-direction:column;gap:0;justify-content:center">
                    <div style="text-align:center;margin-bottom:16px">
                        <div class="s3-heart">🤝</div>
                    </div>
                    <div class="s3-feed">
                        <div class="s3-card">
                            <div>
                                <div class="loc">Centro · Praça</div>
                                <div class="meta">3 pessoas · há 12 min</div>
                            </div>
                            <span class="s3-badge urg">urgente</span>
                        </div>
                        <div class="s3-card">
                            <div>
                                <div class="loc">Bela Vista</div>
                                <div class="meta">1 pessoa · 2 querem ajudar</div>
                            </div>
                            <span class="s3-badge wait">pendente</span>
                        </div>
                        <div class="s3-card">
                            <div>
                                <div class="loc">Liberdade</div>
                                <div class="meta">ONG Acolher passou hoje</div>
                            </div>
                            <span class="s3-badge ok">✓ atendido</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slide-text">
                <h2>A <em>rede</em> se<br>mobiliza por você</h2>
                <p>Voluntários, ONGs e assistência social acompanham o feed e agem. Cada pessoa importa. Juntos chegamos mais longe.</p>
            </div>
            <div class="controls">
                <button class="btn-skip" style="visibility:hidden">Pular</button>
                <div class="dots">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot active"></div>
                </div>
                <a href="login.php" class="btn-start show">Começar ❤</a>
            </div>
        </div>

    </div>
</div>

<script>
let current = 0;
const track = document.getElementById('track');

function goTo(i) {
    current = i;
    track.style.transform = `translateX(-${i * 100/3}%)`;
}

// Touch/swipe
let startX = 0, dragging = false;
const slider = document.getElementById('slider');

slider.addEventListener('touchstart', e => {
    startX  = e.touches[0].clientX;
    dragging = true;
}, { passive: true });

slider.addEventListener('touchend', e => {
    if (!dragging) return;
    dragging = false;
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) < 40) return;
    if (dx < 0 && current < 2) goTo(current + 1);
    if (dx > 0 && current > 0) goTo(current - 1);
}, { passive: true });

// Keyboard
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight' && current < 2) goTo(current + 1);
    if (e.key === 'ArrowLeft'  && current > 0) goTo(current - 1);
});
</script>
</body>
</html>
