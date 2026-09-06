/* Trimiterea formularului de contact. Endpointul /contact/send primeste JSON
   si raspunde JSON, deci trimiterea clasica prin submit nu ar functiona. */
(function () {
  var form = document.getElementById('formular-contact');
  if (!form) return;
  var raspuns = document.getElementById('cf-raspuns');
  var buton = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }

    var date = {};
    new FormData(form).forEach(function (v, k) { date[k] = String(v).trim(); });

    buton.disabled = true;
    raspuns.className = 'mt-3 mb-0 text-secondary';
    raspuns.textContent = 'Se trimite…';

    fetch('/contact/send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(date)
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, corp: j }; }); })
      .then(function (rez) {
        if (rez.ok && rez.corp && rez.corp.ok) {
          form.reset();
          raspuns.className = 'mt-3 mb-0 fw-semibold';
          raspuns.textContent = 'Mesajul a fost trimis. Vă răspundem în cel mai scurt timp.';
        } else {
          raspuns.className = 'mt-3 mb-0 text-danger fw-semibold';
          raspuns.textContent = (rez.corp && rez.corp.error) || 'Mesajul nu a putut fi trimis.';
        }
      })
      .catch(function () {
        raspuns.className = 'mt-3 mb-0 text-danger fw-semibold';
        raspuns.textContent = 'Conexiune întreruptă. Încercați din nou.';
      })
      .finally(function () { buton.disabled = false; });
  });
})();

/*
 * Harta, încărcată la cerere.
 *
 * Adresa „iframe"-ului stă într-un atribut, nu într-un „iframe" ascuns: un
 * cadru ascuns tot se încarcă, deci ar fi adus cookie-urile Google chiar dacă
 * nimeni nu-l vede. Cadrul se construiește abia la apăsare.
 *
 * Cine a ales „Accept toate" în bannerul de cookie-uri o primește din pornire:
 * acordul e dat, nu are rost să fie cerut a doua oară.
 */
(function () {
  'use strict';

  var harta = document.querySelector('[data-harta]');
  if (!harta) {
    return;
  }

  var sursa = harta.getAttribute('data-sursa');
  if (!sursa) {
    return;
  }

  function incarca() {
    var cadru = document.createElement('iframe');
    cadru.src = sursa;
    cadru.title = 'Harta către sediul Grafoanaytis, Str. Văleni nr. 141, Ploiești';
    cadru.loading = 'lazy';
    cadru.referrerPolicy = 'strict-origin-when-cross-origin';
    cadru.setAttribute('allowfullscreen', '');
    harta.textContent = '';
    harta.appendChild(cadru);
    harta.setAttribute('data-incarcata', '');
  }

  var buton = harta.querySelector('[data-harta-arata]');
  if (buton) {
    buton.addEventListener('click', incarca);
  }

  /*
   * „bv_cookie_consent=all" este alegerea scrisă de bannerul din șablon.
   * Citirea se face pe cuvinte întregi, ca „all" să nu se potrivească din
   * mijlocul altei valori.
   */
  var acord = document.cookie.split(';').some(function (bucata) {
    return bucata.trim() === 'bv_cookie_consent=all';
  });

  if (acord) {
    incarca();
  }
})();
