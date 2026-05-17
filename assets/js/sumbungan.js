/**
 * Sumbungan — shared client behaviors
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-toggle="dropdown-s"]');
        if (trigger) {
            e.preventDefault();
            const target = document.querySelector(trigger.getAttribute('data-target'));
            if (target) {
                document.querySelectorAll('.s-dropdown.is-open').forEach(function (d) {
                    if (d !== target) d.classList.remove('is-open');
                });
                target.classList.toggle('is-open');
            }
            return;
        }
        if (!e.target.closest('.s-dropdown')) {
            document.querySelectorAll('.s-dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
        }
    });

    document.querySelectorAll('[data-upload]').forEach(function (box) {
        const input = box.querySelector('input[type="file"]');
        const preview = box.querySelector('.s-upload__preview img');
        if (!input || !preview) return;
        box.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                preview.src = ev.target.result;
                box.classList.add('has-preview');
            };
            reader.readAsDataURL(this.files[0]);
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-confirm-link]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const msg = link.getAttribute('data-confirm-link') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    const checkAll = document.querySelector('[data-check-all]');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            const target = this.getAttribute('data-check-all');
            document.querySelectorAll(target).forEach(function (cb) { cb.checked = checkAll.checked; });
        });
    }

    document.querySelectorAll('[data-mark-read]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const id = link.getAttribute('data-mark-read');
            const url = link.getAttribute('data-mark-url') || '';
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            }).then(function () { window.location.reload(); });
        });
    });
})();

window.fetchJson = async function (url, options) {
    const opts = Object.assign({ credentials: 'same-origin' }, options || {});
    const r = await fetch(url, opts);
    const text = await r.text();
    let j;
    try { j = JSON.parse(text); } catch (e) { throw new Error('Invalid JSON response'); }
    if (!j.ok) throw new Error(j.error || ('Request failed (' + r.status + ')'));
    return j.data;
};
