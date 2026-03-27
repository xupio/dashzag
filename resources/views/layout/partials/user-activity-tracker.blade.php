@auth
  <script>
    (function () {
      if (!window.navigator || !window.navigator.sendBeacon) {
        return;
      }

      var startedAt = Date.now();
      var sent = false;

      function sendPageVisit() {
        if (sent) {
          return;
        }

        var durationSeconds = Math.max(1, Math.round((Date.now() - startedAt) / 1000));
        var formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('meta[name="_token"]')?.getAttribute('content') || '');
        formData.append('path', window.location.pathname + window.location.search);
        formData.append('route_name', @json(optional(request()->route())->getName()));
        formData.append('page_title', document.title || '');
        formData.append('seconds_spent', durationSeconds.toString());

        sent = navigator.sendBeacon(@json(route('dashboard.activity.page-visit')), formData);
      }

      window.addEventListener('pagehide', sendPageVisit);
      window.addEventListener('beforeunload', sendPageVisit);
    })();
  </script>
@endauth
