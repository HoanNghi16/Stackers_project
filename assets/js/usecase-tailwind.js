// Shared presentation helpers for the three Tailwind-based pages.
// Business actions (review, submit, filtering, Excel demo) remain in their PHP pages.
const ucAlertBase = 'alert my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold';
const ucAlertVariants = {
    info: 'border-blue-200 bg-blue-50 text-blue-800',
    success: 'border-green-200 bg-green-50 text-green-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    danger: 'border-red-200 bg-red-50 text-red-800'
};

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('click', function (event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

function showAlert(targetId, type, message) {
    const box = document.getElementById(targetId);
    if (!box) return;
    box.className = ucAlertBase + ' ' + (ucAlertVariants[type] || ucAlertVariants.info);
    box.textContent = message;
    box.classList.remove('hidden');
}

function clearAlert(targetId) {
    const box = document.getElementById(targetId);
    if (box) box.classList.add('hidden');
}
