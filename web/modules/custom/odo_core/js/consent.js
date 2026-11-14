/**
 * @file
 * Analytics consent banner.
 *
 * Shown until the visitor chooses. The choice is stored in localStorage
 * (not a cookie) and applied with Google consent mode. Declining keeps
 * analytics_storage "denied", so Google sets no analytics cookies.
 */
((Drupal, once) => {
  const KEY = 'odo_consent';

  function stored() {
    try {
      return localStorage.getItem(KEY);
    } catch (e) {
      return null;
    }
  }

  function choose(value, banner) {
    try {
      localStorage.setItem(KEY, value);
    } catch (e) {
      // Private browsing: the choice applies to this page only.
    }
    if (value === 'granted' && typeof window.gtag === 'function') {
      window.gtag('consent', 'update', { analytics_storage: 'granted' });
    }
    banner.remove();
  }

  Drupal.behaviors.odoConsent = {
    attach(context) {
      once('odo-consent', 'body', context).forEach((body) => {
        if (stored()) {
          return;
        }
        const banner = document.createElement('section');
        banner.className = 'odo-consent';
        banner.setAttribute('aria-label', Drupal.t('Analytics consent'));
        banner.innerHTML = `
          <p>${Drupal.t('May we use Google Analytics to count visits? It sets cookies and helps us see which datasets are useful. No ads, no personal profiles.')}</p>
          <div class="odo-consent__actions">
            <button type="button" class="button button--primary" data-choice="granted">${Drupal.t('Allow analytics')}</button>
            <button type="button" class="button" data-choice="denied">${Drupal.t('No thanks')}</button>
          </div>`;
        banner.addEventListener('click', (event) => {
          const choice = event.target.closest('[data-choice]');
          if (choice) {
            choose(choice.dataset.choice, banner);
          }
        });
        body.append(banner);
      });
    },
  };
})(Drupal, once);
