'use strict';
const typeSelect = document.getElementById('report-type');
const help = document.getElementById('type-help');
const descriptions = {
    truong: 'Nhóm kết quả theo trường THPT. Chọn một trường hoặc giữ tất cả trường để so sánh.',
    khuvuc: 'Nhóm kết quả theo khu vực của trường THPT. Chọn khu vực cần thống kê; có thể kết hợp trường và nguyện vọng.',
    ketqua: 'Nhóm theo kết quả của từng nguyện vọng. Chọn kết quả cần xem hoặc giữ tất cả kết quả.'
};
function updateCriteria() {
    help.textContent = descriptions[typeSelect.value] || 'Vui lòng chọn loại thống kê.';
    document.querySelectorAll('[data-filter]').forEach(label => {
        label.dataset.priority = String(label.dataset.filter === typeSelect.value);
    });
}
typeSelect.addEventListener('change', updateCriteria);
updateCriteria();
document.getElementById('cancel-export')?.addEventListener('click', () => {
    const panel = document.getElementById('export-confirm');
    panel.open = false;
    panel.querySelector('summary').focus();
});
