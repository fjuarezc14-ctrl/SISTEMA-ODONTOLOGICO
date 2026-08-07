// Service Worker para habilitar la instalación PWA de MahuDent
self.addEventListener('install', (e) => {
    console.log('[MahuDent SW] Instalado con éxito');
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    console.log('[MahuDent SW] Activo');
});

self.addEventListener('fetch', (e) => {
    // Permite que todas las peticiones sigan su flujo normal por internet.
    // En el futuro, se puede agregar lógica de caché offline aquí.
});
