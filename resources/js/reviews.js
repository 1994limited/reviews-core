/*
 * Reviews: what a review form needs in the browser, as a file, so it runs
 * under a strict Content-Security-Policy.
 *
 * - How long the page was open when the form is sent, for the time check
 *   against spam on cached pages.
 * - With reCAPTCHA v3, a token from Google as the form is sent.
 *
 * One listener on the document, so forms added to the page later (by an
 * include fetched for each visitor, say) are covered too.
 */
(function () {
  if (window.reviewsForms) return;
  window.reviewsForms = true;

  var opened = Date.now();

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.querySelectorAll) return;

    form.querySelectorAll('[data-reviews-opened]').forEach(function (field) {
      field.value = String(Math.round((Date.now() - opened) / 1000));
    });

    var input = form.querySelector('[data-reviews-recaptcha]');
    var key = input && input.getAttribute('data-reviews-recaptcha');
    if (!key || input.value || !window.grecaptcha) return;

    // Not sent yet, nor seen by the form's own listeners: it's sent again
    // once the token is in.
    event.preventDefault();
    event.stopPropagation();
    grecaptcha.ready(function () {
      grecaptcha.execute(key, { action: input.getAttribute('data-reviews-action') || 'review' }).then(function (token) {
        input.value = token;
        form.requestSubmit ? form.requestSubmit() : form.submit();
      });
    });
  }, true);
})();
