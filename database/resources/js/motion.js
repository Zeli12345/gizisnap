import { formatNumber } from './http';

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Angka menghitung naik dari 0 sampai nilainya. Teks akhir sudah ada di HTML,
 * jadi tanpa JavaScript angka tetap benar.
 */
function countTo(el) {
    if (el.dataset.counted) return;
    el.dataset.counted = '1';

    const target = parseFloat(el.dataset.count);
    const decimals = parseInt(el.dataset.decimals ?? '0', 10);
    if (reduced || !Number.isFinite(target) || target === 0) return;

    const duration = parseInt(el.dataset.duration ?? '1400', 10);
    const delay = parseInt(el.dataset.delay ?? '0', 10);
    const start = performance.now() + delay;
    const ease = (t) => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t));

    el.textContent = formatNumber(0, decimals);

    const tick = (now) => {
        const t = Math.max(0, Math.min(1, (now - start) / duration));
        el.textContent = formatNumber(target * ease(t), decimals);
        if (t < 1) requestAnimationFrame(tick);
        else el.textContent = formatNumber(target, decimals);
    };
    requestAnimationFrame(tick);
}

/**
 * Elemen muncul halus saat masuk layar. Yang sudah terlihat saat halaman
 * dibuka muncul langsung; sisanya menunggu digulir.
 */
export function initMotion() {
    const reveals = document.querySelectorAll('[data-reveal]');
    const counters = document.querySelectorAll('[data-count]');

    if (!('IntersectionObserver' in window)) {
        reveals.forEach((el) => el.classList.add('is-in'));
        counters.forEach(countTo);
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                const el = entry.target;
                observer.unobserve(el);

                if (el.hasAttribute('data-reveal')) {
                    el.classList.add('is-in');
                    el.querySelectorAll('[data-count]').forEach(countTo);
                }
                if (el.hasAttribute('data-count')) countTo(el);
            }
        },
        { rootMargin: '0px 0px -6% 0px', threshold: 0.08 },
    );

    reveals.forEach((el) => observer.observe(el));
    counters.forEach((el) => observer.observe(el));
}
