(function () {
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !window.Notification) {
    return;
  }

  var keyMeta = document.querySelector('meta[name="vapid-public-key"]');
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  if (!keyMeta || !keyMeta.content) {
    return;
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) {
      output[i] = raw.charCodeAt(i);
    }
    return output;
  }

  function csrf() {
    return csrfMeta ? csrfMeta.content : '';
  }

  function postSubscription(sub) {
    var json = sub.toJSON();
    var encoding = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
    return fetch('/push/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        endpoint: json.endpoint,
        key: json.keys && json.keys.p256dh,
        token: json.keys && json.keys.auth,
        encoding: encoding
      })
    });
  }

  function subscribe() {
    return navigator.serviceWorker.register('/push-sw.js').then(function () {
      return navigator.serviceWorker.ready;
    }).then(function (registration) {
      return registration.pushManager.getSubscription().then(function (existing) {
        if (existing) {
          return existing;
        }
        return registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(keyMeta.content)
        });
      });
    }).then(function (sub) {
      if (sub) {
        return postSubscription(sub);
      }
    });
  }

  function trySubscribe(requestPermission) {
    if (Notification.permission === 'denied') {
      return;
    }
    if (Notification.permission === 'granted') {
      return subscribe().catch(function () {});
    }
    if (!requestPermission) {
      return;
    }
    return Notification.requestPermission().then(function (permission) {
      if (permission === 'granted') {
        return subscribe();
      }
    }).catch(function () {});
  }

  trySubscribe(false);

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || !target.closest) {
      return;
    }
    if (target.closest('#bellBtn')) {
      trySubscribe(true);
    }
  }, true);
})();
