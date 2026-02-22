<?php
// hello-animation.php — Apple-style Hello screen before dashboard
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

$username = sanitize($_SESSION['username'] ?? 'there');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeBox — Welcome</title>
    <!-- Dancing Script for Apple-style cursive "hello" -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Inter:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
        height: 100%;
        overflow: hidden;
        background: #000;
        font-family: 'Inter', -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    /* Background particles */
    .particles { position: fixed; inset: 0; pointer-events: none; }
    .particle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
        animation: float linear infinite;
    }

    /* Center stage */
    .scene {
        position: fixed;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0;
    }

    /* Canvas container — perfectly centered */
    #helloCanvas {
        display: block;
        /* sized per JS based on device */
    }

    /* Username line */
    .greeting-line {
        font-family: 'Inter', sans-serif;
        font-size: clamp(16px, 3.5vw, 26px);
        font-weight: 300;
        color: rgba(255,255,255,0.55);
        letter-spacing: 0.3px;
        margin-top: 18px;
        opacity: 0;
        transform: translateY(8px);
        animation: fadeUp 0.6s ease 1.0s forwards;
    }
    .greeting-line strong {
        color: rgba(255,255,255,0.85);
        font-weight: 500;
    }

    /* Top logo */
    .logo-mark {
        position: fixed;
        top: 36px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255,255,255,0.22);
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.3px;
        opacity: 0;
        animation: fadeUpFixed 0.6s ease 1.7s forwards;
        white-space: nowrap;
    }

    /* Progress bar */
    .progress-wrap {
        position: fixed;
        bottom: 52px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        opacity: 0;
        animation: fadeUpFixed 0.5s ease 1.3s forwards;
    }
    .progress-bar {
        width: 160px;
        height: 2px;
        background: rgba(255,255,255,0.1);
        border-radius: 2px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        width: 0%;
        background: rgba(255,255,255,0.6);
        border-radius: 2px;
        animation: progressGo 2.2s ease 1.5s forwards;
    }
    .progress-label {
        color: rgba(255,255,255,0.28);
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 1.2px;
        text-transform: uppercase;
    }

    /* Keyframes */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeUpFixed {
        from { opacity: 0; transform: translateX(-50%) translateY(8px); }
        to   { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
    @keyframes progressGo { to { width: 100%; } }
    @keyframes float {
        from { transform: translateY(100vh) scale(0); opacity: 0; }
        10%  { opacity: 0.8; }
        90%  { opacity: 0.8; }
        to   { transform: translateY(-80px) scale(1); opacity: 0; }
    }
    @keyframes sceneOut { to { opacity: 0; transform: scale(1.04); } }
    .scene.out { animation: sceneOut 0.5s ease forwards; }
    </style>
</head>
<body>

<!-- Particles -->
<div class="particles" id="particles"></div>

<!-- Top logo -->
<div class="logo-mark">
    <i style="font-family:'Font Awesome 6 Free';font-weight:900;font-style:normal;">&#xf466;</i>
    BeBox
</div>

<!-- Main content -->
<div class="scene" id="scene">
    <canvas id="helloCanvas"></canvas>
    <div class="greeting-line">Welcome back, <strong><?= $username ?></strong>.</div>
</div>

<!-- Progress -->
<div class="progress-wrap">
    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>
    <span class="progress-label">Loading store…</span>
</div>

<script>
// ── Particles ──────────────────────────────────────────────
(function() {
    const c = document.getElementById('particles');
    for (let i = 0; i < 18; i++) {
        const el = document.createElement('div');
        el.className = 'particle';
        const s = Math.random() * 3 + 1;
        el.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}%;animation-duration:${Math.random()*9+7}s;animation-delay:${Math.random()*5}s;`;
        c.appendChild(el);
    }
})();

// ═══════════════════════════════════════════════════════════
//  HELLO ANIMATION ENGINE
//  Technique: Canvas "writing" simulation
//  - Renders the full text to an offscreen canvas
//  - Samples pixel-by-pixel column to find where ink is
//  - Progressively reveals columns via a moving clip stripe
//  - Adds a glowing pen-nib dot at the leading edge
//  Works for ANY font (Dancing Script, serif, etc.)
// ═══════════════════════════════════════════════════════════

const canvas  = document.getElementById('helloCanvas');
const ctx     = canvas.getContext('2d');

// Language sequence
const LANGUAGES = [
    { text: 'hello',   font: "bold 130px 'Dancing Script', cursive", writeDur: 2200 },
    { text: '你好',    font: 'bold 120px serif',                       writeDur: 1800 },
    { text: '今日は',  font: 'bold 110px serif',                       writeDur: 1800 },
    { text: '안녕하세요', font: 'bold 72px serif',                    writeDur: 2000 },
];

const CW = 640, CH = 180;
canvas.width  = CW;
canvas.height = CH;

const HOLD_MS = 750;
const FADE_STEPS = 30;

let langIdx = 0;
let globalAlpha = 1.0;
let fadeFrameId = null;

// ── Off-screen canvas to pre-render full text ──────────────
const offCanvas = document.createElement('canvas');
offCanvas.width  = CW;
offCanvas.height = CH;
const offCtx = offCanvas.getContext('2d');

function prepareLang(lang) {
    offCtx.clearRect(0, 0, CW, CH);
    offCtx.font          = lang.font;
    offCtx.textAlign     = 'center';
    offCtx.textBaseline  = 'middle';
    offCtx.fillStyle     = '#ffffff';
    offCtx.fillText(lang.text, CW / 2, CH / 2);

    // Get pixel data to find leftmost and rightmost ink columns
    const data = offCtx.getImageData(0, 0, CW, CH).data;
    let minX = CW, maxX = 0;
    for (let y = 0; y < CH; y++) {
        for (let x = 0; x < CW; x++) {
            const a = data[(y * CW + x) * 4 + 3];
            if (a > 20) {
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
            }
        }
    }
    return { minX: Math.max(minX - 4, 0), maxX: Math.min(maxX + 4, CW) };
}

// ── Drawing animation ──────────────────────────────────────
function animateLang(lang, bounds, onDone) {
    const { minX, maxX } = bounds;
    const totalW = maxX - minX;
    const dur    = lang.writeDur;
    let startTs  = null;
    let animFrame = null;

    function frame(ts) {
        if (!startTs) startTs = ts;
        const pct = Math.min((ts - startTs) / dur, 1);
        const revealTo = minX + totalW * easeInOut(pct); // current pen X

        ctx.clearRect(0, 0, CW, CH);

        // ── Draw revealed portion of text ──
        ctx.save();
        ctx.beginPath();
        ctx.rect(0, 0, revealTo + 3, CH);
        ctx.clip();

        // Glow layer
        ctx.shadowColor = 'rgba(255,255,255,0.55)';
        ctx.shadowBlur  = 18;
        ctx.drawImage(offCanvas, 0, 0);
        ctx.shadowBlur  = 0;
        ctx.restore();

        // ── Pen nib glow at leading edge ──
        if (pct < 1) {
            // Find the vertical center of ink at revealTo
            const nibX  = revealTo;
            const nibY  = CH / 2;
            const radius = 22;

            // Strong center glow
            const grd = ctx.createRadialGradient(nibX, nibY, 0, nibX, nibY, radius);
            grd.addColorStop(0,   'rgba(255,255,255,0.95)');
            grd.addColorStop(0.25,'rgba(255,255,255,0.55)');
            grd.addColorStop(0.6, 'rgba(255,255,255,0.18)');
            grd.addColorStop(1,   'rgba(255,255,255,0)');
            ctx.beginPath();
            ctx.arc(nibX, nibY, radius, 0, Math.PI * 2);
            ctx.fillStyle = grd;
            ctx.fill();
        }

        if (pct < 1) {
            animFrame = requestAnimationFrame(frame);
        } else {
            setTimeout(function() { fadeOut(onDone); }, HOLD_MS);
        }
    }
    animFrame = requestAnimationFrame(frame);
}

function easeInOut(t) {
    return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}

// ── Fade out current canvas content ───────────────────────
function fadeOut(onDone) {
    let step = 0;
    function tick() {
        step++;
        const alpha = 1 - step / FADE_STEPS;
        ctx.clearRect(0, 0, CW, CH);
        ctx.globalAlpha = Math.max(alpha, 0);
        ctx.drawImage(offCanvas, 0, 0);

        // also fade the nib — just clear everything
        if (alpha <= 0) {
            ctx.globalAlpha = 1;
            ctx.clearRect(0, 0, CW, CH);
            onDone();
        } else {
            requestAnimationFrame(tick);
        }
    }
    requestAnimationFrame(tick);
}

// ── Main sequence ──────────────────────────────────────────
function nextLang() {
    if (langIdx >= LANGUAGES.length) {
        // All done → redirect
        setTimeout(function() {
            document.getElementById('scene').classList.add('out');
            setTimeout(function() {
                window.location.href = '<?= BASE_URL ?>/dashboard.php';
            }, 500);
        }, 200);
        return;
    }

    const lang   = LANGUAGES[langIdx++];
    const bounds = prepareLang(lang);
    animateLang(lang, bounds, nextLang);
}

// Wait for Dancing Script to load then start
document.fonts.load("bold 130px 'Dancing Script'").then(function() {
    setTimeout(nextLang, 400);
}).catch(function() {
    setTimeout(nextLang, 900);
});
</script>
</body>
</html>
