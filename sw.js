self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (e) => {
  // Pass-through fetch for network-first PWA behavior
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});

// =========================================================================
// WEB PUSH NOTIFICATIONS & NATIVE APP SYNC
// =========================================================================
self.addEventListener('push', (e) => {
  let data = { title: 'Fast Site Escrow', body: 'You have a new notification!', url: '/' };
  if (e.data) {
    try { 
      data = e.data.json(); 
    } catch(err) { 
      data.body = e.data.text(); 
    }
  }
  const options = {
    body: data.body,
    icon: '/assets/img/fast site logo only.jpeg',
    badge: '/assets/img/fast site logo only.jpeg',
    data: { url: data.url || '/' },
    vibrate: [100, 50, 100],
    actions: [
      { action: 'open', title: 'Open Fast Site' }
    ]
  };
  e.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const targetUrl = (e.notification.data && e.notification.data.url) ? e.notification.data.url : '/';
  e.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
