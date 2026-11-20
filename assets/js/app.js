/* Urbs — comportamento do lado do cliente. Melhoria progressiva: tudo funciona sem JS, exceto o mapa. */
(function () {
  'use strict';

  var body = document.body;
  var BASE = body.dataset.base || '';
  var CSRF = body.dataset.csrf || '';

  // ---------------------------------------------------------------- PWA
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(BASE + '/sw.js?offline=' + encodeURIComponent(body.dataset.offline || '')).catch(function () {});
    });
  }

  // ---------------------------------------------------------------- Confirmações
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    var btn = ev.submitter;
    var msg = (btn && btn.dataset.confirm) || form.dataset.confirm;
    if (msg && !window.confirm(msg)) {
      ev.preventDefault();
      return;
    }
    if (form.dataset.busy === '1') {
      ev.preventDefault();
      window.alert('Aguarde: as imagens ainda estão sendo preparadas.');
      return;
    }
    // Evita envio duplo em formulários com arquivos.
    if (form.enctype === 'multipart/form-data') {
      setTimeout(function () {
        form.querySelectorAll('button[type=submit], button:not([type])').forEach(function (b) {
          b.disabled = true;
          if (b.classList.contains('btn-lg')) b.textContent = 'Enviando…';
        });
      }, 0);
    }
  });

  // Ao sair, apaga as páginas guardadas para leitura offline (celular compartilhado).
  document.querySelectorAll('form[action$="/sair"], form[action*="r=%2Fsair"]').forEach(function (f) {
    f.addEventListener('submit', function () {
      if (window.caches) caches.keys().then(function (keys) { keys.forEach(function (k) { if (/-pages$/.test(k)) caches.delete(k); }); });
    });
  });

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-reload]');
    if (t) location.reload();
  });

  // ---------------------------------------------------------------- Apoio sem recarregar
  document.querySelectorAll('form[data-support]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.fetch) return;
      ev.preventDefault();
      var btn = form.querySelector('button');
      btn.disabled = true;
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
        credentials: 'same-origin'
      }).then(function (r) {
        if (r.redirected || !r.ok) { location.href = r.url || location.href; return null; }
        return r.json();
      }).then(function (data) {
        if (!data) return;
        // O cartão aparece duas vezes (celular e desktop): atualiza todas as cópias.
        document.querySelectorAll('form[data-support] button').forEach(function (b) {
          b.classList.toggle('btn-supported', data.supported);
          b.classList.toggle('btn-primary', !data.supported);
          b.setAttribute('aria-pressed', data.supported ? 'true' : 'false');
          var label = b.querySelector('[data-support-label]');
          if (label) label.textContent = data.supported ? 'Você apoia' : 'Apoiar';
        });
        document.querySelectorAll('[data-support-count]').forEach(function (c) { c.textContent = data.count; });
        document.querySelectorAll('[data-support-noun]').forEach(function (n) { n.textContent = data.count === 1 ? 'apoiador' : 'apoiadores'; });
      }).catch(function () { form.submit(); })
        .finally(function () { btn.disabled = false; });
    });
  });

  // ---------------------------------------------------------------- Compartilhar
  document.querySelectorAll('[data-share]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var data = { title: btn.dataset.title || document.title, url: location.href.split('#')[0] };
      if (navigator.share) {
        navigator.share(data).catch(function () {});
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(data.url).then(function () {
          var old = btn.textContent;
          btn.textContent = 'Link copiado!';
          setTimeout(function () { btn.textContent = old; }, 2000);
        });
      } else {
        window.prompt('Copie o link:', data.url);
      }
    });
  });

  // ---------------------------------------------------------------- Antes/depois
  document.querySelectorAll('[data-before-after]').forEach(function (fig) {
    var range = fig.querySelector('.ba-range');
    var wrap = fig.querySelector('.ba-before-wrap');
    var before = fig.querySelector('.ba-before');
    var handle = fig.querySelector('.ba-handle');
    function sizeBefore() { before.style.width = fig.clientWidth + 'px'; }
    function update() {
      wrap.style.width = range.value + '%';
      handle.style.left = range.value + '%';
    }
    range.addEventListener('input', update);
    window.addEventListener('resize', sizeBefore);
    fig.querySelector('.ba-after').addEventListener('load', sizeBefore);
    sizeBefore();
    update();
  });

  // ---------------------------------------------------------------- Imagens: compressão no aparelho
  var MAX_SIDE = 1920;

  function compressImage(file) {
    if (!/^image\/(jpeg|png|webp|heic|heif)$/i.test(file.type) || !window.createImageBitmap) {
      return Promise.resolve(file);
    }
    return createImageBitmap(file).then(function (bmp) {
      var scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
      if (scale === 1 && file.size < 700 * 1024) return file;
      var canvas = document.createElement('canvas');
      canvas.width = Math.round(bmp.width * scale);
      canvas.height = Math.round(bmp.height * scale);
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(bmp, 0, 0, canvas.width, canvas.height);
      return new Promise(function (resolve) {
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', 0.85);
      });
    }).catch(function () { return file; });
  }

  function renderPreview(input, files) {
    var id = input.dataset.preview;
    var box = id && document.getElementById(id);
    if (!box) return;
    box.innerHTML = '';
    files.forEach(function (f) {
      if (/^image\//.test(f.type)) {
        var img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(f);
        box.appendChild(img);
      } else {
        var row = document.createElement('div');
        row.textContent = '📄 ' + f.name + ' (' + (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB)';
        box.appendChild(row);
      }
    });
  }

  document.querySelectorAll('input[type=file]').forEach(function (input) {
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files || []);
      var max = parseInt(input.dataset.max || '0', 10);
      if (max && files.length > max) {
        window.alert('Você pode enviar no máximo ' + max + ' arquivos aqui. Vamos usar só os ' + max + ' primeiros.');
        files = files.slice(0, max);
      }
      if (!('compress' in input.dataset) || typeof DataTransfer === 'undefined') {
        if (max && input.files.length > max && typeof DataTransfer !== 'undefined') {
          var dt0 = new DataTransfer();
          files.forEach(function (f) { dt0.items.add(f); });
          input.files = dt0.files;
        }
        renderPreview(input, files);
        return;
      }
      var form = input.form;
      if (form) form.dataset.busy = '1';
      Promise.all(files.map(compressImage)).then(function (out) {
        var dt = new DataTransfer();
        out.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        renderPreview(input, out);
      }).finally(function () { if (form) form.dataset.busy = '0'; });
    });
  });

  // ---------------------------------------------------------------- Mapas
  function tileLayer(el) {
    return L.tileLayer(el.dataset.tiles, { maxZoom: 19, attribution: el.dataset.attribution || '' });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function onLeaflet(fn) {
    if (window.L) return fn();
    window.addEventListener('load', function () { if (window.L) fn(); });
  }

  // Mapa principal
  var mapEl = document.getElementById('map');
  if (mapEl) onLeaflet(function () {
    var map = L.map(mapEl, { zoomControl: false }).setView([+mapEl.dataset.lat, +mapEl.dataset.lng], +mapEl.dataset.zoom || 15);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    tileLayer(mapEl).addTo(map);
    var layer = L.layerGroup().addTo(map);
    var filters = document.getElementById('map-filters');
    var timer;

    function load() {
      var b = map.getBounds();
      var params = new URLSearchParams(new FormData(filters));
      params.set('bbox', [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].map(function (n) { return n.toFixed(5); }).join(','));
      var url = mapEl.dataset.api + (mapEl.dataset.api.indexOf('?') >= 0 ? '&' : '?') + params.toString();
      fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (items) {
        layer.clearLayers();
        items.forEach(function (it) {
          var m = L.circleMarker([it.lat, it.lng], {
            radius: it.kind === 'idea' ? 9 : 10,
            color: '#fff', weight: 2.5, fillColor: it.color, fillOpacity: it.status === 'done' ? 0.45 : 0.95
          });
          var html = '<div class="map-popup">' +
            (it.thumb ? '<img src="' + escapeHtml(it.thumb) + '" alt="">' : '') +
            '<strong>' + escapeHtml(it.title) + '</strong>' +
            '<span class="pill status-' + escapeHtml(it.status) + '">' + escapeHtml(it.statusLabel) + '</span>' +
            (it.category ? '<span class="pill">' + escapeHtml(it.category) + '</span>' : '') +
            '<p>' + it.supports + (it.supports === 1 ? ' apoiador' : ' apoiadores') +
            (it.proposals ? ' · ' + it.proposals + (it.proposals === 1 ? ' proposta' : ' propostas') : '') + '</p>' +
            '<a class="btn btn-primary btn-sm btn-block" href="' + escapeHtml(it.url) + '">Ver detalhes</a></div>';
          m.bindPopup(html);
          m.addTo(layer);
        });
      }).catch(function () {});
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(load, 250); }
    map.on('moveend', schedule);
    filters.addEventListener('change', load);
    load();

    // Toque no mapa: oferece registrar naquele ponto.
    map.on('click', function (ev) {
      var u = mapEl.dataset.new + (mapEl.dataset.new.indexOf('?') >= 0 ? '&' : '?') +
        'lat=' + ev.latlng.lat.toFixed(6) + '&lng=' + ev.latlng.lng.toFixed(6);
      L.popup().setLatLng(ev.latlng)
        .setContent('<div class="map-popup"><strong>Registrar algo aqui?</strong><a class="btn btn-primary btn-sm btn-block" href="' + escapeHtml(u) + '">Registrar ocorrência</a></div>')
        .openOn(map);
    });

    var locate = document.getElementById('map-locate');
    var me;
    locate.addEventListener('click', function () {
      if (!navigator.geolocation) return window.alert('Seu navegador não informa a localização.');
      locate.disabled = true;
      navigator.geolocation.getCurrentPosition(function (pos) {
        var ll = [pos.coords.latitude, pos.coords.longitude];
        if (me) me.remove();
        me = L.circleMarker(ll, { radius: 7, color: '#fff', weight: 3, fillColor: '#2563eb', fillOpacity: 1 }).addTo(map);
        map.setView(ll, 17);
        locate.disabled = false;
      }, function () {
        locate.disabled = false;
        window.alert('Não foi possível obter sua localização. Verifique a permissão do navegador.');
      }, { enableHighAccuracy: true, timeout: 12000 });
    });
  });

  // Mini mapa (detalhe da ocorrência)
  var mini = document.getElementById('mini-map');
  if (mini) onLeaflet(function () {
    var ll = [+mini.dataset.lat, +mini.dataset.lng];
    var m = L.map(mini, { zoomControl: false, dragging: !L.Browser.mobile, scrollWheelZoom: false }).setView(ll, 17);
    tileLayer(mini).addTo(m);
    L.marker(ll).addTo(m);
  });

  // Seletor de local (nova ocorrência)
  var picker = document.getElementById('picker');
  if (picker) onLeaflet(function () {
    var latIn = document.getElementById('lat');
    var lngIn = document.getElementById('lng');
    var addr = document.getElementById('address');
    var nearbyBox = document.getElementById('nearby');
    var form = document.getElementById('issue-form');
    var autoAddress = addr.value === '';
    var hasPoint = latIn.value !== '' && lngIn.value !== '';
    var start = hasPoint ? [+latIn.value, +lngIn.value] : [+picker.dataset.lat, +picker.dataset.lng];
    var map = L.map(picker).setView(start, hasPoint ? 18 : (+picker.dataset.zoom || 15));
    tileLayer(picker).addTo(map);
    var marker = null;
    var geoTimer;

    addr.addEventListener('input', function () { autoAddress = addr.value === ''; });

    function setPoint(lat, lng, pan) {
      latIn.value = lat.toFixed(7);
      lngIn.value = lng.toFixed(7);
      if (!marker) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function () { var p = marker.getLatLng(); setPoint(p.lat, p.lng, false); });
      } else {
        marker.setLatLng([lat, lng]);
      }
      if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 17));
      clearTimeout(geoTimer);
      geoTimer = setTimeout(function () { reverse(lat, lng); nearby(lat, lng); }, 600);
    }

    function reverse(lat, lng) {
      if (!autoAddress) return;
      var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&accept-language=pt-BR&lat=' + lat + '&lon=' + lng;
      fetch(url).then(function (r) { return r.json(); }).then(function (d) {
        if (!autoAddress || !d || !d.address) return;
        var a = d.address;
        var parts = [];
        var road = a.road || a.pedestrian || a.footway || a.path;
        if (road) parts.push(road + (a.house_number ? ', ' + a.house_number : ''));
        var hood = a.suburb || a.neighbourhood || a.quarter || a.hamlet;
        if (hood) parts.push(hood);
        addr.value = parts.join(' – ') || (d.display_name || '').split(',').slice(0, 2).join(',');
      }).catch(function () {});
    }

    function nearby(lat, lng) {
      var url = form.dataset.near + (form.dataset.near.indexOf('?') >= 0 ? '&' : '?') + 'r=150&lat=' + lat + '&lng=' + lng;
      fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (items) {
        if (!items.length) { nearbyBox.hidden = true; return; }
        nearbyBox.innerHTML = '<strong>Já existe algo registrado por perto.</strong> Se for o mesmo caso, apoie a publicação existente em vez de criar outra:<ul>' +
          items.map(function (it) {
            return '<li><a href="' + escapeHtml(it.url) + '" target="_blank">' + escapeHtml(it.title) + '</a> (' + it.distance + ' m · ' + escapeHtml(it.status) + ')</li>';
          }).join('') + '</ul>';
        nearbyBox.hidden = false;
      }).catch(function () {});
    }

    if (hasPoint) setPoint(start[0], start[1], false);
    map.on('click', function (ev) { setPoint(ev.latlng.lat, ev.latlng.lng, false); });

    document.getElementById('use-gps').addEventListener('click', function () {
      var btn = this;
      if (!navigator.geolocation) return window.alert('Seu navegador não informa a localização. Toque no mapa para marcar.');
      btn.disabled = true;
      navigator.geolocation.getCurrentPosition(function (pos) {
        btn.disabled = false;
        setPoint(pos.coords.latitude, pos.coords.longitude, true);
      }, function () {
        btn.disabled = false;
        window.alert('Não foi possível obter sua localização. Toque no mapa para marcar o ponto.');
      }, { enableHighAccuracy: true, timeout: 12000 });
    });

    form.addEventListener('submit', function (ev) {
      if (!latIn.value || !lngIn.value) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        window.alert('Marque o local no mapa antes de publicar.');
        picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, true);
  });
})();
