'use strict';

// CSRF Token aus Meta-Tag
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// Akkordeon
document.addEventListener('click', e => {
    const header = e.target.closest('.accordion-header');
    if (!header || e.target.closest('.handle')) return;

    const body   = header.nextElementSibling;
    const isOpen = body.classList.contains('open');

    document.querySelectorAll('.accordion-header').forEach(h => h.classList.remove('open'));
    document.querySelectorAll('.accordion-body').forEach(b => b.classList.remove('open'));

    if (!isOpen) {
        header.classList.add('open');
        body.classList.add('open');
    }
});

// AJAX Helfer
async function apiPost(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf() },
        body: JSON.stringify(data),
    });
    return res.json();
}

async function apiDelete(url) {
    const res = await fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-Token': csrf() },
    });
    return res.json();
}

// Feed löschen (wird von channel.php aufgerufen)
async function deleteFeed(id, rowEl) {
    if (!confirm('Feed löschen?')) return;
    const data = await apiDelete(`/api/feed/${id}`);
    if (data.success) rowEl.remove();
}

// XSS-sicheres Einfügen von Strings in DOM
function escHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
