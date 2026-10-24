function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('open');
    }
});

function showAlert(targetId, type, message) {
    const box = document.getElementById(targetId);
    if (!box) return;
    box.className = 'alert alert-' + type;
    box.textContent = message;
    box.classList.remove('hidden');
}

function clearAlert(targetId) {
    const box = document.getElementById(targetId);
    if (box) box.classList.add('hidden');
}
