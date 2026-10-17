{{-- Replaces the browser's confirm(). Add data-confirm to any <form>:
       data-confirm="Message shown in the dialog"
       data-confirm-title="Dialog title"
       data-confirm-button="Label of the action button"
       data-confirm-icon="key"            (any Bootstrap Icons name)
       data-confirm-variant="danger"      (optional, for destructive actions) --}}

<div class="modal fade" id="jlConfirmModal" tabindex="-1" aria-labelledby="jlConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jl-confirm">
            <div class="modal-body text-center p-4 p-sm-5">
                <div class="jl-confirm-icon"><i class="bi bi-question-circle"></i></div>
                <h2 class="h4 mb-2" id="jlConfirmTitle">Are you sure?</h2>
                <p class="text-secondary mb-4" id="jlConfirmMessage"></p>
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-jl-gold px-4" id="jlConfirmOk">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .jl-confirm {
        border: none;
        border-top: 4px solid var(--jl-gold);
        border-radius: 0.9rem;
        box-shadow: 0 20px 50px rgba(8, 27, 61, 0.25);
    }
    .jl-confirm-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1.1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.7rem;
        background: rgba(201, 162, 39, 0.16);
        color: var(--jl-navy);
    }
    .jl-confirm.is-danger { border-top-color: var(--jl-rose); }
    .jl-confirm.is-danger .jl-confirm-icon { background: rgba(180, 85, 63, 0.12); color: var(--jl-rose); }
</style>

<script>
    (function () {
        var el = document.getElementById('jlConfirmModal');
        if (!el) return;

        var box = el.querySelector('.jl-confirm');
        var iconEl = el.querySelector('.jl-confirm-icon i');
        var titleEl = document.getElementById('jlConfirmTitle');
        var msgEl = document.getElementById('jlConfirmMessage');
        var okBtn = document.getElementById('jlConfirmOk');
        var modal = null, pendingForm = null, pendingSubmitter = null;

        // Capture phase, so this runs before the page loader's submit listener
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;

            // Second pass, after the user pressed the action button: let it through
            if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }

            e.preventDefault();
            e.stopImmediatePropagation();

            pendingForm = form;
            pendingSubmitter = e.submitter || null;

            var danger = form.dataset.confirmVariant === 'danger';
            titleEl.textContent = form.dataset.confirmTitle || 'Are you sure?';
            msgEl.textContent = form.getAttribute('data-confirm');
            okBtn.textContent = form.dataset.confirmButton || 'Confirm';
            okBtn.className = 'btn px-4 ' + (danger ? 'btn-danger' : 'btn-jl-gold');
            box.classList.toggle('is-danger', danger);
            iconEl.className = 'bi bi-' + (form.dataset.confirmIcon || (danger ? 'exclamation-triangle' : 'question-circle'));

            modal = modal || new bootstrap.Modal(el);
            modal.show();
        }, true);

       okBtn.addEventListener('click', function () {
    if (!pendingForm) return;
    var form = pendingForm;
    pendingForm = null;
    modal.hide();
    if (window.PageLoader) PageLoader.show();
    form.submit();
        });

        el.addEventListener('hidden.bs.modal', function () { pendingForm = null; });
    })();
</script>