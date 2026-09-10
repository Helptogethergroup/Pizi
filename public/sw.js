const CACHE_NAME = 'pizi-v1';

self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
  // Sirf GET requests ko hi intercept/cache karo — POST/PUT/DELETE (jaise OTP,
  // login, forms) ko seedha browser ko normally handle karne do.
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request).catch(async () => {
      const cached = await caches.match(event.request);
      // Cache khaali ho sakta hai — undefined kabhi respondWith() ko mat do,
      // warna "Failed to convert value to 'Response'" crash hoke navigation
      // hi fail ho jata hai aur browser purana stale page dikha deta hai.
      return cached || Response.error();
    })
  );
});