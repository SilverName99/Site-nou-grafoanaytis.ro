/*
 * Sertarul de ofertă, pe pagina de servicii.
 *
 * Este un singur sertar pentru toate cele trei coloane. Aici se schimbă, la
 * deschidere, două lucruri: titlul din capul panoului și serviciul pe care îl
 * trimite formularul mai departe, către /api/cerere-oferta.
 *
 * Restul — deschiderea, închiderea cu Escape, ținerea focusului înăuntru și
 * întoarcerea lui pe butonul apăsat — le face offcanvas-ul Bootstrap, iar
 * trimiterea formularului o face „sertar-oferta.js", care este încărcat pe
 * toate paginile.
 */
(function () {
  'use strict';

  var sertar = document.getElementById('sertar-oferta');
  if (!sertar) {
    return;
  }

  var eticheta = sertar.querySelector('[data-sertar-nume]');
  var formular = sertar.querySelector('.sertar-oferta__formular');
  if (!eticheta || !formular) {
    return;
  }

  /*
   * „show.bs.offcanvas" ne dă chiar elementul care a deschis panoul, deci nu
   * trebuie ținută minte nicio stare între clicuri.
   */
  sertar.addEventListener('show.bs.offcanvas', function (eveniment) {
    var declansator = eveniment.relatedTarget;
    if (!declansator) {
      return;
    }

    var nume = declansator.getAttribute('data-serviciu-nume') || '';
    var slug = declansator.getAttribute('data-serviciu') || '';

    if (nume !== '') {
      eticheta.textContent = nume;
    }

    /*
     * Slug-ul ajunge în cererea salvată. Serverul îl caută întâi printre
     * produse și, dacă nu-l găsește — cazul nostru — îl păstrează ca atare,
     * ceea ce este exact ce ne trebuie: în lista de cereri din dashboard se
     * vede la ce serviciu se referă.
     */
    formular.setAttribute('data-produs', slug);
  });
})();
