/* Kazán Szerviz Kecskemét – online időpontfoglaló widget.
   A #foglalas[data-service] konténerbe rajzolja a naptárat, idősávokat és az űrlapot.
   A book.php PHP-végponttal kommunikál (JSON). */
(function () {
  'use strict';
  var root = document.getElementById('foglalas');
  if (!root) return;
  var mount = root.querySelector('.bkf-widget');
  if (!mount) return;

  var API = 'book.php';
  var service = root.getAttribute('data-service') || 'karbantartas';
  var HU_MONTHS = ['január', 'február', 'március', 'április', 'május', 'június',
    'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];
  var HU_DAYS = ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'];

  var state = {
    cfg: null, token: '', viewYear: 0, viewMonth: 0,
    selectedDate: '', selectedTime: '', minDate: '', maxDate: '', workDays: []
  };

  function ymd(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function huDate(s) { var d = parseYmd(s); return d.getFullYear() + '. ' + HU_MONTHS[d.getMonth()] + ' ' + d.getDate() + '. (' + HU_DAYS[d.getDay()] + ')'; }

  function el(html) { var t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstChild; }

  function setStep(n) {
    Array.prototype.forEach.call(mount.querySelectorAll('.bkf-step'), function (s, i) {
      s.classList.toggle('is-active', i === (n - 1));
      s.classList.toggle('is-done', i < (n - 1));
    });
  }

  /* ── Naptár ───────────────────────────────────────────────── */
  function renderCalendar() {
    var grid = mount.querySelector('.bkf-cal-grid');
    var label = mount.querySelector('.bkf-cal-label');
    grid.innerHTML = '';
    var first = new Date(state.viewYear, state.viewMonth, 1);
    label.textContent = state.viewYear + '. ' + HU_MONTHS[state.viewMonth];

    var lead = (first.getDay() + 6) % 7; // hétfő-kezdés
    for (var i = 0; i < lead; i++) grid.appendChild(el('<div class="bkf-cell bkf-cell-empty"></div>'));

    var dim = new Date(state.viewYear, state.viewMonth + 1, 0).getDate();
    for (var d = 1; d <= dim; d++) {
      var date = new Date(state.viewYear, state.viewMonth, d);
      var s = ymd(date);
      var dow = date.getDay() === 0 ? 7 : date.getDay();
      var selectable = s >= state.minDate && s <= state.maxDate && state.workDays.indexOf(dow) !== -1;
      var cell = el('<button type="button" class="bkf-cell">' + d + '</button>');
      if (!selectable) { cell.classList.add('is-disabled'); cell.disabled = true; }
      else {
        if (s === state.selectedDate) cell.classList.add('is-selected');
        (function (ds) { cell.addEventListener('click', function () { pickDate(ds); }); })(s);
      }
      grid.appendChild(cell);
    }

    // navigációs gombok tiltása a tartományon kívül
    var prevBtn = mount.querySelector('.bkf-cal-prev');
    var nextBtn = mount.querySelector('.bkf-cal-next');
    var minM = parseYmd(state.minDate); var maxM = parseYmd(state.maxDate);
    prevBtn.disabled = (state.viewYear < minM.getFullYear()) ||
      (state.viewYear === minM.getFullYear() && state.viewMonth <= minM.getMonth());
    nextBtn.disabled = (state.viewYear > maxM.getFullYear()) ||
      (state.viewYear === maxM.getFullYear() && state.viewMonth >= maxM.getMonth());
  }

  function shiftMonth(delta) {
    var m = state.viewMonth + delta;
    state.viewYear += Math.floor(m / 12);
    state.viewMonth = ((m % 12) + 12) % 12;
    renderCalendar();
  }

  /* ── Idősávok ─────────────────────────────────────────────── */
  function pickDate(dateStr) {
    state.selectedDate = dateStr;
    state.selectedTime = '';
    renderCalendar();
    var box = mount.querySelector('.bkf-slots');
    box.innerHTML = '<p class="bkf-loading">Szabad időpontok betöltése…</p>';
    mount.querySelector('.bkf-slotwrap').hidden = false;
    mount.querySelector('.bkf-slot-date').textContent = huDate(dateStr);
    setStep(2);

    fetch(API + '?action=slots&date=' + encodeURIComponent(dateStr), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.token) state.token = data.token;
        box.innerHTML = '';
        var free = (data.slots || []).filter(function (s) { return s.available; });
        if (!free.length) {
          box.appendChild(el('<p class="bkf-noslot">Erre a napra nincs szabad időpont. Válasszon másik napot, vagy hívjon: <a href="tel:+36302605756">+36 30 260 57 56</a></p>'));
          return;
        }
        (data.slots || []).forEach(function (s) {
          var b = el('<button type="button" class="bkf-slot">' + s.time + '</button>');
          if (!s.available) { b.classList.add('is-taken'); b.disabled = true; b.title = 'Foglalt'; }
          else b.addEventListener('click', function () { pickSlot(s.time, b); });
          box.appendChild(b);
        });
      })
      .catch(function () {
        box.innerHTML = '<p class="bkf-noslot">Hiba történt a betöltéskor. Kérjük, próbálja újra, vagy hívjon telefonon.</p>';
      });
  }

  function pickSlot(time, btn) {
    state.selectedTime = time;
    Array.prototype.forEach.call(mount.querySelectorAll('.bkf-slot'), function (s) { s.classList.remove('is-selected'); });
    btn.classList.add('is-selected');
    var form = mount.querySelector('.bkf-formwrap');
    form.hidden = false;
    mount.querySelector('.bkf-chosen').textContent = huDate(state.selectedDate) + ' – ' + time;
    setStep(3);
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /* ── Beküldés ─────────────────────────────────────────────── */
  function submit(e) {
    e.preventDefault();
    var form = e.target;
    var msg = mount.querySelector('.bkf-formmsg');
    var btn = form.querySelector('button[type=submit]');
    msg.textContent = ''; msg.className = 'bkf-formmsg';

    if (!state.selectedDate || !state.selectedTime) {
      msg.textContent = 'Kérjük, válasszon napot és időpontot.'; msg.classList.add('is-err'); return;
    }
    var fd = new FormData(form);
    fd.append('token', state.token);
    fd.append('service', service);
    fd.append('date', state.selectedDate);
    fd.append('time', state.selectedTime);

    btn.disabled = true; btn.textContent = 'Küldés…';
    fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) { showSuccess(data); }
        else {
          msg.textContent = data.error || 'Nem sikerült a foglalás.'; msg.classList.add('is-err');
          btn.disabled = false; btn.textContent = 'Foglalás elküldése';
        }
      })
      .catch(function () {
        msg.textContent = 'Hálózati hiba. Kérjük, próbálja újra.'; msg.classList.add('is-err');
        btn.disabled = false; btn.textContent = 'Foglalás elküldése';
      });
  }

  function showSuccess(data) {
    mount.innerHTML =
      '<div class="bkf-success">' +
      '<div class="bkf-success-ico"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
      '<h3>Köszönjük a foglalást!</h3>' +
      '<p class="bkf-success-when">' + data.service + '<br><strong>' + huDate(data.date) + ' – ' + data.time + '</strong></p>' +
      '<p>' + (data.message || 'Hamarosan telefonon visszaigazoljuk az időpontot.') + '</p>' +
      '<a class="btn-primary" href="tel:+36302605756" style="justify-content:center;margin-top:8px">Kérdése van? Hívjon: +36 30 260 57 56</a>' +
      '</div>';
  }

  /* ── Indítás ──────────────────────────────────────────────── */
  function boot() {
    fetch(API + '?action=config', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (cfg) {
        state.cfg = cfg;
        state.token = cfg.token || '';
        state.minDate = cfg.minDate;
        state.maxDate = cfg.maxDate;
        state.workDays = cfg.workDays || [1, 2, 3, 4, 5, 6];
        var start = parseYmd(cfg.minDate);
        state.viewYear = start.getFullYear();
        state.viewMonth = start.getMonth();
        buildShell();
        renderCalendar();
      })
      .catch(function () {
        mount.innerHTML = '<p class="bkf-noslot">A foglaló jelenleg nem elérhető. Kérjük, hívjon: <a href="tel:+36302605756">+36 30 260 57 56</a></p>';
      });
  }

  function buildShell() {
    mount.innerHTML =
      '<div class="bkf-steps">' +
      '  <div class="bkf-step is-active"><span>1</span> Nap</div>' +
      '  <div class="bkf-step"><span>2</span> Időpont</div>' +
      '  <div class="bkf-step"><span>3</span> Adatok</div>' +
      '</div>' +
      '<div class="bkf-cal">' +
      '  <div class="bkf-cal-nav">' +
      '    <button type="button" class="bkf-cal-prev" aria-label="Előző hónap">‹</button>' +
      '    <span class="bkf-cal-label"></span>' +
      '    <button type="button" class="bkf-cal-next" aria-label="Következő hónap">›</button>' +
      '  </div>' +
      '  <div class="bkf-cal-head"><span>H</span><span>K</span><span>Sze</span><span>Cs</span><span>P</span><span>Szo</span><span>V</span></div>' +
      '  <div class="bkf-cal-grid"></div>' +
      '</div>' +
      '<div class="bkf-slotwrap" hidden>' +
      '  <h4>Szabad időpontok – <span class="bkf-slot-date"></span></h4>' +
      '  <div class="bkf-slots"></div>' +
      '</div>' +
      '<div class="bkf-formwrap" hidden>' +
      '  <div class="bkf-chosenbar">Kiválasztott időpont: <strong class="bkf-chosen"></strong></div>' +
      '  <form class="bkf-form" novalidate>' +
      '    <div class="bkf-field"><label>Név *</label><input type="text" name="name" required autocomplete="name"></div>' +
      '    <div class="bkf-field"><label>Telefon *</label><input type="tel" name="phone" required autocomplete="tel"></div>' +
      '    <div class="bkf-field"><label>E-mail</label><input type="email" name="email" autocomplete="email"></div>' +
      '    <div class="bkf-field"><label>Cím (ahol a munka lesz)</label><input type="text" name="address" autocomplete="street-address"></div>' +
      '    <div class="bkf-field bkf-field-full"><label>Megjegyzés (kazán típusa, hiba leírása…)</label><textarea name="note" rows="3"></textarea></div>' +
      '    <input type="text" name="company" class="bkf-hp" tabindex="-1" autocomplete="off" aria-hidden="true">' +
      '    <div class="bkf-formmsg"></div>' +
      '    <button type="submit" class="btn-primary bkf-submit">Foglalás elküldése</button>' +
      '    <p class="bkf-note">A foglalás nem jár fizetési kötelezettséggel. Az időpontot telefonon visszaigazoljuk.</p>' +
      '  </form>' +
      '</div>';

    mount.querySelector('.bkf-cal-prev').addEventListener('click', function () { shiftMonth(-1); });
    mount.querySelector('.bkf-cal-next').addEventListener('click', function () { shiftMonth(1); });
    mount.querySelector('.bkf-form').addEventListener('submit', submit);
  }

  boot();
})();
