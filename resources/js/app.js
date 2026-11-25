import './bootstrap';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import Chart from 'chart.js/auto';
import './device-capabilities';

window.Chart = Chart;

let networkIndicatorTimer;

const announceNetwork = async () => {
    const indicator = document.querySelector('[data-network-status]');
    if (!indicator) return;
    clearTimeout(networkIndicatorTimer);
    indicator.hidden = false;
    if (!navigator.onLine) { indicator.textContent = 'Sin red'; indicator.dataset.state = 'offline'; return; }
    try {
        const response = await fetch('/pwa/status', { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) throw new Error();
        const state = await response.json();
        indicator.textContent = state.cloud === 'online' ? 'Local y nube conectados' : 'Servidor local disponible';
        indicator.dataset.state = 'online';
        networkIndicatorTimer = setTimeout(() => { indicator.hidden = true; }, 3000);
    } catch {
        indicator.textContent = 'Servidor local no disponible';
        indicator.dataset.state = 'lan-offline';
    }
};

window.addEventListener('online', announceNetwork);
window.addEventListener('offline', announceNetwork);
document.addEventListener('DOMContentLoaded', announceNetwork);

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', async () => {
        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        if (registration.waiting) document.dispatchEvent(new CustomEvent('pwa:update', { detail: registration }));
        registration.addEventListener('updatefound', () => registration.installing?.addEventListener('statechange', () => {
            if (registration.waiting && navigator.serviceWorker.controller) document.dispatchEvent(new CustomEvent('pwa:update', { detail: registration }));
        }));
    });
}

document.addEventListener('submit', event => {
    if (event.target.matches('form[action$="/logout"]')) navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_DATA' });
});
