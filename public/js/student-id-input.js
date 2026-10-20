/*
 * Student ID typing helper.
 * Add the attribute  data-student-id  to any <input>:
 *   - "23ac2344" / "23-ac-2344" is turned into "23-AC-2344" as the student types or pastes
 *   - a green check shows when the ID has the right format
 *   - a red X shows when the input can't become a valid ID (or is still incomplete after leaving the field)
 *
 * The check only means "correct format", not "real student". The server
 * (App\Support\StudentId) is the real check, and the Coordinator reviews every request.
 * Keep the constants below in sync with App\Support\StudentId.
 */
(function () {
    'use strict';

    var CAMPUS = 'AC';
    var YEAR_DIGITS = 2;
    var NUMBER_DIGITS = 4;

    var FULL = new RegExp('^\\d{' + YEAR_DIGITS + '}-' + CAMPUS + '-\\d{' + NUMBER_DIGITS + '}$');
    var SHAPE = new RegExp('^(\\d{0,' + YEAR_DIGITS + '})([A-Z]{0,' + CAMPUS.length + '})(\\d{0,' + NUMBER_DIGITS + '})$');

    // Values that could still become a valid ID if the student keeps typing.
    var PREFIX = [
        new RegExp('^\\d{0,' + YEAR_DIGITS + '}$'),
        new RegExp('^\\d{' + YEAR_DIGITS + '}-' + CAMPUS.charAt(0) + '$'),
        new RegExp('^\\d{' + YEAR_DIGITS + '}-' + CAMPUS + '$'),
        new RegExp('^\\d{' + YEAR_DIGITS + '}-' + CAMPUS + '-\\d{1,' + (NUMBER_DIGITS - 1) + '}$')
    ];

    var CHECK_SVG = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><circle cx='8' cy='8' r='8' fill='#198754'/><path d='M4.5 8.3l2.4 2.4 4.6-4.9' fill='none' stroke='white' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/></svg>";
    var X_SVG = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><circle cx='8' cy='8' r='8' fill='#dc3545'/><path d='M5.2 5.2l5.6 5.6M10.8 5.2l-5.6 5.6' fill='none' stroke='white' stroke-width='1.7' stroke-linecap='round'/></svg>";

    /** Returns the formatted ID, or null when the text can't fit the format (left as typed). */
    function format(raw) {
        var s = raw.toUpperCase().replace(/[^0-9A-Z]/g, '');
        var m = s.match(SHAPE);
        if (!m) { return null; }

        var year = m[1], campus = m[2], num = m[3];
        if (campus && year.length < YEAR_DIGITS) { return null; }
        if (num && campus.length < CAMPUS.length) { return null; }

        var out = year;
        if (campus) { out += '-' + campus; }
        if (num) { out += '-' + num; }
        return out;
    }

    /** 'none' (neutral), 'ok' (check) or 'bad' (X). */
    function stateOf(value, hasLeftField) {
        if (!value) { return 'none'; }
        if (FULL.test(value)) { return 'ok'; }
        if (!hasLeftField && PREFIX.some(function (re) { return re.test(value); })) { return 'none'; }
        return 'bad';
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { format: format, stateOf: stateOf };
    }
    if (typeof document === 'undefined') { return; }

    function icon(el, state) {
        if (state === 'none') {
            el.style.backgroundImage = 'none';
            el.removeAttribute('aria-invalid');
            return;
        }
        var svg = state === 'ok' ? CHECK_SVG : X_SVG;
        el.style.backgroundImage = 'url("data:image/svg+xml,' + encodeURIComponent(svg) + '")';
        el.style.backgroundRepeat = 'no-repeat';
        el.style.backgroundPosition = 'right 0.75rem center';
        el.style.backgroundSize = '1.25rem 1.25rem';
        el.setAttribute('aria-invalid', state === 'bad' ? 'true' : 'false');
    }

    function init(el) {
        var leftField = document.activeElement !== el; // restored old() values are judged right away

        if (!el.hasAttribute('maxlength')) { el.setAttribute('maxlength', '14'); }
        el.setAttribute('autocapitalize', 'characters');
        el.setAttribute('autocomplete', 'off');
        el.setAttribute('spellcheck', 'false');
        el.style.paddingRight = '2.75rem';

        function apply() {
            var focused = document.activeElement === el;
            var before = 0;

            // Letters/digits left of the caret, so the caret can be put back after dashes are inserted.
            if (focused && el.selectionStart !== null) {
                before = el.value.slice(0, el.selectionStart).replace(/[^0-9A-Za-z]/g, '').length;
            }

            var out = format(el.value);

            if (out === null) {
                // Doesn't fit the format: keep what was typed (just uppercase it) so the X explains itself.
                var up = el.value.toUpperCase();
                if (up !== el.value) {
                    var pos = el.selectionStart;
                    el.value = up;
                    if (focused && pos !== null) { el.setSelectionRange(pos, pos); }
                }
            } else if (out !== el.value) {
                el.value = out;
                if (focused) {
                    var idx = 0, seen = 0;
                    while (idx < out.length && seen < before) {
                        if (/[0-9A-Z]/.test(out.charAt(idx))) { seen++; }
                        idx++;
                    }
                    el.setSelectionRange(idx, idx);
                }
            }

            icon(el, stateOf(el.value, leftField));
        }

        el.addEventListener('input', function (e) {
            if (!e.isComposing) { apply(); }
        });
        el.addEventListener('focus', function () { leftField = false; icon(el, stateOf(el.value, false)); });
        el.addEventListener('blur', function () { leftField = true; icon(el, stateOf(el.value, true)); });

        apply();
    }

    var fields = document.querySelectorAll('input[data-student-id]');
    for (var n = 0; n < fields.length; n++) { init(fields[n]); }
})();