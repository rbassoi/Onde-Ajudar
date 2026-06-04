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

  // Restore from hash
  const hash = location.hash.replace('#', '');
  if (hash) {
    const btn = document.querySelector(`.avista-tabs .tab-btn[data-tab="${hash}"]`);
    if (btn) btn.click();
  }
}

/* ---- Chip toggle (necessidades) ---- */
function initChips() {
  document.querySelectorAll('.chip[data-toggle="chip"]').forEach(chip => {
    const cb = chip.querySelector('input[type="checkbox"]');
    if (cb && cb.checked) chip.dataset.active = 'true';

    chip.addEventListener('click', () => {
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
  document.querySelectorAll('.avista-card').forEach(card => {
    if (filter === 'all') {
      card.style.display = '';
      return;
    }
    const status = (card.dataset.status || '').toLowerCase();
    if (filter === 'urgentes') {
      card.style.display = status === 'urgente' ? '' : 'none';
    } else if (filter === 'recentes') {
      card.style.display = '';
    } else if (filter === 'atendidos') {
      card.style.display = status === 'atendido' ? '' : 'none';
    } else if (filter === 'pendentes') {
      card.style.display = status === 'pendente' ? '' : 'none';
    }
  });
}

/* ---- Leaflet map (avistamentos) ---- */
function initAvistaMap() {
  const mapEl = document.getElementById('avista-map');
  if (!mapEl || typeof L === 'undefined') return;

  // Lazy-init so the map renders correctly when tab is visible
  let mapInstance = null;

  function doInit() {
    if (mapInstance) return;
    mapInstance = L.map('avista-map').setView([-27.5954, -48.5480], 13); // Florianópolis

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 18,
    }).addTo(mapInstance);

    // Custom marker icon (accent color)
    const accentIcon = L.divIcon({
      className: '',
      html: `<div style="
        width:18px;height:18px;
        background:#cf6a44;
        border:2.5px solid #3a342c;
        border-radius:50% 50% 50% 0;
        transform:rotate(-45deg);
        box-shadow:2px 2px 0 rgba(44,38,32,.3);
      "></div>`,
      iconSize:   [18, 18],
      iconAnchor: [9, 18],
      popupAnchor:[0,-18],
    });

    // Load pins from embedded data
    const pinsEl = document.getElementById('map-pins-data');
    if (pinsEl) {
      try {
        const pins = JSON.parse(pinsEl.textContent);
        pins.forEach(p => {
          if (!p.lat || !p.lng) return;
          L.marker([p.lat, p.lng], { icon: accentIcon })
            .addTo(mapInstance)
            .bindPopup(`
              <div style="font-family:'Patrick Hand',cursive;min-width:200px">
                <strong>${escHtml(p.local || 'Local não informado')}</strong><br>
                <span style="color:#8d8579;font-size:13px">${escHtml(p.pessoas)} pessoa(s) · ${escHtml(p.tempo)}</span><br>
                ${p.status === 'urgente' ? '<span style="background:#cf6a44;color:#fff7ef;border-radius:10px;padding:1px 8px;font-size:12px">urgente</span>' : ''}
                ${p.status === 'atendido' ? '<span style="background:#d9e7d2;border-radius:10px;padding:1px 8px;font-size:12px">✓ atendido</span>' : ''}
              </div>
            `);
        });
      } catch (e) { /* no-op */ }
    }

    // Geolocation button
    const locBtn = document.getElementById('btn-my-location');
    if (locBtn) {
      locBtn.addEventListener('click', () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
          const { latitude: lat, longitude: lng } = pos.coords;
          mapInstance.setView([lat, lng], 15);
          L.circle([lat, lng], { radius: 80, color: '#cf6a44', fillOpacity: .2 }).addTo(mapInstance);
        });
      });
    }
  }

  // If the map tab is already active, init immediately; otherwise wait for click
  if (mapEl.closest('.tab-panel.active')) {
    doInit();
  }
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
