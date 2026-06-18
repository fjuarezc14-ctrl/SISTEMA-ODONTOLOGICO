/**
 * toast_alerts.js — MahuDent UI Helpers
 * Sistema de notificaciones premium: Toasts automáticos y Confirmaciones elegantes.
 * 
 * Uso:
 *   showToast('Cita guardada con éxito', 'success');
 *   showToast('No se pudo eliminar', 'error');
 *   showToast('Procesando...', 'info');
 *   showConfirm('¿Seguro que deseas eliminar esta cita?', () => doDelete(), () => {});
 */

// ─── Inyección de estilos base (se llama una sola vez) ───────────────────────
(function injectToastStyles() {
    if (document.getElementById('mahudent-toast-styles')) return;
    const style = document.createElement('style');
    style.id = 'mahudent-toast-styles';
    style.textContent = `
        #mahudent-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .mahudent-toast {
            pointer-events: all;
            min-width: 280px;
            max-width: 380px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 16px;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(12px);
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
        }
        .mahudent-toast.visible {
            transform: translateX(0);
            opacity: 1;
        }
        .mahudent-toast.hiding {
            transform: translateX(120%);
            opacity: 0;
        }
        .mahudent-toast-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .mahudent-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 0 16px;
            animation: toast-progress linear forwards;
        }
        @keyframes toast-progress {
            from { width: 100%; }
            to   { width: 0%; }
        }
        /* Confirm modal */
        #mahudent-confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            z-index: 99998;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        #mahudent-confirm-overlay.visible {
            opacity: 1;
        }
        #mahudent-confirm-box {
            background: white;
            border-radius: 24px;
            padding: 32px 28px 24px;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: 'Montserrat', sans-serif;
            text-align: center;
        }
        #mahudent-confirm-overlay.visible #mahudent-confirm-box {
            transform: scale(1) translateY(0);
        }
    `;
    document.head.appendChild(style);
})();

// ─── Contenedor de Toasts ─────────────────────────────────────────────────────
function _getToastContainer() {
    let container = document.getElementById('mahudent-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'mahudent-toast-container';
        document.body.appendChild(container);
    }
    return container;
}

// ─── showToast ────────────────────────────────────────────────────────────────
/**
 * Muestra una notificación toast auto-desaparecida.
 * @param {string} message   Texto del mensaje
 * @param {'success'|'error'|'warning'|'info'} type  Tipo visual
 * @param {number} duration  Milisegundos antes de desaparecer (default: 3500)
 */
function showToast(message, type = 'success', duration = 3500) {
    const container = _getToastContainer();

    const configs = {
        success: {
            bg: '#ecfdf5',
            border: '#a7f3d0',
            text: '#065f46',
            progress: '#10b981',
            icon: `<svg class="mahudent-toast-icon" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
        },
        error: {
            bg: '#fff1f2',
            border: '#fecdd3',
            text: '#9f1239',
            progress: '#f43f5e',
            icon: `<svg class="mahudent-toast-icon" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`
        },
        warning: {
            bg: '#fffbeb',
            border: '#fde68a',
            text: '#92400e',
            progress: '#f59e0b',
            icon: `<svg class="mahudent-toast-icon" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
        },
        info: {
            bg: '#eff6ff',
            border: '#bfdbfe',
            text: '#1e40af',
            progress: '#3b82f6',
            icon: `<svg class="mahudent-toast-icon" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
        }
    };

    const cfg = configs[type] || configs.info;

    const toast = document.createElement('div');
    toast.className = 'mahudent-toast';
    toast.style.cssText = `background: ${cfg.bg}; border-color: ${cfg.border}; color: ${cfg.text}; position: relative; overflow: hidden;`;
    toast.innerHTML = `
        ${cfg.icon}
        <span style="flex:1; line-height:1.4;">${message}</span>
        <button onclick="this.closest('.mahudent-toast')._hide()" style="background:none;border:none;cursor:pointer;padding:2px;opacity:0.5;color:${cfg.text};font-size:16px;line-height:1;margin-left:4px;">✕</button>
        <div class="mahudent-toast-progress" style="background:${cfg.progress}; animation-duration: ${duration}ms;"></div>
    `;

    // Función de ocultamiento
    toast._hide = function() {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 400);
    };

    container.appendChild(toast);
    // Trigger animation
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('visible')));

    // Auto-hide
    setTimeout(() => toast._hide && toast._hide(), duration);

    return toast;
}

// ─── showConfirm ──────────────────────────────────────────────────────────────
/**
 * Muestra un modal de confirmación premium (SOLO para acciones destructivas/irreversibles).
 * @param {string}   message        Texto de la pregunta
 * @param {Function} onConfirm      Callback si el usuario confirma
 * @param {Function} onCancel       Callback si el usuario cancela (opcional)
 * @param {Object}   options        Opciones de personalización
 */
function showConfirm(message, onConfirm, onCancel = null, options = {}) {
    const {
        confirmText = 'Sí, confirmar',
        cancelText  = 'Cancelar',
        type        = 'danger',     // 'danger' | 'warning' | 'info'
        title       = '¿Estás seguro?'
    } = options;

    const iconMap = {
        danger:  `<div style="width:56px;height:56px;border-radius:50%;background:#fff1f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></div>`,
        warning: `<div style="width:56px;height:56px;border-radius:50%;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>`,
        info:    `<div style="width:56px;height:56px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>`
    };

    const confirmBtnColors = {
        danger:  'background:#f43f5e; color:white;',
        warning: 'background:#f59e0b; color:white;',
        info:    'background:#3b82f6; color:white;'
    };

    // Eliminar overlay previo si existe
    const existing = document.getElementById('mahudent-confirm-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'mahudent-confirm-overlay';
    overlay.innerHTML = `
        <div id="mahudent-confirm-box">
            ${iconMap[type] || iconMap.info}
            <h3 style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:17px;color:#1e293b;margin:0 0 10px;">${title}</h3>
            <p style="font-family:'Montserrat',sans-serif;font-size:13px;color:#64748b;margin:0 0 24px;line-height:1.6;">${message}</p>
            <div style="display:flex;gap:10px;">
                <button id="mahudent-confirm-cancel" style="flex:1;padding:12px;border-radius:12px;border:2px solid #e2e8f0;background:white;font-family:'Montserrat',sans-serif;font-weight:700;font-size:13px;color:#64748b;cursor:pointer;transition:all 0.2s;">${cancelText}</button>
                <button id="mahudent-confirm-ok" style="flex:1;padding:12px;border-radius:12px;border:none;${confirmBtnColors[type] || confirmBtnColors.danger};font-family:'Montserrat',sans-serif;font-weight:700;font-size:13px;cursor:pointer;transition:all 0.2s;">${confirmText}</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => requestAnimationFrame(() => overlay.classList.add('visible')));

    function _close() {
        overlay.classList.remove('visible');
        setTimeout(() => overlay.remove(), 300);
    }

    document.getElementById('mahudent-confirm-ok').onclick = () => {
        _close();
        if (typeof onConfirm === 'function') onConfirm();
    };
    document.getElementById('mahudent-confirm-cancel').onclick = () => {
        _close();
        if (typeof onCancel === 'function') onCancel();
    };
    // Click fuera del box cierra
    overlay.addEventListener('click', (e) => { if (e.target === overlay) { _close(); if (typeof onCancel === 'function') onCancel(); } });
    // Escape cierra
    const escListener = (e) => { if (e.key === 'Escape') { _close(); document.removeEventListener('keydown', escListener); if (typeof onCancel === 'function') onCancel(); } };
    document.addEventListener('keydown', escListener);
}
