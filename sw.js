// Service Worker para habilitar la instalación PWA de MahuDent
self.addEventListener('install', (e) => {
    console.log('[MahuDent SW] Instalado con éxito');
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    console.log('[MahuDent SW] Activo');
});


