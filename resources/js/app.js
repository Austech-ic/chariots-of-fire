// Dependency-free progressive enhancement (the site works without JavaScript).
// Kept CSP-friendly: no inline handlers, no eval.

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    if (!toggle || !panel) return;
    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        panel.hidden = !open;
        document.body.classList.toggle('overflow-hidden', open);
        document.querySelector('[data-sticky-header]')?.classList.toggle('nav-open', open);
    };
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) { setOpen(false); toggle.focus(); } });

    const header = document.querySelector('[data-sticky-header]');
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 24);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Close desktop dropdowns when clicking elsewhere.
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details[data-dropdown][open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
}

function initSlideshow(root) {
    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-slide-dot]')];
    if (slides.length < 2) return;
    let index = 0;
    let timer = null;
    const delay = (i) => (parseInt(slides[i].dataset.duration, 10) || 7) * 1000;

    const show = (i) => {
        index = (i + slides.length) % slides.length;
        slides.forEach((s, n) => {
            const active = n === index;
            s.classList.toggle('opacity-100', active);
            s.classList.toggle('opacity-0', !active);
            s.classList.toggle('pointer-events-none', !active);
            s.setAttribute('aria-hidden', String(!active));
            s.querySelectorAll('a,button').forEach((el) => (el.tabIndex = active ? 0 : -1));
        });
        dots.forEach((d, n) => d.setAttribute('aria-current', n === index ? 'true' : 'false'));
    };
    const stop = () => { clearTimeout(timer); timer = null; };
    const play = () => { if (reduceMotion) return; stop(); timer = setTimeout(() => { show(index + 1); play(); }, delay(index)); };

    root.querySelector('[data-slide-prev]')?.addEventListener('click', () => { show(index - 1); play(); });
    root.querySelector('[data-slide-next]')?.addEventListener('click', () => { show(index + 1); play(); });
    dots.forEach((d, n) => d.addEventListener('click', () => { show(n); play(); }));
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', play);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', play);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : play()));
    show(0);
    play();
}

function initImageFallbacks() {
    document.querySelectorAll('img[data-fallback]').forEach((img) => {
        const fail = () => {
            const wrap = img.closest('[data-media]') || img.parentElement;
            img.remove();
            wrap?.classList.add('media-fallback');
        };
        if (img.complete && img.naturalWidth === 0) fail();
        else img.addEventListener('error', fail, { once: true });
    });
}

function initLightbox() {
    const items = [...document.querySelectorAll('[data-lightbox-item]')];
    const box = document.querySelector('[data-lightbox]');
    if (!items.length || !box) return;
    const img = box.querySelector('img');
    const caption = box.querySelector('[data-lightbox-caption]');
    let current = 0;
    let lastFocus = null;

    const open = (i) => {
        current = (i + items.length) % items.length;
        const a = items[current];
        img.src = a.getAttribute('href');
        img.alt = a.dataset.alt || '';
        caption.textContent = a.dataset.caption || '';
        if (box.hidden) { lastFocus = document.activeElement; box.hidden = false; document.body.classList.add('overflow-hidden'); box.querySelector('[data-lightbox-close]').focus(); }
    };
    const close = () => { box.hidden = true; img.src = ''; document.body.classList.remove('overflow-hidden'); lastFocus?.focus(); };

    items.forEach((a, i) => a.addEventListener('click', (e) => { e.preventDefault(); open(i); }));
    box.querySelector('[data-lightbox-close]').addEventListener('click', close);
    box.querySelector('[data-lightbox-prev]').addEventListener('click', () => open(current - 1));
    box.querySelector('[data-lightbox-next]').addEventListener('click', () => open(current + 1));
    box.addEventListener('click', (e) => { if (e.target === box) close(); });
    document.addEventListener('keydown', (e) => {
        if (box.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') open(current + 1);
        if (e.key === 'ArrowLeft') open(current - 1);
    });
}

function initDonationPresets() {
    const input = document.querySelector('[data-amount-input]');
    document.querySelectorAll('[data-amount-preset]').forEach((btn) => {
        btn.addEventListener('click', () => {
            input.value = btn.dataset.amountPreset;
            document.querySelectorAll('[data-amount-preset]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
            input.focus();
        });
    });
    const anon = document.querySelector('[data-anonymous-toggle]');
    const nameField = document.querySelector('[data-donor-name]');
    if (anon && nameField) {
        const sync = () => { nameField.hidden = anon.checked; };
        anon.addEventListener('change', sync);
        sync();
    }
}

function initShare() {
    document.querySelectorAll('[data-share]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.share;
            const title = btn.dataset.title || document.title;
            try {
                if (navigator.share) await navigator.share({ title, url });
                else { await navigator.clipboard.writeText(url); btn.querySelector('[data-share-label]').textContent = btn.dataset.copied || 'Link copied'; }
            } catch (_) { /* dismissed */ }
        });
    });
}

function initSubmitGuard() {
    // Prevent double submissions (bookings, donations).
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type=submit]').forEach((b) => { b.disabled = true; b.setAttribute('aria-busy', 'true'); });
        });
    });
}

function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach((el) => el.addEventListener('change', () => el.form?.submit()));
}

function initThemeToggle() {
    const root = document.documentElement;
    const apply = (mode) => {
        root.classList.toggle('dark', mode === 'dark');
        root.dataset.theme = mode;
        document.querySelector('meta[name="color-scheme"]')?.setAttribute('content', mode);
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', mode === 'dark' ? '#101116' : '#f7f4ee');
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `fco_theme=${mode}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
        document.querySelectorAll('[data-theme-toggle]').forEach((el) => {
            const label = mode === 'dark' ? el.dataset.labelDark : el.dataset.labelLight;
            el.setAttribute('aria-label', label);
            el.setAttribute('title', label);
            el.setAttribute('href', `/theme/${mode === 'dark' ? 'light' : 'dark'}`);
        });
    };
    document.querySelectorAll('[data-theme-toggle]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            apply(root.classList.contains('dark') ? 'light' : 'dark');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initThemeToggle();
    document.querySelectorAll('[data-slideshow]').forEach(initSlideshow);
    initImageFallbacks();
    initLightbox();
    initDonationPresets();
    initShare();
    initSubmitGuard();
    initAutoSubmit();
});
