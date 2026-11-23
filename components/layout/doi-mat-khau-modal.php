<?php if (!empty($_SESSION['manguoidung'])): ?>
<dialog id="password-modal" aria-labelledby="password-modal-title" class="w-full max-w-lg rounded-2xl bg-white p-0 text-slate-800 shadow-xl" style="max-height:90vh">
    <form id="password-modal-form" class="space-y-4 p-6" novalidate>
        <div class="flex items-center justify-between gap-4">
            <h2 id="password-modal-title" class="text-xl font-bold">Đổi mật khẩu</h2>
            <button type="button" data-password-close aria-label="Đóng" class="rounded-lg px-3 py-1 text-2xl hover:bg-slate-100">×</button>
        </div>
        <p class="text-sm text-slate-500" id="modal-password-help">Mật khẩu mới tối thiểu 6 ký tự.</p>
        <div id="password-modal-message" role="status" aria-live="polite" hidden></div>
        <input type="hidden" name="csrf_token">
        <?php foreach (['current_password' => 'Mật khẩu hiện tại', 'new_password' => 'Mật khẩu mới', 'confirm_password' => 'Xác nhận mật khẩu mới'] as $field => $label): ?>
        <div>
            <label for="modal-<?= $field ?>" class="block text-sm font-medium"><?= $label ?> <span class="text-red-500">*</span></label>
            <div class="relative">
                <input id="modal-<?= $field ?>" name="<?= $field ?>" type="password" required autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>" aria-describedby="modal-password-help modal-<?= $field ?>-error" class="mt-1 w-full rounded-lg border border-slate-300 pl-3 pr-12 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php if ($field === 'current_password'): ?>
                <button type="button" id="modal-current-eye" aria-label="Hiển thị mật khẩu hiện tại" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-500 hover:text-indigo-700">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path data-eye-slash d="m3 3 18 18" style="display:none"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <p id="modal-<?= $field ?>-error" data-password-error="<?= $field ?>" class="mt-1 text-sm text-red-600"></p>
        </div>
        <?php endforeach; ?>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="modal-show-passwords"> Hiển thị mật khẩu mới</label>
        <div class="flex gap-3 pt-2">
            <button type="submit" id="password-modal-submit" disabled class="flex-1 rounded-lg bg-indigo-600 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Đổi mật khẩu</button>
            <button type="button" data-password-close class="rounded-lg border border-slate-300 px-6 py-2 hover:bg-slate-50">Hủy</button>
        </div>
    </form>
</dialog>
<style>#password-modal::backdrop { background: rgb(15 23 42 / 55%); } #password-modal { margin: auto; width: calc(100% - 2rem); } </style>
<script>
(() => {
    const modal = document.getElementById('password-modal');
    const form = document.getElementById('password-modal-form');
    const submit = document.getElementById('password-modal-submit');
    const message = document.getElementById('password-modal-message');
    const fields = ['current_password', 'new_password', 'confirm_password'];
    const endpoint = '/stackers_project/pages/doi-mat-khau/api.php';
    let busy = false;
    let opener;
    let opening = 0;
    const eye = document.getElementById('modal-current-eye');
    function hidePasswords() {
        fields.forEach(name => form.elements[name].type = 'password');
        eye.setAttribute('aria-pressed', 'false');
        eye.setAttribute('aria-label', 'Hiển thị mật khẩu hiện tại');
        eye.querySelector('[data-eye-slash]').style.display = 'none';
    }
    eye.addEventListener('click', () => {
        const show = form.elements.current_password.type === 'password';
        form.elements.current_password.type = show ? 'text' : 'password';
        eye.setAttribute('aria-pressed', String(show));
        eye.setAttribute('aria-label', show ? 'Ẩn mật khẩu hiện tại' : 'Hiển thị mật khẩu hiện tại');
        eye.querySelector('[data-eye-slash]').style.display = show ? '' : 'none';
    });
    function notice(text, success = false) {
        message.textContent = text;
        message.hidden = !text;
        message.className = 'rounded-lg p-3 text-sm ' + (success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700');
    }
    function clearErrors() {
        notice('');
        fields.forEach(name => {
            form.elements[name].removeAttribute('aria-invalid');
            form.querySelector('[data-password-error="' + name + '"]').textContent = '';
        });
    }
    document.addEventListener('click', async event => {
        const trigger = event.target.closest('a[href="/stackers_project/pages/doi-mat-khau/"], [data-open-password-modal]');
        if (!trigger) return;
        event.preventDefault();
        if (modal.open) return;
        opener = trigger;
        const panel = document.getElementById('account-dropdown-panel');
        if (panel) panel.hidden = true;
        const toggle = document.querySelector('[data-account-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.querySelector('svg').style.transform = '';
        }
        form.reset();
        hidePasswords();
        clearErrors();
        submit.disabled = true;
        modal.showModal();
        form.elements.current_password.focus();
        const request = ++opening;
        try {
            const response = await fetch(endpoint, {credentials: 'same-origin', cache: 'no-store'});
            const data = await response.json();
            if (request !== opening || !modal.open) return;
            if (!response.ok) throw new Error(data.errors?.general || 'Không thể tải biểu mẫu. Vui lòng thử lại.');
            form.elements.csrf_token.value = data.csrf_token;
            submit.disabled = false;
        } catch (error) {
            if (request === opening && modal.open) notice(error.message);
        }
    });
    function close() { if (!busy) modal.close(); }
    modal.querySelectorAll('[data-password-close]').forEach(button => button.addEventListener('click', close));
    modal.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    modal.addEventListener('close', () => {
        opening++;
        form.reset();
        if (opener?.closest('#account-dropdown-panel')) document.querySelector('[data-account-toggle]')?.focus();
        else opener?.focus();
    });
    document.getElementById('modal-show-passwords').addEventListener('change', event => {
        ['new_password', 'confirm_password'].forEach(name => form.elements[name].type = event.target.checked ? 'text' : 'password');
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || submit.disabled) return;
        clearErrors();
        busy = true;
        submit.disabled = true;
        submit.textContent = 'Đang cập nhật…';
        modal.querySelectorAll('[data-password-close]').forEach(button => button.disabled = true);
        try {
            const response = await fetch(endpoint, {method: 'POST', body: new FormData(form), credentials: 'same-origin'});
            const data = await response.json();
            if (!response.ok) {
                const errors = data.errors || {general: 'Đổi mật khẩu không thành công.'};
                fields.forEach(name => {
                    if (errors[name]) {
                        form.elements[name].setAttribute('aria-invalid', 'true');
                        form.querySelector('[data-password-error="' + name + '"]').textContent = errors[name];
                    }
                });
                if (errors.general) notice(errors.general);
                const first = fields.find(name => errors[name]);
                if (first) form.elements[first].focus();
                return;
            }
            form.reset();
            hidePasswords();
            form.elements.csrf_token.value = data.csrf_token;
            notice(data.message, true);
        } catch (error) {
            notice('Không nhận được kết quả từ máy chủ. Vui lòng kiểm tra kết nối và thử lại.');
        } finally {
            busy = false;
            submit.disabled = false;
            submit.textContent = 'Đổi mật khẩu';
            modal.querySelectorAll('[data-password-close]').forEach(button => button.disabled = false);
        }
    });
})();
</script>
<?php endif; ?>
