self.addEventListener('install', (event) => {
  event.waitUntil(caches.open('hydrox-v1').then((cache) => {
    return cache.addAll([
      '/celular/public/index.php',
      '/celular/assets/styles.css',
      '/celular/assets/app.js'
    ]);
  }));
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((resp) => resp || fetch(event.request))
  );
});
