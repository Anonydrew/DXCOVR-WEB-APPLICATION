/**
 * DXCOVR public site behaviour.
 *
 * Every feature is a progressive enhancement: navigation links, filters and
 * forms all work without JavaScript. Nothing here uses eval, so the public
 * site runs under a strict nonce-based Content Security Policy.
 */

import { initQuestionnaire } from './onboarding';

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function safeStorage(action, key, value) {
    try {
        if (action === 'get') {
            return window.localStorage.getItem(key);
        }
        window.localStorage.setItem(key, value);
    } catch {
        return null;
    }
    return null;
}

/* Header ---------------------------------------------------------------- */

function initHeader() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const update = () => header.setAttribute('data-scrolled', window.scrollY > 8 ? 'true' : 'false');
    update();
    window.addEventListener('scroll', update, { passive: true });
}

/* Desktop dropdown navigation (disclosure pattern) ---------------------- */

function initDropdowns() {
    const toggles = document.querySelectorAll('[data-nav-toggle]');
    let closeTimer;

    const close = (toggle) => {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        toggle.setAttribute('aria-expanded', 'false');
        panel?.setAttribute('data-open', 'false');
    };

    const closeAll = (except) => toggles.forEach((toggle) => toggle !== except && close(toggle));

    const open = (toggle) => {
        closeAll(toggle);
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        toggle.setAttribute('aria-expanded', 'true');
        panel?.setAttribute('data-open', 'true');
    };

    toggles.forEach((toggle) => {
        const wrapper = toggle.closest('[data-nav-item]');
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));

        toggle.addEventListener('click', () => {
            toggle.getAttribute('aria-expanded') === 'true' ? close(toggle) : open(toggle);
        });

        if (window.matchMedia('(hover: hover)').matches && wrapper) {
            wrapper.addEventListener('mouseenter', () => {
                clearTimeout(closeTimer);
                open(toggle);
            });
            wrapper.addEventListener('mouseleave', () => {
                closeTimer = setTimeout(() => close(toggle), 160);
            });
        }

        wrapper?.addEventListener('focusout', (event) => {
            if (!wrapper.contains(event.relatedTarget)) close(toggle);
        });

        panel?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close(toggle);
                toggle.focus();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        toggles.forEach((toggle) => {
            if (toggle.getAttribute('aria-expanded') === 'true') {
                close(toggle);
                toggle.focus();
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-nav-item]')) closeAll();
    });
}

/* Mobile drawer --------------------------------------------------------- */

function initDrawer() {
    const drawer = document.querySelector('[data-drawer]');
    const openButton = document.querySelector('[data-drawer-open]');
    if (!drawer || !openButton) return;

    const focusable = () =>
        [...drawer.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])')].filter(
            (element) => element.offsetParent !== null,
        );

    const close = () => {
        drawer.hidden = true;
        document.documentElement.style.overflow = '';
        openButton.setAttribute('aria-expanded', 'false');
        openButton.focus();
    };

    openButton.addEventListener('click', () => {
        drawer.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        openButton.setAttribute('aria-expanded', 'true');
        focusable()[0]?.focus();
    });

    drawer.querySelectorAll('[data-drawer-close]').forEach((button) => button.addEventListener('click', close));

    drawer.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key !== 'Tab') return;

        const items = focusable();
        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches && !drawer.hidden) close();
    });
}

/* Reveal on scroll ------------------------------------------------------ */

function initReveal() {
    const items = document.querySelectorAll('[data-reveal]');

    if (!items.length) return;

    if (reduceMotion.matches || !('IntersectionObserver' in window)) {
        items.forEach((item) => item.setAttribute('data-revealed', 'true'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.setAttribute('data-revealed', 'true');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    items.forEach((item) => observer.observe(item));
}

/* Forms ----------------------------------------------------------------- */

function initForms() {
    document.querySelectorAll('form[data-submit-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            form.dataset.submitting = 'true';
            const button = form.querySelector('[type="submit"]');

            if (button) {
                button.setAttribute('aria-disabled', 'true');
                const label = button.querySelector('[data-label]');
                if (label && button.dataset.loadingText) {
                    label.textContent = button.dataset.loadingText;
                }
            }
        });
    });

    const summary = document.querySelector('[data-error-summary]');
    if (summary) {
        summary.focus();
    } else {
        document.querySelector('[aria-invalid="true"]')?.focus();
    }

    document.querySelectorAll('[data-file-input]').forEach((input) => {
        const output = document.getElementById(input.dataset.fileInput);
        input.addEventListener('change', () => {
            if (output) output.textContent = input.files?.[0]?.name ?? '';
        });
    });

    document.querySelectorAll('[data-autosubmit]').forEach((control) => {
        control.addEventListener('change', () => control.form?.requestSubmit());
    });

    document.querySelectorAll('[data-char-count]').forEach((field) => {
        const output = document.getElementById(field.dataset.charCount);
        const max = Number(field.getAttribute('maxlength'));
        const update = () => {
            if (output) output.textContent = `${field.value.length} / ${max}`;
        };
        field.addEventListener('input', update);
        update();
    });
}

/* Announcement bar ------------------------------------------------------ */

function initAnnouncement() {
    const bar = document.querySelector('[data-announcement]');
    if (!bar) return;

    const key = `dxcovr.announcement.${bar.dataset.announcement}`;

    if (safeStorage('get', key) === 'dismissed') {
        bar.hidden = true;
        return;
    }

    bar.querySelector('[data-announcement-dismiss]')?.addEventListener('click', () => {
        bar.hidden = true;
        safeStorage('set', key, 'dismissed');
    });
}

/* Looping videos: play while visible, respect reduced motion, pausable -- */

function initLoopVideos() {
    document.querySelectorAll('[data-loop-video]').forEach((video) => {
        const toggle = video.parentElement.querySelector('[data-video-toggle]');
        const label = toggle?.querySelector('[data-video-label]');
        const pauseIcon = toggle?.querySelector('[data-video-icon="pause"]');
        const playIcon = toggle?.querySelector('[data-video-icon="play"]');
        let stoppedByVisitor = reduceMotion.matches;
        let visible = false;

        const render = () => {
            const paused = video.paused;
            toggle?.setAttribute('aria-pressed', String(paused));
            if (label) label.textContent = paused ? 'Play video' : 'Pause video';
            pauseIcon?.classList.toggle('hidden', paused);
            playIcon?.classList.toggle('hidden', !paused);
        };

        const sync = () => {
            if (visible && !stoppedByVisitor) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        };

        if (toggle) {
            toggle.hidden = false;
            toggle.addEventListener('click', () => {
                stoppedByVisitor = !video.paused;
                if (stoppedByVisitor) {
                    video.pause();
                } else {
                    video.play().catch(() => {});
                }
            });
        }

        video.addEventListener('play', render);
        video.addEventListener('pause', render);

        new IntersectionObserver((entries) => {
            visible = entries[0].isIntersecting;
            sync();
        }, { threshold: 0.25 }).observe(video);

        render();
    });
}

/* Share: native share sheet, falling back to copying the link ---------- */

function initShareButtons() {
    document.querySelectorAll('[data-share-url]').forEach((button) => {
        const canShare = typeof navigator.share === 'function';
        const canCopy = Boolean(navigator.clipboard);
        if (!canShare && !canCopy) return;

        button.hidden = false;
        const label = button.querySelector('[data-share-label]');

        button.addEventListener('click', async () => {
            const url = button.dataset.shareUrl;

            if (canShare) {
                try {
                    await navigator.share({ title: button.dataset.shareTitle, url });
                } catch {
                    // Closing the share sheet is not an error worth reporting.
                }
                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                if (label) label.textContent = 'Link copied';
            } catch {
                if (label) label.textContent = 'Copy failed';
            }
            setTimeout(() => {
                if (label) label.textContent = 'Share';
            }, 2200);
        });
    });
}

/* Share: copy link ------------------------------------------------------ */

function initCopyLinks() {
    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        button.hidden = !navigator.clipboard;
        button.addEventListener('click', async () => {
            const status = button.querySelector('[data-label]');
            try {
                await navigator.clipboard.writeText(button.dataset.copyUrl);
                if (status) status.textContent = 'Link copied';
            } catch {
                if (status) status.textContent = 'Copy failed';
            }
            setTimeout(() => {
                if (status) status.textContent = 'Copy link';
            }, 2200);
        });
    });
}

/* Table of contents ----------------------------------------------------- */

function initToc() {
    const links = [...document.querySelectorAll('[data-toc] a[href^="#"]')];
    if (!links.length || !('IntersectionObserver' in window)) return;

    const headings = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1)))).filter(Boolean);

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                links.forEach((link) => link.setAttribute('aria-current', link.hash === `#${entry.target.id}` ? 'true' : 'false'));
            });
        },
        { rootMargin: '0px 0px -70% 0px' },
    );

    headings.forEach((heading) => observer.observe(heading));
}

/* Spotlight carousels --------------------------------------------------- */

function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const prev = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        const status = carousel.querySelector('[data-carousel-status]');
        const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
        if (!track || !slides.length) return;

        const step = () => {
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            return slides[0].getBoundingClientRect().width + gap;
        };

        const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];

        const update = () => {
            const max = track.scrollWidth - track.clientWidth - 2;
            if (prev) prev.disabled = track.scrollLeft <= 2;
            if (next) next.disabled = track.scrollLeft >= max;

            const current = Math.round(track.scrollLeft / step());
            dots.forEach((dot, index) => dot.setAttribute('aria-current', index === current ? 'true' : 'false'));
        };

        dots.forEach((dot, index) =>
            dot.addEventListener('click', () => {
                track.scrollTo({ left: index * step(), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
            }),
        );

        const behaviour = reduceMotion.matches ? 'auto' : 'smooth';

        const go = (direction) => {
            const size = step();
            const target = Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / size) + direction));
            track.scrollBy({ left: direction * size, behavior: behaviour });
            if (status) status.textContent = `Card ${target + 1} of ${slides.length}`;
        };

        prev?.addEventListener('click', () => go(-1));
        next?.addEventListener('click', () => go(1));

        let ticking = false;
        track.addEventListener(
            'scroll',
            () => {
                if (ticking) return;
                ticking = true;
                window.requestAnimationFrame(() => {
                    update();
                    ticking = false;
                });
            },
            { passive: true },
        );

        // Keep a focused card fully in view when tabbing through its links.
        track.addEventListener('focusin', (event) => {
            const slide = event.target.closest('[data-carousel-slide]');
            slide?.scrollIntoView({ behavior: behaviour, block: 'nearest', inline: 'nearest' });
        });

        window.addEventListener('resize', update, { passive: true });
        update();
    });
}

/* Scrolling strips: pause control (WCAG 2.2.2) ------------------------- */

function initMotionToggles() {
    document.querySelectorAll('[data-motion-group]').forEach((group) => {
        const toggle = group.querySelector('[data-motion-toggle]');
        if (!toggle || !group.querySelector('.is-animated')) return;

        const label = toggle.querySelector('[data-motion-label]');
        const pauseIcon = toggle.querySelector('[data-motion-icon="pause"]');
        const playIcon = toggle.querySelector('[data-motion-icon="play"]');

        const render = (paused) => {
            group.toggleAttribute('data-paused', paused);
            toggle.setAttribute('aria-pressed', String(paused));
            if (label) label.textContent = paused ? 'Resume scrolling' : 'Pause scrolling';
            pauseIcon?.classList.toggle('hidden', paused);
            playIcon?.classList.toggle('hidden', !paused);
        };

        const sync = () => {
            toggle.hidden = reduceMotion.matches;
        };

        toggle.addEventListener('click', () => render(!group.hasAttribute('data-paused')));
        reduceMotion.addEventListener?.('change', sync);
        sync();
    });
}

/* Optional CAPTCHA ------------------------------------------------------ */

function initCaptcha() {
    const widget = document.querySelector('[data-turnstile]');
    if (!widget) return;

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.async = true;
    script.defer = true;
    const nonce = document.querySelector('meta[name="csp-nonce"]')?.content;
    if (nonce) script.nonce = nonce;
    document.head.appendChild(script);
}

document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    initDropdowns();
    initDrawer();
    initReveal();
    initForms();
    initAnnouncement();
    initCopyLinks();
    initShareButtons();
    initLoopVideos();
    initToc();
    initCarousels();
    initMotionToggles();
    initQuestionnaire();
    initCaptcha();
});
