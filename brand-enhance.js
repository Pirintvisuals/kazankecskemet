/* ───────────────────────────────────────────────────────────────
   Márkaoldal-finomítások: márkaszín + hero-jelvény + görgetés-mozgás.
   Egy fájl, ami minden márka- és szolgáltatásoldalon fut; a márkát az
   URL-ből ismeri fel, így a 36 aloldalt nem kell egyenként szerkeszteni.
   ─────────────────────────────────────────────────────────────── */
(function () {
  'use strict';

  var BRANDS = {
    ariston:  { name: 'Ariston',  color: '#C8102E', logo: 'ariston.png'  },
    baxi:     { name: 'Baxi',     color: '#005EB8' },
    biasi:    { name: 'Biasi',    color: '#E2001A' },
    bosch:    { name: 'Bosch',    color: '#E20015', logo: 'bosch.png'     },
    hajdu:    { name: 'Hajdu',    color: '#00529B' },
    immergas: { name: 'Immergas', color: '#D2001C' },
    viessmann:{ name: 'Viessmann',color: '#E2401E', logo: 'viessmann.png' },
    westen:   { name: 'Westen',   color: '#0096D6' },
    wolf:     { name: 'Wolf',     color: '#E2001A' }
  };

  var root = document.documentElement;
  var path = location.pathname.toLowerCase();
  var m = path.match(/(ariston|baxi|biasi|bosch|hajdu|immergas|viessmann|westen|wolf)-kazan-/);
  var brand = m ? BRANDS[m[1]] : null;

  if (brand) {
    root.style.setProperty('--brand', brand.color);
  }

  function onReady(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  onReady(function () {
    // ── Márka-jelvény a hero tetejére ──
    if (brand) {
      document.body.classList.add('has-brand');
      var holder = document.querySelector('.svc-hero .container');
      if (holder && !holder.querySelector('.brand-hero-badge')) {
        var badge = document.createElement('div');
        badge.className = 'brand-hero-badge';
        if (brand.logo) {
          badge.innerHTML = '<img src="k%C3%A9pek/logok/' + brand.logo + '" alt="' + brand.name + ' logó" />'
                          + '<span class="bhb-label">szakszerviz</span>';
        } else {
          badge.innerHTML = '<span class="bhb-word">' + brand.name + '</span>'
                          + '<span class="bhb-label">szakszerviz</span>';
        }
        holder.insertBefore(badge, holder.firstChild);
      }
    }

    // ── Görgetésre megjelenő mozgás ──
    root.classList.add('reveal-ready');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) return;

    var selectors = [
      '.svc-prose h2', '.svc-prose > p', '.svc-checklist', '.svc-callout',
      '.svc-step', '.svc-faq', '.svc-aside .svc-card', '.svc-related h2',
      '.svc-rel-card', '.svc-brandlinks .svc-brandlink',
      '.brands-hub-grid > *', '.blog-featured', '.blog-card'
    ];
    // Csak a hajtás alatti elemeket animáljuk – ami már látszik, az ne villanjon.
    var vh = window.innerHeight || document.documentElement.clientHeight;
    var seen = [], toObserve = [];
    selectors.forEach(function (s) {
      document.querySelectorAll(s).forEach(function (el) {
        if (seen.indexOf(el) !== -1) return;
        seen.push(el);
        if (el.getBoundingClientRect().top < vh * 0.92) return; // már látható
        el.classList.add('reveal-up');
        toObserve.push(el);
      });
    });

    // finom késleltetés a rács-szerű elemeknél (lépcsőzött belépés)
    ['.svc-rel-card', '.brands-hub-grid > *', '.blog-card', '.svc-step', '.svc-brandlinks .svc-brandlink']
      .forEach(function (s) {
        var i = 0;
        document.querySelectorAll(s).forEach(function (el) {
          if (el.classList.contains('reveal-up')) { el.style.transitionDelay = ((i % 4) * 70) + 'ms'; i++; }
        });
      });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

    toObserve.forEach(function (el) { io.observe(el); });
  });
})();
