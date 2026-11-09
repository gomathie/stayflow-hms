/*
 * Lets the site be added to a home screen. It does not store pages or
 * bookings: every request still goes to the server.
 */
self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function () {
});
