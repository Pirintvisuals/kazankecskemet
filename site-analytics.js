/* Website analytics (PostHog) - page views, phone/email/WhatsApp clicks, forms.
   Same PostHog project as the quoting chat widgets; every event carries `site`.

   Install: <script src="/site-analytics.js" data-site="SITE-ID" defer></script>
   Optional: data-consent-key="localStorage key" -> full tracking once that key
   holds "accepted". Until then (and on sites without a consent banner) PostHog
   keeps everything in memory: nothing is stored in the visitor's browser.

   Runs as its own named PostHog instance ("site"), so it never interferes with
   the chat widget's tracking on the same page. Never records what people type. */
(function () {
  "use strict";
  if (window.__siteAnalyticsLoaded) return;
  window.__siteAnalyticsLoaded = true;

  var TOKEN = "phc_v8Kz9YKBCizrjV3vLzRxdiqvikGJpYy4FMET92Z8htpP";
  var HOST = "https://eu.i.posthog.com";
  var NAME = "site";

  var el = document.currentScript || document.querySelector("script[data-site]");
  var cfg = window.SITE_ANALYTICS || {};
  var SITE = cfg.site || (el && el.getAttribute("data-site")) || location.hostname;
  var CONSENT_KEY = cfg.consentKey || (el && el.getAttribute("data-consent-key")) || "";

  function consented() {
    if (!CONSENT_KEY) return false;
    try { return localStorage.getItem(CONSENT_KEY) === "accepted"; } catch (e) { return false; }
  }

  // Load the PostHog library once (the chat widget may load it too). Only the
  // loader stub is created here; the "site" instance is started once the real
  // library is in, because a named instance queued on the stub gets lost when
  // the widget also initialises PostHog on the same page.
  if (!window.posthog || !window.posthog.__SV) {
    !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug getPageViewId".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
    var lib = document.createElement("script");
    lib.async = true;
    lib.crossOrigin = "anonymous";
    lib.src = HOST.replace(".i.posthog.com", "-assets.i.posthog.com") + "/static/array.js";
    document.head.appendChild(lib);
  }

  var instance = null;
  var pending = []; // events fired before the library finished loading

  function libraryReady() {
    var p = window.posthog;
    return p && !p._i && typeof p.init === "function";
  }
  function start() {
    try {
      instance = window.posthog.init(TOKEN, {
        api_host: HOST,
        persistence: consented() ? "localStorage+cookie" : "memory",
        persistence_name: "ph_site_analytics",
        capture_pageview: "history_change", // also counts page changes in Next.js sites
        capture_pageleave: true,             // time on page + scroll depth
        autocapture: true,                   // button/link clicks (never input values)
        disable_session_recording: true,
        disable_surveys: true,
        person_profiles: "identified_only",
      }, NAME);
      instance.register({ site: SITE });
      pending.forEach(function (e) { instance.capture(e[0], e[1]); });
      pending = [];
    } catch (e) {}
  }
  (function wait(tries) {
    if (libraryReady()) return start();
    if (tries < 300) setTimeout(function () { wait(tries + 1); }, 50); // up to 15 s
  })(0);

  function send(event, props) {
    try {
      if (instance) instance.capture(event, props || {});
      else if (pending.length < 50) pending.push([event, props || {}]);
    } catch (e) {}
  }
  function text(node) {
    return String((node && (node.getAttribute("aria-label") || node.textContent)) || "").replace(/\s+/g, " ").trim().slice(0, 60);
  }
  function inChat(node) {
    return !!(node && node.closest && node.closest(".faq-chat-window, .faq-chat-container"));
  }

  // Called by a cookie banner when the visitor accepts.
  window.siteAnalyticsConsent = function (granted) {
    try { if (granted && instance) instance.set_config({ persistence: "localStorage+cookie" }); } catch (e) {}
  };

  // ---- Contact clicks ------------------------------------------------------
  document.addEventListener("click", function (ev) {
    var t = ev.target;
    if (!t || !t.closest) return;
    if (t.closest(".faq-chat-launcher")) send("chat_launcher_click", { page: location.pathname });

    var tracked = t.closest("[data-track]");
    if (tracked) send("cta_click", { name: tracked.getAttribute("data-track"), label: text(tracked) });

    var a = t.closest("a[href]");
    if (!a || inChat(a)) return;
    var href = (a.getAttribute("href") || "").trim().toLowerCase();
    var props = { label: text(a), page: location.pathname };
    if (href.indexOf("tel:") === 0) return send("phone_click", props);
    if (href.indexOf("mailto:") === 0) return send("email_click", props);
    if (/wa\.me|whatsapp\.com|whatsapp:/.test(href)) return send("whatsapp_click", props);
    if (/viber:/.test(href)) return send("viber_click", props);
    var m = href.match(/(facebook|instagram|tiktok|youtube|linkedin|messenger|m\.me)/);
    if (m) { props.network = m[1] === "m.me" ? "messenger" : m[1]; return send("social_click", props); }
    if (a.hostname && a.hostname !== location.hostname && /^https?:/.test(href)) {
      props.domain = a.hostname;
      return send("outbound_click", props);
    }
  }, true);

  // ---- Forms (names only, never the values) --------------------------------
  function formName(f) {
    return f.getAttribute("name") || f.id || (f.getAttribute("action") || "").split("?")[0] || location.pathname;
  }
  var startedForms = [];
  document.addEventListener("focusin", function (ev) {
    var f = ev.target && ev.target.form;
    if (!f || inChat(f) || startedForms.indexOf(f) !== -1) return;
    startedForms.push(f);
    send("form_started", { form: formName(f), page: location.pathname });
  }, true);
  document.addEventListener("submit", function (ev) {
    var f = ev.target;
    if (!f || inChat(f)) return;
    send("form_submitted", { form: formName(f), page: location.pathname });
  }, true);
})();
