'use strict';
document.getElementById('cancel-export')?.addEventListener('click', () => {
    const panel = document.getElementById('export-confirm');
    panel.open = false;
    panel.querySelector('summary').focus();
});
