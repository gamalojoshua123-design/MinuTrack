<?php
/**
 * Shared confirmation modal used to replace native browser confirm() dialogs.
 *
 * Include this once near the end of a page that loads assets/css/admin.css
 * (so the global .modal / .btn / .modal-icon styles are available):
 *
 *   <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
 *
 * To confirm a form submit, change:
 *   onsubmit="return confirm('Are you sure?')"
 * to:
 *   onsubmit="return askConfirm(event, 'Are you sure?')"
 *
 * For a submit button with a confirm, change:
 *   onclick="return confirm('Are you sure?')"
 * to:
 *   onclick="return askConfirm(event, 'Are you sure?')"
 *
 * askConfirm() shows the confirm modal and blocks the action until the user
 * confirms, then re-dispatches the original submit (preserving the submit
 * button's name/value so PHP action handlers still work).
 *
 * For a confirm() inside a JS action (not a form submit), use the callback
 * form instead:
 *   if (!confirm('Are you sure?')) { return; }
 *   doThing();
 * becomes:
 *   askConfirmCallback('Are you sure?', function(){ doThing(); });
 *
 * Non-blocking messages use the toast helper (replaces native alert()):
 *   showToastMsg('Please enter a valid quantity', 'error');  // type: error|warning|info
 * Those come after the native alert() has been removed.
 *
 * The helper is named showToastMsg (not showToast) to avoid colliding with
 * cashier/pos.php, which already defines its own showToast(type, title, msg).
 *
 * This file is safe to include multiple times; it only emits once.
 */
if (defined('CONFIRM_MODAL_LOADED')) {
    return;
}
define('CONFIRM_MODAL_LOADED', true);
?>
<div class="modal" id="confirmModal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-body" style="text-align:center;">
            <div class="modal-icon warning"><i class='bx bx-error-circle'></i></div>
            <h3 style="margin:0 0 0.5rem;font-size:1.05rem;color:var(--text-primary);">Please Confirm</h3>
            <p id="confirmModalMessage" style="margin:0;font-size:0.9rem;color:var(--text-secondary);line-height:1.6;word-break:break-word;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn btn-delete" onclick="proceedConfirmModal()">Confirm</button>
        </div>
    </div>
</div>
<div id="toastContainer"></div>
<script>
function askConfirm(event, message) {
    if (event) event.preventDefault();
    var target = event ? (event.target || null) : null;
    var current = event ? (event.currentTarget || null) : null;
    var form = null;
    if (target && target.tagName === 'FORM') {
        form = target;
    } else if (current && current.form) {
        form = current.form;
    } else if (current && current.tagName === 'FORM') {
        form = current;
    }
    window.__confirmForm = form;
    window.__confirmSubmitter = (event && event.submitter)
        ? event.submitter
        : (current && current.tagName !== 'FORM' ? current : null);

    var msg = document.getElementById('confirmModalMessage');
    if (msg) msg.textContent = message;
    var modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'flex';
    return false;
}

function closeConfirmModal() {
    var modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';
}

function askConfirmCallback(message, callback) {
    window.__confirmFrame = callback;
    window.__confirmForm = null;
    window.__confirmSubmitter = null;
    var msg = document.getElementById('confirmModalMessage');
    if (msg) msg.textContent = message;
    var modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'flex';
}

function proceedConfirmModal() {
    var modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';

    var cb = window.__confirmFrame;
    window.__confirmFrame = null;

    var form = window.__confirmForm;
    var submitter = window.__confirmSubmitter;
    window.__confirmForm = null;
    window.__confirmSubmitter = null;

    if (cb) { cb(); return; }
    if (!form) return;

    form.onsubmit = null;
    if (submitter) submitter.onclick = null;

    if (submitter && typeof form.requestSubmit === 'function') {
        try { form.requestSubmit(submitter); return; } catch (e) { /* fall through */ }
    }
    form.submit();
}

function showToastMsg(message, type) {
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:2000;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;max-width:90vw;';
        document.body.appendChild(container);
    }
    var styles = {
        error:   { bg: '#fef2f2', border: '#fecaca', text: '#b91c1c', icon: '&#9888;&#65039;' },
        warning: { bg: '#fffbeb', border: '#fde68a', text: '#92400e', icon: '&#9888;&#65039;' },
        info:    { bg: '#eff6ff', border: '#bfdbfe', text: '#1e40af', icon: '&#8505;&#65039;' }
    };
    var t = styles[type] || styles.info;
    var toast = document.createElement('div');
    toast.style.cssText = 'display:flex;align-items:flex-start;gap:0.6rem;background:' + t.bg + ';border:1px solid ' + t.border + ';color:' + t.text + ';padding:0.7rem 1rem;border-radius:10px;font-size:0.85rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.12);pointer-events:auto;max-width:340px;';
    var icon = document.createElement('span');
    icon.style.cssText = 'font-size:1rem;line-height:1;';
    icon.innerHTML = t.icon;
    var text = document.createElement('span');
    text.style.cssText = 'line-height:1.4;';
    text.textContent = message;
    toast.appendChild(icon);
    toast.appendChild(text);
    container.appendChild(toast);
    setTimeout(function () {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
    }, 4000);
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.__pendingToast) {
        showToastMsg(window.__pendingToast.msg, window.__pendingToast.type || 'info');
        window.__pendingToast = null;
    }
});
</script>
