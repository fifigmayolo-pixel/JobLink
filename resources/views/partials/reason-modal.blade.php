{{-- Asks for a required reason, then submits the form.
     Add data-reason to any <form> that has <input type="hidden" name="reason">:
       data-reason="Text shown above the box"
       data-reason-title="Dialog title"
       data-reason-label="Label of the text box"
       data-reason-button="Label of the action button"
       data-reason-variant="danger"   (optional)
     For buttons that can't have their own form, use JLReason.submit({...}). --}}

<div class="modal fade" id="jlReasonModal" tabindex="-1" aria-labelledby="jlReasonTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jl-confirm">
            <div class="modal-body p-4 p-sm-5">
                <div class="text-center">
                    <div class="jl-confirm-icon"><i class="bi bi-chat-left-text"></i></div>
                    <h2 class="h4 mb-2" id="jlReasonTitle">Reason required</h2>
                    <p class="text-secondary mb-3" id="jlReasonMessage"></p>
                </div>

                <label for="jlReasonInput" class="form-label fw-semibold" id="jlReasonLabel">Reason</label>
                <textarea id="jlReasonInput" class="form-control" rows="3"></textarea>
                <div class="invalid-feedback">A reason is required.</div>

                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end mt-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="jlReasonOk">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var el = document.getElementById('jlReasonModal');
        if (!el) return;

        var TOKEN = '{{ csrf_token() }}';
        var box = el.querySelector('.jl-confirm');
        var iconEl = el.querySelector('.jl-confirm-icon i');
        var titleEl = document.getElementById('jlReasonTitle');
        var msgEl = document.getElementById('jlReasonMessage');
        var labelEl = document.getElementById('jlReasonLabel');
        var input = document.getElementById('jlReasonInput');
        var okBtn = document.getElementById('jlReasonOk');
        var modal = null, onSubmit = null;

        function open(opts, callback) {
            var danger = opts.variant === 'danger';
            titleEl.textContent = opts.title || 'Reason required';
            msgEl.textContent = opts.message || '';
            msgEl.classList.toggle('d-none', !opts.message);
            labelEl.textContent = opts.label || 'Reason';
            okBtn.textContent = opts.button || 'Confirm';
            okBtn.className = 'btn px-4 ' + (danger ? 'btn-danger' : 'btn-jl-gold');
            box.classList.toggle('is-danger', danger);
            iconEl.className = 'bi bi-' + (opts.icon || 'chat-left-text');
            input.value = '';
            input.classList.remove('is-invalid');
            onSubmit = callback;
            modal = modal || new bootstrap.Modal(el);
            modal.show();
        }

        okBtn.addEventListener('click', function () {
            var reason = input.value.trim();
            if (!reason) { input.classList.add('is-invalid'); input.focus(); return; }
            var cb = onSubmit;
            onSubmit = null;
            modal.hide();
            if (cb) cb(reason);
        });
        input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
        el.addEventListener('shown.bs.modal', function () { input.focus(); });
        el.addEventListener('hidden.bs.modal', function () { onSubmit = null; });

        // Forms with data-reason (capture phase, so it runs before the page loader)
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-reason')) return;
            if (form.dataset.reasonDone === '1') { delete form.dataset.reasonDone; return; }

            e.preventDefault();
            e.stopImmediatePropagation();
            var submitter = e.submitter || null;

            open({
                title: form.dataset.reasonTitle,
                message: form.getAttribute('data-reason'),
                label: form.dataset.reasonLabel,
                button: form.dataset.reasonButton,
                variant: form.dataset.reasonVariant,
                icon: form.dataset.reasonIcon
            }, function (reason) {
                var field = form.querySelector('[name="reason"]');
                if (!field) {
                    field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'reason';
                    form.appendChild(field);
                }
                field.value = reason;
                form.dataset.reasonDone = '1';
                if (form.requestSubmit) { submitter ? form.requestSubmit(submitter) : form.requestSubmit(); }
                else { form.submit(); }
            });
        }, true);

        // For buttons that sit inside another form and can't have their own
        window.JLReason = {
            submit: function (opts) {
                open(opts, function (reason) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = opts.action;
                    form.style.display = 'none';

                    var fields = [['_token', TOKEN], ['reason', reason]];
                    if (opts.method) fields.push(['_method', opts.method]);
                    fields.forEach(function (p) {
                        var i = document.createElement('input');
                        i.type = 'hidden'; i.name = p[0]; i.value = p[1];
                        form.appendChild(i);
                    });

                    document.body.appendChild(form);
                    if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
                });
            }
        };
    })();
</script>