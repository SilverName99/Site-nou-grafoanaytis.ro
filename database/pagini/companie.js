/*
 * Cronologia din pagina Companie.
 *
 * Banda se derulează oricum, fără JavaScript: are „overflow-x: auto", deci
 * merge cu degetul pe telefon și cu bara de derulare a browserului. Scriptul
 * adaugă trei lucruri peste asta — săgeți, tragere cu mouse-ul și oprirea la
 * capete — și de aceea săgețile sunt ascunse până când el pornește: un buton
 * care nu comandă nimic e mai rău decât niciun buton.
 */
(function () {
  'use strict';

  var cronologie = document.querySelector('.cronologie');
  if (!cronologie) {
    return;
  }

  var pista = cronologie.querySelector('.cronologie__pista');
  var inapoi = cronologie.querySelector('.cronologie__sageata--inapoi');
  var inainte = cronologie.querySelector('.cronologie__sageata--inainte');
  var oprire = cronologie.querySelector('.cronologie__oprire');
  if (!pista || !inapoi || !inainte || !oprire) {
    return;
  }

  function pas() {
    return oprire.getBoundingClientRect().width || 272;
  }

  /*
   * Săgețile apar doar dacă banda chiar depășește ecranul.
   *
   * Pe ecran lat cele șase opriri intră toate, deci nu era ce derula: rămâneau
   * două butoane rotunde care nu făceau nimic. Măsurătoarea se reface la
   * fiecare redimensionare, fiindcă lățimea unei opriri se schimbă cu ea.
   *
   * Marja de o unitate acoperă rotunjirile de subpixel ale browserului, care
   * altfel raportează o depășire de o fracțiune de pixel.
   */
  function seDeruleaza() {
    return pista.scrollWidth - pista.clientWidth > 1;
  }

  /*
   * Un capăt atins înseamnă buton stins. Aceeași marjă de o unitate, din
   * același motiv.
   */
  function actualizeazaSageti() {
    cronologie.classList.toggle('cronologie--pornita', seDeruleaza());

    var maxim = pista.scrollWidth - pista.clientWidth;
    inapoi.disabled = pista.scrollLeft <= 1;
    inainte.disabled = pista.scrollLeft >= maxim - 1;
  }

  var miscareRedusa = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
    : false;

  function deruleaza(directie) {
    pista.scrollBy({
      left: directie * pas(),
      behavior: miscareRedusa ? 'auto' : 'smooth'
    });
  }

  inapoi.addEventListener('click', function () { deruleaza(-1); });
  inainte.addEventListener('click', function () { deruleaza(1); });
  pista.addEventListener('scroll', actualizeazaSageti, { passive: true });
  window.addEventListener('resize', actualizeazaSageti);
  actualizeazaSageti();

  /* ── Tragere cu mouse-ul ──────────────────────────────────────────────
     Pe ecrane tactile derularea funcționează deja; asta este doar pentru
     mouse, unde altfel ar rămâne numai săgețile.
     ────────────────────────────────────────────────────────────────── */

  var seTrage = false;
  var pornireX = 0;
  var pornireScroll = 0;

  pista.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'touch') {
      return;
    }
    seTrage = true;
    pornireX = e.clientX;
    pornireScroll = pista.scrollLeft;
    pista.classList.add('se-trage');
    pista.setPointerCapture(e.pointerId);
  });

  pista.addEventListener('pointermove', function (e) {
    if (!seTrage) {
      return;
    }
    /*
     * „preventDefault" oprește selecția textului în timpul tragerii. Fără el,
     * mouse-ul ținut apăsat marchează anii ca pe un text obișnuit.
     */
    e.preventDefault();
    pista.scrollLeft = pornireScroll - (e.clientX - pornireX);
  });

  function incheieTragerea(e) {
    if (!seTrage) {
      return;
    }
    seTrage = false;
    pista.classList.remove('se-trage');
    if (e && e.pointerId !== undefined && pista.hasPointerCapture(e.pointerId)) {
      pista.releasePointerCapture(e.pointerId);
    }
  }

  pista.addEventListener('pointerup', incheieTragerea);
  pista.addEventListener('pointercancel', incheieTragerea);
  pista.addEventListener('pointerleave', incheieTragerea);
})();
