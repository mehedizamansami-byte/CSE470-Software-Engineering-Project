// BrandBondhu front-end helpers. Plain JavaScript, no build step needed.
(function () {
  'use strict';

  var meta = function (name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  };
  var csrf = meta('csrf-token');

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
  }

  // ---------- Mobile sidebar ----------
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-toggle-sidebar]')) {
      document.body.classList.toggle('sidebar-open');
    } else if (document.body.classList.contains('sidebar-open') && !e.target.closest('.sidebar')) {
      document.body.classList.remove('sidebar-open');
    }
  });

  // ---------- Confirm before dangerous actions ----------
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      e.preventDefault();
    }
  });

  // ---------- Copy buttons ----------
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    var done = function () {
      var old = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = old; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var area = document.createElement('textarea');
      area.value = text;
      document.body.appendChild(area);
      area.select();
      try { document.execCommand('copy'); } catch (err) { /* ignore */ }
      document.body.removeChild(area);
      done();
    }
  });

  // ---------- Feature 7: notification bell with polling ----------
  var bell = document.querySelector('[data-notif]');
  if (bell) {
    var feedUrl = meta('notifications-feed');
    var panel = bell.querySelector('[data-notif-panel]');
    var list = bell.querySelector('[data-notif-list]');
    var countEl = bell.querySelector('[data-notif-count]');
    var sideCount = document.querySelector('[data-notif-side-count]');
    var lastUnread = null;

    bell.querySelector('[data-notif-toggle]').addEventListener('click', function (e) {
      e.stopPropagation();
      panel.hidden = !panel.hidden;
      if (!panel.hidden) poll();
    });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !e.target.closest('[data-notif]')) panel.hidden = true;
    });

    var toast = function (title, message) {
      var wrap = document.querySelector('[data-toasts]');
      if (!wrap) return;
      var el = document.createElement('div');
      el.className = 'toast';
      el.innerHTML = '<strong>' + escapeHtml(title) + '</strong>' + escapeHtml(message);
      wrap.appendChild(el);
      setTimeout(function () { el.remove(); }, 6000);
    };

    var render = function (data) {
      var unread = data.unread || 0;
      countEl.textContent = unread > 99 ? '99+' : unread;
      countEl.hidden = unread === 0;
      if (sideCount) {
        sideCount.textContent = unread;
        sideCount.hidden = unread === 0;
      }

      if (!data.items || !data.items.length) {
        list.innerHTML = '<p class="muted" style="padding:14px;margin:0">No notifications yet.</p>';
      } else {
        list.innerHTML = data.items.map(function (n) {
          return '<a class="notif-item' + (n.read ? '' : ' unread') + '" href="' + escapeHtml(n.open_url) + '">' +
            '<strong>' + escapeHtml(n.title) + '</strong>' +
            '<span>' + escapeHtml(n.message) + '</span>' +
            '<span>' + escapeHtml(n.time) + '</span></a>';
        }).join('');
      }

      // Show a toast when a new notification arrives while the page is open.
      if (lastUnread !== null && unread > lastUnread && data.items && data.items.length) {
        var newest = data.items[0];
        toast(newest.title, newest.message);
      }
      lastUnread = unread;
    };

    var poll = function () {
      if (!feedUrl) return;
      fetch(feedUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) { if (data) render(data); })
        .catch(function () { /* offline: try again next time */ });
    };

    poll();
    setInterval(poll, 20000);
  }

  // ---------- Feature 6: payment method tabs ----------
  document.querySelectorAll('[data-pay-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      var method = tab.getAttribute('data-pay-tab');
      document.querySelectorAll('[data-pay-tab]').forEach(function (t) { t.classList.toggle('active', t === tab); });
      document.querySelectorAll('[data-pay-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-pay-panel') !== method; });
    });
  });

  // Card number spacing: 4242424242424242 -> 4242 4242 4242 4242
  document.querySelectorAll('[data-card-number]').forEach(function (input) {
    input.addEventListener('input', function () {
      var digits = input.value.replace(/\D/g, '').slice(0, 19);
      input.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });
  });

  // ---------- Feature 10: AI caption generator ----------
  var gen = document.querySelector('[data-caption-generator]');
  if (gen) {
    var button = gen.querySelector('[data-generate]');
    var results = gen.querySelector('[data-results]');
    var creditsEl = gen.querySelector('[data-credits]');
    var captionField = document.querySelector('#caption');
    var hashtagField = document.querySelector('#hashtags');
    var titleField = document.querySelector('#title');
    var topicField = gen.querySelector('[name="ai_topic"]');

    if (titleField && topicField) {
      titleField.addEventListener('input', function () {
        if (!topicField.dataset.touched) topicField.value = titleField.value;
      });
      topicField.addEventListener('input', function () { topicField.dataset.touched = '1'; });
    }

    button.addEventListener('click', function () {
      var value = function (name) { var el = gen.querySelector('[name="' + name + '"]'); return el ? el.value : ''; };
      var emojis = gen.querySelector('[name="ai_emojis"]');
      var platform = document.querySelector('[name="platform"]');

      if (!value('ai_topic').trim()) {
        results.innerHTML = '<div class="alert alert-warn">Write what the post is about first.</div>';
        return;
      }

      button.disabled = true;
      button.textContent = 'Generating...';
      results.innerHTML = '';

      fetch(gen.getAttribute('data-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({
          topic: value('ai_topic'),
          details: value('ai_details'),
          tone: value('ai_tone'),
          language: value('ai_language'),
          platform: platform ? platform.value : 'facebook',
          emojis: emojis && emojis.checked ? 1 : 0
        })
      })
        .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
        .then(function (res) {
          if (!res.ok) {
            var msg = res.body.message || 'Could not generate captions.';
            if (res.body.errors) msg = Object.values(res.body.errors).map(function (e) { return e[0]; }).join(' ');
            results.innerHTML = '<div class="alert alert-error">' + escapeHtml(msg) + '</div>';
            return;
          }
          if (typeof res.body.credits !== 'undefined') {
            if (creditsEl) creditsEl.textContent = res.body.credits;
            var sideCredits = document.querySelector('[data-sidebar-credits]');
            if (sideCredits) sideCredits.textContent = res.body.credits;
          }
          var tags = (res.body.hashtags || []).join(' ');
          results.innerHTML = res.body.captions.map(function (c, i) {
            return '<div class="caption-result"><div>' + escapeHtml(c) + '</div>' +
              '<div class="row"><button type="button" class="btn btn-sm" data-use="' + i + '">Use this caption</button></div></div>';
          }).join('') + '<p class="help mt">Suggested hashtags: ' + escapeHtml(tags) + '</p>';

          results.querySelectorAll('[data-use]').forEach(function (b) {
            b.addEventListener('click', function () {
              if (captionField) captionField.value = res.body.captions[parseInt(b.getAttribute('data-use'), 10)];
              if (hashtagField && !hashtagField.value.trim()) hashtagField.value = tags;
              b.textContent = 'Added to the post';
            });
          });
        })
        .catch(function () {
          results.innerHTML = '<div class="alert alert-error">Network error. Please try again.</div>';
        })
        .finally(function () {
          button.disabled = false;
          button.textContent = 'Generate captions';
        });
    });
  }

  // ---------- Post form: hide schedule fields for drafts ----------
  var statusSelect = document.querySelector('[data-post-status]');
  if (statusSelect) {
    var toggleSchedule = function () {
      var box = document.querySelector('[data-schedule-fields]');
      if (box) box.style.display = statusSelect.value === 'scheduled' ? '' : 'none';
    };
    statusSelect.addEventListener('change', toggleSchedule);
    toggleSchedule();
  }

  // ---------- Live colour preview on the brand kit edit page ----------
  document.querySelectorAll('[data-color-input]').forEach(function (input) {
    var text = document.querySelector('[data-color-text="' + input.getAttribute('data-color-input') + '"]');
    input.addEventListener('input', function () { if (text) text.value = input.value.toUpperCase(); });
    if (text) text.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(text.value)) input.value = text.value; });
  });
})();
