/* =============================================================
   app.js — Frontend interactivity
   Sistema de Moradores de Rua
   ============================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initChips();
  initCounters();
  initAlerts();
  initAvistaMap();
  initFilterbar();
  initFormValidation();
});

/* ---- Tab navigation ---- */
function initTabs() {
  document.querySelectorAll('.avista-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;

      document.querySelectorAll('.avista-tabs .tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      const panel = document.getElementById('tab-' + target);
      if (panel) {
        panel.classList.add('active');
        // Initialise map when its tab becomes visible
        if (target === 'mapa' && window._avistaMapInit) {
          window._avistaMapInit();
        }
      }

      // Persist active tab in URL hash
      history.replaceState(null, '', '#' + target);
    });
  });

  // Restore from hash on load
  const hash = location.hash.replace('#', '');
  if (hash) {
    const btn = document.querySelector(`.avista-tabs .tab-btn[data-tab="${hash}"]`);
    if (btn) btn.click();
  }

  // Handle brand link or any same-page hash navigation
  window.addEventListener('hashchange', () => {
    const h = location.hash.replace('#', '');
    if (!h) return;
    const btn = document.querySelector(`.avista-tabs .tab-btn[data-tab="${h}"]`);
    if (btn) btn.click();
  });
}

/* ---- Chip toggle (necessidades) ---- */
function initChips() {
  document.querySelectorAll('.chip[data-toggle="chip"]').forEach(chip => {
    const cb = chip.querySelector('input[type="checkbox"]');
    if (cb && cb.checked) chip.dataset.active = 'true';

    chip.addEventListener('click', (e) => {
      e.preventDefault(); // prevent browser auto-toggling the hidden checkbox a second time
      const isActive = chip.dataset.active === 'true';
      chip.dataset.active = isActive ? 'false' : 'true';
      if (cb) cb.checked = !isActive;
    });
  });
}

/* ---- Person counter ---- */
function initCounters() {
  document.querySelectorAll('.counter').forEach(counter => {
    const valEl  = counter.querySelector('.val');
    const hidden = counter.querySelector('input[type="hidden"]');
    const btnDec = counter.querySelector('.dec');
    const btnInc = counter.querySelector('.inc');
    if (!valEl || !btnDec || !btnInc) return;

    let val = parseInt(valEl.textContent) || 1;

    function update(n) {
      val = Math.max(1, Math.min(99, n));
      valEl.textContent = val;
      if (hidden) hidden.value = val;
    }

    btnDec.addEventListener('click', () => update(val - 1));
    btnInc.addEventListener('click', () => update(val + 1));
  });
}

/* ---- Auto-dismiss alerts ---- */
function initAlerts() {
  document.querySelectorAll('.alert[data-dismiss="auto"]').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  });
}

/* ---- Filterbar (Feed) ---- */
function initFilterbar() {
  document.querySelectorAll('.filterbar .f').forEach(btn => {
    btn.addEventListener('click', () => {
      const bar = btn.closest('.filterbar');
      bar.querySelectorAll('.f').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.filter || 'all';
      filterCards(filter);
    });
  });
}

function filterCards(filter) {
  const estadoId = (document.getElementById('fil-estado')?.value || '');
  const cidadeId = (document.getElementById('fil-cidade')?.value || '');
  const bairro   = (document.getElementById('fil-bairro')?.value || '').toLowerCase();

  document.querySelectorAll('.avista-card').forEach(card => {
    let show = true;

    // Filtro de status
    if (filter !== 'all') {
      const status = (card.dataset.status || '').toLowerCase();
      if (filter === 'urgentes'  && status !== 'urgente')  show = false;
      if (filter === 'pendentes' && status !== 'pendente') show = false;
      if (filter === 'atendidos' && status !== 'atendido') show = false;
    }

    // Filtros de localização
    if (show && estadoId && (card.dataset.estadoId || '') !== estadoId) show = false;
    if (show && cidadeId && (card.dataset.cidadeId || '') !== cidadeId) show = false;
    if (show && bairro   && (card.dataset.bairro   || '') !== bairro)   show = false;

    card.style.display = show ? '' : 'none';
  });
}

/* ---- Leaflet map (avistamentos) ---- */
function initAvistaMap() {
  const mapEl = document.getElementById('avista-map');
  if (!mapEl || typeof L === 'undefined') return;

  let mapInstance  = null;
  let userMarker   = null;
  let userCircle   = null;
  let clusterGroup = null;
  let heatLayer    = null;
  let showHeat     = false;
  let allPins      = [];

  function doInit() {
    if (mapInstance) return;

    // Start centered on Brazil; user location will re-center
    mapInstance = L.map('avista-map', { zoomControl: true }).setView([-14.235, -51.925], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(mapInstance);

    // Load pins into outer-scope variable so placeUser can access them
    try { allPins = JSON.parse(document.getElementById('map-pins-data')?.textContent || '[]'); } catch(e) {}

    const statusColor = { urgente: '#cf6a44', pendente: '#c9a84c', atendido: '#5a9e4a' };

    // Build marker cluster
    if (typeof L.markerClusterGroup === 'function') {
      clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        iconCreateFunction(cluster) {
          const n   = cluster.getChildCount();
          const cls = n >= 20 ? 'av-cl-lg' : n >= 5 ? 'av-cl-md' : 'av-cl-sm';
          return L.divIcon({ className: '', html: `<div class="av-cluster ${cls}">${n}</div>`, iconSize: [36, 36] });
        },
      });
    }

    allPins.forEach(p => {
      if (!p.lat || !p.lng) return;
      const color = statusColor[p.status] || '#cf6a44';
      const icon  = L.divIcon({
        className: '',
        html: `<div style="width:12px;height:12px;background:${color};border:2px solid rgba(0,0,0,.4);border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:1px 1px 3px rgba(0,0,0,.3)"></div>`,
        iconSize: [12, 12], iconAnchor: [6, 12], popupAnchor: [0, -12],
      });
      const popup = `<div style="font-family:Inter,sans-serif;min-width:180px">
        <strong>${escHtml(p.local || 'Local não informado')}</strong><br>
        <span style="color:#8d8579;font-size:12px">${escHtml(String(p.pessoas))} pessoa(s) · ${escHtml(p.tempo)}</span><br>
        ${p.status === 'urgente' ? '<span style="background:#cf6a44;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px">urgente</span>' : ''}
        ${p.status === 'atendido' ? '<span style="background:#d9e7d2;border-radius:10px;padding:1px 8px;font-size:11px">✓ atendido</span>' : ''}
      </div>`;
      const marker = L.marker([p.lat, p.lng], { icon }).bindPopup(popup);
      if (clusterGroup) clusterGroup.addLayer(marker);
      else marker.addTo(mapInstance);
    });
    if (clusterGroup) mapInstance.addLayer(clusterGroup);

    // Heatmap (urgentes têm peso maior)
    if (typeof L.heatLayer === 'function' && allPins.length > 0) {
      const heatData = allPins.map(p => [p.lat, p.lng, p.status === 'urgente' ? 1.0 : p.status === 'pendente' ? 0.6 : 0.3]);
      heatLayer = L.heatLayer(heatData, {
        radius: 35, blur: 25, maxZoom: 14,
        gradient: { 0.3: '#5a9e4a', 0.6: '#c9a84c', 1.0: '#cf6a44' },
      });
    }

    // Toggle heat button
    const toggleBtn = document.getElementById('btn-toggle-heat');
    if (toggleBtn) {
      if (!heatLayer) { toggleBtn.style.display = 'none'; }
      else {
        toggleBtn.addEventListener('click', () => {
          showHeat = !showHeat;
          if (showHeat) {
            if (clusterGroup) mapInstance.removeLayer(clusterGroup);
            heatLayer.addTo(mapInstance);
            toggleBtn.innerHTML = '📍 Marcadores';
          } else {
            mapInstance.removeLayer(heatLayer);
            if (clusterGroup) clusterGroup.addTo(mapInstance);
            toggleBtn.innerHTML = '🔥 Mapa de calor';
          }
        });
      }
    }

    // My-location button: recenter
    document.getElementById('btn-my-location')?.addEventListener('click', () => locateUser(false));

    // Auto-locate on map open
    locateUser(true);
  }

  function locateUser(autoCenter) {
    if (!mapInstance || !navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
      pos => placeUser(pos.coords.latitude, pos.coords.longitude, autoCenter),
      ()  => { if (autoCenter) mapInstance.setView([-15.78, -47.93], 5); }, // fallback: centro do Brasil
      { timeout: 8000, maximumAge: 60000 }
    );
  }

  function placeUser(lat, lng, center) {
    if (!mapInstance) return;
    if (userMarker) { mapInstance.removeLayer(userMarker); userMarker = null; }
    if (userCircle) { mapInstance.removeLayer(userCircle); userCircle = null; }
    userMarker = L.marker([lat, lng], {
      icon: L.divIcon({
        className: '',
        html: '<div class="av-user-dot"><div class="av-user-pulse"></div></div>',
        iconSize: [20, 20], iconAnchor: [10, 10],
      }),
      zIndexOffset: 2000,
    }).bindPopup('<strong>📍 Você está aqui</strong>').addTo(mapInstance);
    userCircle = L.circle([lat, lng], { radius: 400, color: '#4a90d9', fillOpacity: .06, weight: 1 }).addTo(mapInstance);
    if (center) fitViewToUserAndPins(lat, lng);
    else        mapInstance.setView([lat, lng], 14);
  }

  function fitViewToUserAndPins(userLat, userLng) {
    const userLL = L.latLng(userLat, userLng);

    // Tenta raios crescentes até encontrar avistamentos próximos
    const radii = [30000, 100000, 500000];
    let nearby = [];
    for (const r of radii) {
      nearby = allPins.filter(p => p.lat && p.lng && userLL.distanceTo(L.latLng(p.lat, p.lng)) <= r);
      if (nearby.length > 0) break;
    }

    if (nearby.length === 0) {
      // Sem avistamentos próximos: mostra o Brasil todo
      mapInstance.setView([userLat, userLng], 12);
      return;
    }

    const bounds = L.latLngBounds([userLL]);
    nearby.forEach(p => bounds.extend([p.lat, p.lng]));
    mapInstance.fitBounds(bounds.pad(0.25), { maxZoom: 15 });
  }

  if (mapEl.closest('.tab-panel.active')) doInit();
  window._avistaMapInit = doInit;
}

/* ---- Basic form validation ---- */
function initFormValidation() {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', e => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
          field.style.borderColor = '#c0392b';
          field.style.boxShadow   = '0 0 0 3px #fde8e640';
          valid = false;
        } else {
          field.style.borderColor = '';
          field.style.boxShadow   = '';
        }
      });
      if (!valid) {
        e.preventDefault();
        const firstInvalid = form.querySelector('[required]:not([value])');
        if (firstInvalid) firstInvalid.focus();
      }
    });
  });
}

/* ---- Helpers ---- */
function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
