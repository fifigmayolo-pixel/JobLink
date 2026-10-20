/*
 * JobLink idle warning.
 *
 * Loaded on every page, but does nothing unless #idleWarningModal exists
 * (it is only rendered for logged-in users).
 *
 * How it works:
 *  - The "clock" is the last time the server was touched (page load or a
 *    keep-alive ping), so the browser can never think the session is
 *    fresher than the server does.
 *  - Real activity (click, key, scroll, touch) sends a keep-alive ping, at
 *    most once a minute.
 *  - WARNING_SECONDS before the limit, a modal appears. Only the
 *    "Stay logged in" button extends the session from there.
 *  - At the limit we load the dashboard; the server middleware sees the
 *    session is idle, logs the user out and shows the login page with a message.
 *  - The last-touch time is shared between tabs through localStorage, so
 *    activity in one tab keeps the others alive and hides their warnings.
 */
(function () {
    'use strict';

    var modalEl = document.getElementById('idleWarningModal');
    if (!modalEl || typeof bootstrap === 'undefined') { return; }

    var LIMIT_MS = parseInt(modalEl.dataset.limitMinutes, 10) * 60 * 1000;
    var WARN_MS = parseInt(modalEl.dataset.warningSeconds, 10) * 1000;
    var KEEP_ALIVE_URL = modalEl.dataset.keepAliveUrl;
    var EXPIRE_URL = modalEl.dataset.expireUrl;
    var CSRF = modalEl.dataset.csrf;

    var PING_EVERY_MS = 60 * 1000;     // at most one keep-alive per minute
    var EXPIRE_GRACE_MS = 2000;        // wait just past the limit so the server is surely expired too
    var STORAGE_KEY = 'jl:lastTouch';

    var modal = new bootstrap.Modal(modalEl);
    var countdownEl = modalEl.querySelector('[data-idle-countdown]');
    var stayBtn = modalEl.querySelector('[data-idle-stay]');

    var lastTouch = Date.now(); // this page load just touched the server
    var warningShown = false;
    var expired = false;
    var pinging = false;

    function saveTouch(t) {
        lastTouch = t;
        try { localStorage.setItem(STORAGE_KEY, String(t)); } catch (e) { /* private mode: single-tab only */ }
    }

    function syncFromStorage() {
        try {
            var t = parseInt(localStorage.getItem(STORAGE_KEY), 10);
            if (!isNaN(t) && t > lastTouch) { lastTouch = t; }
        } catch (e) { /* ignore */ }
    }

    function fmt(ms) {
        var s = Math.max(0, Math.ceil(ms / 1000));
        return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
    }

    function showWarning(remaining) {
        if (!warningShown) { warningShown = true; modal.show(); }
        if (countdownEl) { countdownEl.textContent = fmt(remaining); }
    }

    function hideWarning() {
        if (warningShown) { warningShown = false; modal.hide(); }
    }

    function expire() {
        if (expired) { return; }
        expired = true;
        window.location.replace(EXPIRE_URL);
    }

    function tick() {
        if (expired) { return; }
        var remaining = LIMIT_MS - (Date.now() - lastTouch);

        if (remaining <= -EXPIRE_GRACE_MS) { expire(); return; }
        if (remaining <= WARN_MS) { showWarning(remaining); } else { hideWarning(); }
    }

    function keepAlive() {
        if (pinging || expired) { return; }
        pinging = true;

        fetch(KEEP_ALIVE_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) { expire(); return; }
            if (res.ok) { saveTouch(Date.now()); hideWarning(); }
        }).catch(function () {
            // Network problem: leave the clock alone; it will warn/expire normally.
        }).then(function () {
            pinging = false;
        });
    }

    function onActivity() {
        if (expired || warningShown) { return; } // from the warning on, only the button counts
        if (Date.now() - lastTouch >= PING_EVERY_MS) { keepAlive(); }
    }

    ['mousedown', 'keydown', 'touchstart', 'wheel', 'scroll'].forEach(function (name) {
        window.addEventListener(name, onActivity, { passive: true, capture: true });
    });

    // Another tab touched the server: adopt its time and close our warning.
    window.addEventListener('storage', function (e) {
        if (e.key !== STORAGE_KEY || !e.newValue) { return; }
        var t = parseInt(e.newValue, 10);
        if (!isNaN(t) && t > lastTouch) { lastTouch = t; hideWarning(); }
    });

    // Laptop woke up / tab came back / page restored from back-forward cache.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { syncFromStorage(); tick(); }
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { syncFromStorage(); tick(); }
    });

    if (stayBtn) { stayBtn.addEventListener('click', keepAlive); }

    saveTouch(lastTouch);
    setInterval(tick, 1000);
})();