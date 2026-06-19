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

  /* Egy releváns YouTube-videó aloldalanként. A kulcs a fájlnév (kiterjesztés
     nélkül). Ahol nincs márka-specifikus videó, ott kategória szerinti
     általános videó kerül be (karbantartás / szerelő / szerviz / beüzemelés). */
  var VIDEOS = {
    'ariston-kazan-karbantartas': 'VGCLxRlDSg4',
    'ariston-kazan-szerviz':      '9jcoLHg_ELU',
    'ariston-kazan-szerelo':      '8IV7oG43DZ0',
    'ariston-kazan-beuzemeles':   'CC5fC3t9-d4',
    'baxi-kazan-karbantartas':    'xKZ_fV1H-14',
    'baxi-kazan-szerviz':         '9jcoLHg_ELU',
    'baxi-kazan-szerelo':         '8IV7oG43DZ0',
    'baxi-kazan-beuzemeles':      'CC5fC3t9-d4',
    'biasi-kazan-karbantartas':   't1wLbu3NoXw',
    'biasi-kazan-szerviz':        'n2dhkAlNWrA',
    'biasi-kazan-szerelo':        '8IV7oG43DZ0',
    'biasi-kazan-beuzemeles':     'lisz_qOlxXg',
    'bosch-kazan-karbantartas':   'OV3J4rU-Qmk',
    'bosch-kazan-szerviz':        '9jcoLHg_ELU',
    'bosch-kazan-szerelo':        '8IV7oG43DZ0',
    'bosch-kazan-beuzemeles':     'pkwutbZBuyI',
    'hajdu-kazan-karbantartas':   'Mo-gZdSUxT8',
    'hajdu-kazan-szerviz':        'm3Yc9QYCiLI',
    'hajdu-kazan-szerelo':        'HAPWeMqGb9g',
    'hajdu-kazan-beuzemeles':     'CC5fC3t9-d4',
    'immergas-kazan-karbantartas':'GUtaTk_LNNs',
    'immergas-kazan-szerviz':     '9jcoLHg_ELU',
    'immergas-kazan-szerelo':     '8IV7oG43DZ0',
    'immergas-kazan-beuzemeles':  'CC5fC3t9-d4',
    'viessmann-kazan-karbantartas':'yP1mHbKDl-k',
    'viessmann-kazan-szerviz':    'xF7s2FD8Guc',
    'viessmann-kazan-szerelo':    '8IV7oG43DZ0',
    'viessmann-kazan-beuzemeles': 'CC5fC3t9-d4',
    'westen-kazan-karbantartas':  '2xnyi18l9NU',
    'westen-kazan-szerviz':       'Mhygobo09nU',
    'westen-kazan-szerelo':       '8IV7oG43DZ0',
    'westen-kazan-beuzemeles':    'CC5fC3t9-d4',
    'wolf-kazan-karbantartas':    'vG6tH8-SuSU',
    'wolf-kazan-szerviz':         '9jcoLHg_ELU',
    'wolf-kazan-szerelo':         'pc388Q1s3Bs',
    'wolf-kazan-beuzemeles':      'CC5fC3t9-d4'
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

    // ── Releváns YouTube-videó (lite-embed: csak kattintásra tölti be az
    //    iframe-et, így a 36 aloldal nem húzza be a nehéz YouTube-lejátszót) ──
    var file = (path.split('/').pop() || '').replace('.html', '');
    var vid = VIDEOS[file];
    var prose = document.querySelector('.svc-prose');
    if (vid && prose && !prose.querySelector('.yt-lite')) {
      var cat = file.split('-').pop();
      var labels = { karbantartas: 'karbantartás', szerviz: 'szerviz', szerelo: 'kazánszerelés', beuzemeles: 'beüzemelés' };
      var label = labels[cat] || 'munka';
      var box = document.createElement('div');
      box.className = 'yt-lite';
      box.innerHTML =
        '<button type="button" class="yt-lite-btn" aria-label="Videó lejátszása – szakszerű ' + label + '">' +
          '<img src="https://i.ytimg.com/vi/' + vid + '/hqdefault.jpg" alt="Videó: szakszerű ' + label + '" loading="lazy" width="480" height="360" />' +
          '<span class="yt-lite-play" aria-hidden="true"><svg viewBox="0 0 68 48" width="68" height="48"><path class="yt-lite-bg" d="M66.5 7.7c-.8-2.9-2.5-5.4-5.4-6.2C55.8.1 34 0 34 0S12.2.1 6.9 1.5C4 2.3 2.3 4.8 1.5 7.7.1 13 0 24 0 24s.1 11 1.5 16.3c.8 2.9 2.5 5.4 5.4 6.2C12.2 47.9 34 48 34 48s21.8-.1 27.1-1.5c2.9-.8 4.6-3.3 5.4-6.2C67.9 35 68 24 68 24s-.1-11-1.5-16.3z"/><path d="M45 24 27 14v20z" fill="#fff"/></svg></span>' +
        '</button>' +
        '<div class="yt-lite-cap">Videó: így néz ki a szakszerű ' + label + '. (Szemléltető felvétel.)</div>';
      var firstP = prose.querySelector(':scope > p');
      if (firstP && firstP.parentNode === prose) firstP.insertAdjacentElement('afterend', box);
      else prose.insertBefore(box, prose.firstChild);
      box.querySelector('.yt-lite-btn').addEventListener('click', function () {
        var ifr = document.createElement('iframe');
        ifr.src = 'https://www.youtube-nocookie.com/embed/' + vid + '?autoplay=1&rel=0';
        ifr.title = 'YouTube videó: szakszerű ' + label;
        ifr.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        ifr.setAttribute('allowfullscreen', '');
        ifr.loading = 'lazy';
        box.classList.add('is-playing');
        box.innerHTML = '';
        box.appendChild(ifr);
      });
    }

    // ── Tartalmi fotó a hero-n kívül (csak márkaoldalakon) ──
    //    Márka-saját fotó ott, ahol van belőle több (Baxi/Hajdu/Viessmann),
    //    egyébként valódi helyszíni munkafotó – mindig a hero-tól eltérő.
    if (brand && prose && !prose.querySelector('.svc-content-photo')) {
      var GENERIC = [
        '707125326_918619997869657_1747506858485559298_n','707420684_1352835460072800_1841501831037842416_n',
        '707420684_981249194665501_7865748018828990854_n','707445157_1589615896497672_6999197818411514873_n',
        '707667883_1000358642393394_1460888495025001767_n','707762984_963615763337907_386011816725818970_n',
        '708134573_1224220399645587_5410294520444085498_n','708299957_1041331691655733_4562790263212162873_n',
        '708304113_1354565203392678_3170359402837390392_n','708865694_1010758681499236_3829306001970551938_n',
        '708876815_980802154355996_844945269867719164_n','708937665_1147167984261941_1263930095550568693_n',
        '709165748_2733256427042606_1419274681728900376_n','709699200_845407851487605_4922152442905902910_n',
        '709762381_1755585772299869_2010306322728056826_n','709840697_2467390073762802_2080528073030313049_n',
        '710350941_4444912195652580_3692931298806003095_n'
      ];
      var POOLS = {
        baxi: ['kazancsere-baxi-1','kazancsere-baxi-2','kazancsere-baxi-3'],
        hajdu: ['kazancsere-hajdu-1','kazancsere-hajdu-2','kazancsere-hajdu-3','kazancsere-hajdu-4','kazancsere-hajdu-5','kazancsere-hajdu-6'],
        viessmann: ['kazancsere-viessmann-1','kazancsere-viessmann-2','kazancsere-viessmann-3','kazancsere-viessmann-4','kazancsere-viessmann-5','kazancsere-viessmann-6','kazancsere-viessmann-7','kazancsere-viessmann-8','kazancsere-viessmann-9','kazancsere-viessmann-10','kazancsere-viessmann-11']
      };
      var brandKey = m ? m[1] : null;
      var pool = (brandKey && POOLS[brandKey]) ? POOLS[brandKey] : GENERIC;
      var cat2 = file.split('-').pop();
      var catIdx = ({ beuzemeles: 0, karbantartas: 1, szerelo: 2, szerviz: 3 })[cat2] || 0;
      var brandIdx = brandKey ? Object.keys(BRANDS).indexOf(brandKey) : 0;
      if (brandIdx < 0) brandIdx = 0;
      var heroImg = document.querySelector('.svc-hero-photo');
      var heroName = heroImg ? decodeURIComponent(heroImg.getAttribute('src') || '').replace(/^.*\//, '').replace(/\.webp$/i, '') : '';
      var pick = pool[(brandIdx * 4 + catIdx) % pool.length];
      if (pick === heroName) pick = pool[(brandIdx * 4 + catIdx + 1) % pool.length];
      if (pick && pick !== heroName) {
        var labels2 = { karbantartas: 'karbantartás', szerviz: 'szerviz', szerelo: 'kazánszerelés', beuzemeles: 'beüzemelés' };
        var lbl = labels2[cat2] || 'munka';
        var fig = document.createElement('figure');
        fig.className = 'svc-content-photo';
        fig.innerHTML = '<img src="k%C3%A9pek/' + pick + '.webp" alt="' + brand.name + ' ' + lbl + ' – valódi helyszíni munka Kecskeméten" loading="lazy" />' +
          '<figcaption>Valódi helyszíni munkánk – ' + brand.name + ' ' + lbl + '.</figcaption>';
        var h2s = prose.querySelectorAll(':scope > h2');
        if (h2s.length >= 2) h2s[1].parentNode.insertBefore(fig, h2s[1]);
        else prose.appendChild(fig);
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
