/* Cookie banner – Kazán Szerviz Kecskemét
   Önálló, függőség nélküli. A választást a localStorage-ban tárolja.
   Beillesztés minden oldalon a </body> elé:  <script src="cookie-banner.js" defer></script>
*/
(function () {
  'use strict';

  var STORAGE_KEY = 'kazan_cookie_consent';
  var PRIVACY_URL = 'adatkezelesi-tajekoztato.html';

  // Ha már döntött a felhasználó, ne jelenjen meg újra.
  try {
    if (localStorage.getItem(STORAGE_KEY)) return;
  } catch (e) { /* localStorage nem elérhető – mutassuk a sávot */ }

  function injectStyles() {
    if (document.getElementById('cookie-banner-styles')) return;
    var css = '' +
      '.cookie-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;' +
      'max-width:560px;margin:0 auto;background:#0F172A;color:#fff;' +
      'border-radius:14px;padding:20px 22px;' +
      'box-shadow:0 12px 40px rgba(0,0,0,.14),0 4px 12px rgba(0,0,0,.08);' +
      'font-family:inherit;line-height:1.55;' +
      'transform:translateY(160%);opacity:0;transition:transform .45s cubic-bezier(.16,1,.3,1),opacity .45s ease;}' +
      '.cookie-banner.is-visible{transform:translateY(0);opacity:1;}' +
      '.cookie-banner__title{font-weight:700;font-size:1.05rem;margin:0 0 6px;}' +
      '.cookie-banner__text{font-size:.92rem;color:#CBD5E1;margin:0 0 16px;}' +
      '.cookie-banner__text a{color:#F97316;text-decoration:underline;}' +
      '.cookie-banner__actions{display:flex;flex-wrap:wrap;gap:10px;}' +
      '.cookie-banner__btn{cursor:pointer;border:none;font:inherit;font-weight:600;' +
      'font-size:.92rem;padding:11px 20px;border-radius:8px;transition:background .2s,transform .15s;}' +
      '.cookie-banner__btn:hover{transform:translateY(-1px);}' +
      '.cookie-banner__btn--accept{background:#F97316;color:#fff;flex:1 1 auto;}' +
      '.cookie-banner__btn--accept:hover{background:#EA580C;}' +
      '.cookie-banner__btn--decline{background:transparent;color:#CBD5E1;border:1px solid #334155;}' +
      '.cookie-banner__btn--decline:hover{background:#1E293B;color:#fff;}' +
      '@media (max-width:480px){.cookie-banner{padding:18px;} .cookie-banner__btn--decline{flex:1 1 auto;}}';
    var style = document.createElement('style');
    style.id = 'cookie-banner-styles';
    style.textContent = css;
    document.head.appendChild(style);
  }

  function store(value) {
    try { localStorage.setItem(STORAGE_KEY, value); } catch (e) {}
  }

  function dismiss(banner) {
    banner.classList.remove('is-visible');
    setTimeout(function () {
      if (banner.parentNode) banner.parentNode.removeChild(banner);
    }, 450);
  }

  function build() {
    injectStyles();

    var banner = document.createElement('div');
    banner.className = 'cookie-banner';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-live', 'polite');
    banner.setAttribute('aria-label', 'Sütik kezelése');
    banner.innerHTML =
      '<p class="cookie-banner__title">Sütiket használunk 🍪</p>' +
      '<p class="cookie-banner__text">Oldalunk sütiket használ a működés biztosításához és a felhasználói élmény javításához. ' +
      'Részletek az <a href="' + PRIVACY_URL + '">Adatkezelési tájékoztatóban</a>.</p>' +
      '<div class="cookie-banner__actions">' +
        '<button type="button" class="cookie-banner__btn cookie-banner__btn--accept">Elfogadom</button>' +
        '<button type="button" class="cookie-banner__btn cookie-banner__btn--decline">Csak a szükségeseket</button>' +
      '</div>';

    document.body.appendChild(banner);

    banner.querySelector('.cookie-banner__btn--accept').addEventListener('click', function () {
      store('accepted');
      dismiss(banner);
    });
    banner.querySelector('.cookie-banner__btn--decline').addEventListener('click', function () {
      store('declined');
      dismiss(banner);
    });

    // Belépő animáció a következő képkockában.
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { banner.classList.add('is-visible'); });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }
})();
