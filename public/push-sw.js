self.addEventListener('push', function (event) {
  var payload = { title: 'Kidzio', body: '', icon: '/assets/images/icon.png', data: { url: '/' } };
  try {
    if (event.data) {
      payload = Object.assign(payload, event.data.json());
    }
  } catch (e) {}
  var data = payload.data || {};
  event.waitUntil(self.registration.showNotification(payload.title || 'Kidzio', {
    body: payload.body || '',
    icon: payload.icon || '/assets/images/icon.png',
    badge: payload.badge || '/assets/images/icon.png',
    data: { url: data.url || '/' }
  }));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
    for (var i = 0; i < clientList.length; i++) {
      var client = clientList[i];
      if (client.url === url && 'focus' in client) {
        return client.focus();
      }
    }
    return clients.openWindow(url);
  }));
});
