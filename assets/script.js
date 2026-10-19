(function () {
  'use strict';

  function getCookie(name) {
    const m = document.cookie.match('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)');
    return m ? decodeURIComponent(m[1]) : null;
  }
  function setCookie(name, value, seconds) {
    const d = new Date();
    d.setTime(d.getTime() + seconds * 1000);
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof NP_DATA === 'undefined') return;

    const COOKIE_DISMISS    = (NP_DATA.cookiePrefix || 'np_') + 'dismissed_until';
    const COOKIE_SUBSCRIBED = (NP_DATA.cookiePrefix || 'np_') + 'subscribed_until';
    const now = Math.floor(Date.now() / 1000);
    const popupEnabled = parseInt(NP_DATA.popupEnabled, 10) === 1;

    /* =========================================================
       1)  POPUP (scroll-triggered)
    ========================================================= */
    const overlay  = document.getElementById('newsletterOverlay');
    const closeBtn = document.getElementById('closeNewsletter');

    if (overlay && popupEnabled) {

      const dismissUntil   = parseInt(getCookie(COOKIE_DISMISS) || '0', 10);
      const subscribeUntil = parseInt(getCookie(COOKIE_SUBSCRIBED) || '0', 10);
      const suppressed     = subscribeUntil > now || dismissUntil > now;

      let hasAppeared = false;
      let isDismissed = false;

      function openOverlay() {
        overlay.classList.add('active');
        hasAppeared = true;
        document.body.style.overflow = 'hidden';
      }
      function closeOverlay(storeCooldown) {
        overlay.classList.remove('active');
        isDismissed = true;
        document.body.style.overflow = '';
        if (storeCooldown) {
          const cd = parseInt(NP_DATA.dismissCooldown, 10) || 300;
          setCookie(COOKIE_DISMISS, String(now + cd), cd);
        }
      }
      function checkScroll() {
        if (isDismissed || hasAppeared) return;
        if (window.scrollY > parseInt(NP_DATA.scrollThreshold, 10)) openOverlay();
      }

      if (!suppressed) {
        window.addEventListener('scroll', checkScroll, { passive: true });
        window.addEventListener('load', function () {
          if (window.scrollY > parseInt(NP_DATA.scrollThreshold, 10) && !isDismissed && !hasAppeared) {
            openOverlay();
          }
        });
      }

      if (closeBtn) closeBtn.addEventListener('click', function () { closeOverlay(true); });
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeOverlay(true);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) closeOverlay(true);
      });

      // Expose for the form handler below.
      overlay._np = { close: function () { closeOverlay(false); } };
    }

    /* =========================================================
       2)  ALL SUBSCRIBE FORMS (popup + shortcode)
    ========================================================= */
    const forms = document.querySelectorAll('form.np-subscribe-form');
    if (!forms.length) return;

    forms.forEach(function (form) {

      form.addEventListener('submit', function (e) {
        e.preventDefault();

        const emailInput = form.querySelector('input[name="email"]');
        const submitBtn  = form.querySelector('button[type="submit"], input[type="submit"]');
        const msgEl      = form.querySelector('.np-message') || form.parentNode.querySelector('.np-message');

        if (!emailInput || !submitBtn) return;

        const email = (emailInput.value || '').trim();
        if (!email) {
          if (msgEl) showMessage(msgEl, 'Please enter your email address.', 'error');
          return;
        }

        const originalText = submitBtn.tagName === 'INPUT' ? submitBtn.value : submitBtn.innerHTML;

        submitBtn.disabled = true;
        if (submitBtn.tagName === 'INPUT') submitBtn.value = 'Checking…';
        else submitBtn.innerHTML = 'Checking…';
        if (msgEl) showMessage(msgEl, 'Validating email, please wait…', 'success');

        const body = new URLSearchParams();
        body.append('action', 'np_subscribe');
        body.append('nonce', NP_DATA.nonce);
        body.append('email', email);

        fetch(NP_DATA.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          submitBtn.disabled = false;
          if (submitBtn.tagName === 'INPUT') submitBtn.value = originalText;
          else submitBtn.innerHTML = originalText;

          if (json && json.success) {
            if (msgEl) showMessage(msgEl, json.data.message || 'Thanks for subscribing!', 'success');
            emailInput.value = '';

            // Set subscribed cookie to suppress popup for X days.
            const cd = parseInt(NP_DATA.subscribeCooldown, 10) || 2592000;
            setCookie(COOKIE_SUBSCRIBED, String(now + cd), cd);

            // If inside the popup, close it after a delay.
            if (overlay && overlay._np && form.closest('#newsletterOverlay')) {
              setTimeout(overlay._np.close, 1500);
            }
          } else {
            const msg = (json && json.data && json.data.message) || 'Subscription failed. Please try again.';
            if (msgEl) showMessage(msgEl, msg, 'error');
          }
        })
        .catch(function () {
          submitBtn.disabled = false;
          if (submitBtn.tagName === 'INPUT') submitBtn.value = originalText;
          else submitBtn.innerHTML = originalText;
          if (msgEl) showMessage(msgEl, 'Network error. Please try again.', 'error');
        });
      });
    });

    function showMessage(el, text, type) {
      el.textContent = text;
      el.classList.remove('is-error', 'is-success');
      el.classList.add(type === 'success' ? 'is-success' : 'is-error');
    }
  });
})();