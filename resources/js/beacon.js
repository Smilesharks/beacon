/**
 * Beacon — Statamic notification frontend
 * Vanilla JS, no dependencies. Target: <8kb minified + gzipped.
 */
(function () {
    'use strict';

    const STORAGE_PREFIX = 'beacon_';

    /** Read the inline JSON config block injected by {{ beacon:assets }} */
    function readConfig() {
        const el = document.getElementById('beacon-config');
        if (!el) return null;
        try {
            return JSON.parse(el.textContent);
        } catch {
            return null;
        }
    }

    // ---------------------------------------------------------------------------
    // Frequency checks (client-side persistence)
    // ---------------------------------------------------------------------------

    function getStorageKey(handle) {
        return STORAGE_PREFIX + handle;
    }

    function shouldShow(config) {
        const key = getStorageKey(config.handle);
        const freq = config.frequency;

        if (freq === 'always') return true;

        if (freq === 'session') {
            return !sessionStorage.getItem(key);
        }

        if (freq === 'daily') {
            const stored = localStorage.getItem(key);
            if (!stored) return true;
            const ts = parseInt(stored, 10);
            return Date.now() - ts > 86400000; // 24 hours
        }

        if (freq === 'permanent') {
            return !localStorage.getItem(key);
        }

        if (freq === 'dismissed') {
            return !sessionStorage.getItem(key);
        }

        return true;
    }

    function markShown(config) {
        const key = getStorageKey(config.handle);
        const freq = config.frequency;

        if (freq === 'session' || freq === 'dismissed') {
            sessionStorage.setItem(key, '1');
        } else if (freq === 'daily') {
            localStorage.setItem(key, String(Date.now()));
        } else if (freq === 'permanent') {
            localStorage.setItem(key, '1');
        }
    }

    function markDismissed(config) {
        const key = getStorageKey(config.handle);
        const freq = config.frequency;

        if (freq === 'dismissed' || freq === 'permanent') {
            localStorage.setItem(key, '1');
        } else if (freq === 'session') {
            sessionStorage.setItem(key, '1');
        }
    }

    // ---------------------------------------------------------------------------
    // HTML rendering per type
    // ---------------------------------------------------------------------------

    function renderAnnouncement(payload) {
        const cta = payload.cta_label && payload.cta_url
            ? `<a class="beacon__cta" href="${esc(payload.cta_url)}">${esc(payload.cta_label)}</a>`
            : '';
        return `<p class="beacon__message" id="beacon-modal-title">${esc(payload.message)}</p>${cta}`;
    }

    function renderDiscount(payload) {
        const countdown = payload.show_countdown && payload.countdown_until
            ? `<span class="beacon__countdown" data-until="${esc(payload.countdown_until)}"></span>`
            : '';
        return `
            <p class="beacon__message" id="beacon-modal-title">${esc(payload.message)}</p>
            <div class="beacon__code-wrap">
                <code class="beacon__code">${esc(payload.code)}</code>
                <button type="button" class="beacon__copy-btn" data-code="${esc(payload.code)}">
                    ${esc(payload.code_label || 'Copy code')}
                </button>
            </div>
            ${countdown}
        `.trim();
    }

    function renderCta(payload) {
        const secondary = payload.secondary_label && payload.secondary_url
            ? `<a class="beacon__btn beacon__btn--secondary" href="${esc(payload.secondary_url)}">${esc(payload.secondary_label)}</a>`
            : '';
        return `
            <h2 class="beacon__headline" id="beacon-modal-title">${esc(payload.headline)}</h2>
            ${payload.description ? `<p class="beacon__message">${esc(payload.description)}</p>` : ''}
            <div class="beacon__actions">
                <a class="beacon__btn beacon__btn--primary" href="${esc(payload.primary_url)}">${esc(payload.primary_label)}</a>
                ${secondary}
            </div>
        `.trim();
    }

    function renderConsent(payload) {
        return `
            <p class="beacon__message" id="beacon-modal-title">${esc(payload.message)}</p>
            <div class="beacon__actions">
                <button type="button" class="beacon__btn beacon__btn--primary beacon__accept-btn">
                    ${esc(payload.accept_label || 'Accept')}
                </button>
                <button type="button" class="beacon__btn beacon__btn--secondary beacon__decline-btn">
                    ${esc(payload.decline_label || 'Decline')}
                </button>
            </div>
        `.trim();
    }

    function renderContent(config) {
        const p = config.payload || {};
        switch (config.type) {
            case 'discount': return renderDiscount(p);
            case 'cta':      return renderCta(p);
            case 'consent':  return renderConsent(p);
            default:         return renderAnnouncement(p);
        }
    }

    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ---------------------------------------------------------------------------
    // Build the notification element
    // ---------------------------------------------------------------------------

    function resolvePosition(config) {
        const pos = config.position || 'bottom-right';
        // On narrow viewports, collapse all non-modal positions to bottom-bar
        if (pos !== 'modal-center' && window.innerWidth < 480) {
            return 'bottom-bar';
        }
        return pos;
    }

    function buildElement(config) {
        const position = resolvePosition(config);
        const isModal = position === 'modal-center';
        const isBar = position === 'top-bar' || position === 'bottom-bar';

        const wrapper = document.createElement('div');
        wrapper.className = `beacon beacon--${position}`;

        if (isModal) {
            wrapper.setAttribute('role', 'dialog');
            wrapper.setAttribute('aria-modal', 'true');
            wrapper.setAttribute('aria-labelledby', 'beacon-modal-title');
        } else {
            wrapper.setAttribute('role', 'alert');
            wrapper.setAttribute('aria-live', 'polite');
        }

        const inner = document.createElement('div');
        inner.className = 'beacon__inner';
        inner.innerHTML = renderContent(config);

        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'beacon__dismiss';
        dismiss.setAttribute('aria-label', 'Dismiss notification');
        dismiss.innerHTML = '<svg aria-hidden="true" viewBox="0 0 16 16" width="16" height="16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>';

        wrapper.appendChild(inner);
        wrapper.appendChild(dismiss);

        // iOS safe area for bottom positions
        if (position === 'bottom-bar' || position === 'bottom-right' || position === 'bottom-left') {
            wrapper.style.paddingBottom = 'max(var(--beacon-padding), env(safe-area-inset-bottom))';
        }

        if (isModal) {
            const backdrop = document.createElement('div');
            backdrop.className = 'beacon-backdrop';
            backdrop.setAttribute('aria-hidden', 'true');
            return { wrapper, backdrop };
        }

        return { wrapper, backdrop: null };
    }

    // ---------------------------------------------------------------------------
    // Focus trap (modal only)
    // ---------------------------------------------------------------------------

    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function trapFocus(el) {
        function handleKey(e) {
            if (e.key !== 'Tab') return;
            const focusable = Array.from(el.querySelectorAll(FOCUSABLE));
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }
        el.addEventListener('keydown', handleKey);
        return handleKey;
    }

    // ---------------------------------------------------------------------------
    // Countdown timer
    // ---------------------------------------------------------------------------

    function startCountdown(wrapper) {
        const el = wrapper.querySelector('.beacon__countdown');
        if (!el) return;
        const until = new Date(el.dataset.until).getTime();
        if (isNaN(until)) return;

        // If the countdown target is already in the past, hide the notification
        if (until - Date.now() <= 0) {
            wrapper._beaconExpired = true;
            return;
        }

        function tick() {
            const diff = until - Date.now();
            if (diff <= 0) {
                clearInterval(wrapper._beaconTimer);
                if (el.parentElement) el.parentElement.removeChild(el);
                return;
            }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            el.textContent = `${h}h ${m}m ${s}s`;
        }
        const timer = setInterval(tick, 1000);
        wrapper._beaconTimer = timer;
        tick();
    }

    // ---------------------------------------------------------------------------
    // Clipboard copy
    // ---------------------------------------------------------------------------

    function setupCopy(wrapper) {
        const btn = wrapper.querySelector('.beacon__copy-btn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            const code = btn.dataset.code;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.textContent = btn.dataset.originalLabel || 'Copy code'; }, 2000);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = code;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.textContent = btn.dataset.originalLabel || 'Copy code'; }, 2000);
            }
        });
    }

    // ---------------------------------------------------------------------------
    // Consent hooks
    // ---------------------------------------------------------------------------

    function resolveConsentHook(hook) {
        if (!hook || !/^[a-zA-Z][a-zA-Z0-9]{0,30}(\.[a-zA-Z][a-zA-Z0-9]{0,30}){0,2}$/.test(hook)) {
            return null;
        }
        const segments = hook.split('.');
        if (segments.length > 3) return null;
        try {
            const fn = segments.reduce((obj, key) => obj?.[key], window);
            return typeof fn === 'function' ? fn.bind(window) : null;
        } catch {
            return null;
        }
    }

    function setupConsentButtons(wrapper, config, api) {
        const acceptBtn = wrapper.querySelector('.beacon__accept-btn');
        const declineBtn = wrapper.querySelector('.beacon__decline-btn');
        const fn = resolveConsentHook(config.payload?.consent_hook);

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                if (fn) {
                    try { fn('consent', 'update', { ad_storage: 'granted', analytics_storage: 'granted' }); } catch {}
                }
                api.dismiss();
            });
        }

        if (declineBtn) {
            declineBtn.addEventListener('click', function () {
                if (fn) {
                    try { fn('consent', 'update', { ad_storage: 'denied', analytics_storage: 'denied' }); } catch {}
                }
                api.dismiss();
            });
        }
    }

    // ---------------------------------------------------------------------------
    // Public API and show/dismiss
    // ---------------------------------------------------------------------------

    let _activeWrapper = null;
    let _activeBackdrop = null;
    let _activeTrapHandler = null;
    let _previousFocus = null;
    let _activeConfig = null;
    const _listeners = {};

    // Pending trigger cleanup (scroll handlers, delay/exit-intent timeouts)
    const _pendingTimers = [];
    const _pendingListeners = [];

    function _teardownPending() {
        _pendingTimers.forEach(id => clearTimeout(id));
        _pendingTimers.length = 0;
        _pendingListeners.forEach(([target, event, handler]) => target.removeEventListener(event, handler));
        _pendingListeners.length = 0;
    }

    function emit(event, detail) {
        (_listeners[event] || []).forEach(fn => fn(detail));
    }

    const api = {
        show(overrideConfig) {
            const config = overrideConfig || _activeConfig;
            if (!config || _activeWrapper) return;
            _show(config);
        },

        dismiss() {
            _dismiss();
        },

        on(event, fn) {
            if (!_listeners[event]) _listeners[event] = [];
            _listeners[event].push(fn);
        },

        waitFor(promise) {
            if (promise && typeof promise.then === 'function') {
                promise.then(() => _show(_activeConfig));
            }
        },
    };

    function _show(config) {
        if (!config || _activeWrapper) return;
        const { wrapper, backdrop } = buildElement(config);

        _activeWrapper = wrapper;
        _activeBackdrop = backdrop;
        _activeConfig = config;
        _previousFocus = document.activeElement;

        if (backdrop) document.body.appendChild(backdrop);
        document.body.appendChild(wrapper);

        setupCopy(wrapper);
        startCountdown(wrapper);

        if (config.type === 'consent') setupConsentButtons(wrapper, config, api);

        if (config.position === 'modal-center') {
            _activeTrapHandler = trapFocus(wrapper);
            const firstFocusable = wrapper.querySelector(FOCUSABLE);
            if (firstFocusable) firstFocusable.focus();
        }

        wrapper.querySelector('.beacon__dismiss')?.addEventListener('click', () => api.dismiss());

        document.addEventListener('keydown', _onKeydown);

        markShown(config);
        emit('show', { config });
    }

    function _dismiss() {
        if (!_activeWrapper) return;

        _teardownPending();
        if (_activeWrapper._beaconTimer) clearInterval(_activeWrapper._beaconTimer);
        if (_activeTrapHandler) _activeWrapper.removeEventListener('keydown', _activeTrapHandler);

        _activeWrapper.remove();
        _activeBackdrop?.remove();

        document.removeEventListener('keydown', _onKeydown);

        if (_previousFocus && typeof _previousFocus.focus === 'function') {
            _previousFocus.focus();
        }

        markDismissed(_activeConfig);
        emit('dismiss', { config: _activeConfig });

        _activeWrapper = null;
        _activeBackdrop = null;
        _activeTrapHandler = null;
        _previousFocus = null;
    }

    function _onKeydown(e) {
        if (e.key === 'Escape') api.dismiss();
    }

    // ---------------------------------------------------------------------------
    // Trigger logic
    // ---------------------------------------------------------------------------

    function applyTrigger(config) {
        const trigger = config.trigger || 'immediate';
        const value = config.trigger_value != null ? Number(config.trigger_value) : null;
        const defaultDelay = Number(config.default_delay ?? 2);

        if (trigger === 'immediate') {
            _show(config);
            return;
        }

        if (trigger === 'delay') {
            const secs = (value != null && !isNaN(value)) ? value : defaultDelay;
            const id = setTimeout(() => _show(config), secs * 1000);
            _pendingTimers.push(id);
            return;
        }

        if (trigger === 'scroll') {
            const pct = (value != null && !isNaN(value)) ? value : 50;
            function onScroll() {
                const scrolled = window.scrollY + window.innerHeight;
                const total = document.documentElement.scrollHeight;
                if (total > 0 && (scrolled / total) * 100 >= pct) {
                    window.removeEventListener('scroll', onScroll, { passive: true });
                    _show(config);
                }
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            _pendingListeners.push([window, 'scroll', onScroll]);
            return;
        }

        if (trigger === 'exit_intent') {
            // Only on pointer devices (desktop)
            if (!window.matchMedia('(pointer: fine)').matches) {
                const id = setTimeout(() => _show(config), defaultDelay * 1000);
                _pendingTimers.push(id);
                return;
            }
            function onMouseleave(e) {
                if (e.clientY <= 0) {
                    document.documentElement.removeEventListener('mouseleave', onMouseleave);
                    _show(config);
                }
            }
            document.documentElement.addEventListener('mouseleave', onMouseleave);
            _pendingListeners.push([document.documentElement, 'mouseleave', onMouseleave]);
            return;
        }

        // Fallback
        _show(config);
    }

    // ---------------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------------

    function boot() {
        const config = readConfig();
        if (!config) return;

        _activeConfig = config;
        window.Beacon = api;

        if (!shouldShow(config)) return;

        applyTrigger(config);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
