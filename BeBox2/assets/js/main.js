// assets/js/main.js — BeBox2 Shared JavaScript

// ─── Burger Menu Close on Outside Click ──────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const toggle = document.getElementById('menu-toggle');
        const nav    = document.querySelector('.nav-container');
        if (toggle && toggle.checked && nav && !nav.contains(e.target)) {
            toggle.checked = false;
        }
    });

    // ─── Copy Promo Code on Click ─────────────────────────
    document.querySelectorAll('.promo-code[data-code]').forEach(function (el) {
        el.addEventListener('click', function () {
            const code = el.getAttribute('data-code');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function () {
                    const original = el.textContent;
                    el.textContent = '✅ Disalin!';
                    setTimeout(function () { el.textContent = original; }, 1500);
                });
            }
        });
    });

    // ─── Auto-dismiss Flash Messages ─────────────────────
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.5s ease';
            flash.style.opacity = '0';
            setTimeout(function () { flash.remove(); }, 500);
        }, 4000);
    }

    // ─── Confirm Delete ───────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Yakin ingin menghapus?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ─── Animate page in ─────────────────────────────────
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s ease';
    requestAnimationFrame(function () {
        document.body.style.opacity = '1';
    });
});
