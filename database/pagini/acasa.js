/*
 * Cele două filme de pe prima pagină.
 *
 * Amândouă pornesc singure — unul ca fundal de hero, celălalt când vizitatorul
 * ajunge cu ecranul la el — deci amândouă rulează fără sunet: aceasta este
 * condiția pusă de browsere pentru redarea automată, nu o alegere de stil.
 *
 * Și amândouă respectă aceleași două limite:
 *
 * - o imagine în mișcare pornită automat, mai lungă de cinci secunde, trebuie
 *   să poată fi oprită de vizitator (WCAG 2.2.2). Filmul de fundal primește un
 *   buton, cel de prezentare are deja comenzile la vedere;
 * - cine și-a cerut din sistem mai puțină mișcare pe ecran, sau are economia de
 *   date pornită, nu primește nimic pornit peste el.
 */
(function () {
  'use strict';

  var miscareRedusa = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
    : false;
  var legatura = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  var economieDeDate = !!(legatura && legatura.saveData);
  var pornimSinguri = !miscareRedusa && !economieDeDate;

  /*
   * Fără pornire automată nu are rost să tragem tot fișierul: „metadata" aduce
   * doar antetul, cât să existe primul cadru și comanda de pornire.
   */
  function nuPorni(film) {
    film.removeAttribute('autoplay');
    film.pause();
    film.preload = 'metadata';
  }

  /* ── Filmul de fundal din hero ─────────────────────────────────────────── */

  (function heroul() {
    var film = document.querySelector('#hero .hero-video');
    var hero = document.getElementById('hero');
    if (!film || !hero) {
      return;
    }

    if (!pornimSinguri) {
      nuPorni(film);
    }

    /*
     * Butonul se construiește din script, nu din markup: dacă filmul lipsește
     * sau browserul refuză să-l redea, nu rămâne pe pagină un buton fără rost.
     */
    var buton = document.createElement('button');
    buton.type = 'button';
    buton.className = 'hero-video-comanda';
    buton.hidden = true;

    var pictograma = document.createElement('span');
    pictograma.setAttribute('aria-hidden', 'true');
    buton.appendChild(pictograma);

    var eticheta = document.createElement('span');
    eticheta.className = 'visually-hidden';
    buton.appendChild(eticheta);

    function descrie() {
      var oprit = film.paused;
      pictograma.textContent = oprit ? '▶' : '❚❚';
      eticheta.textContent = oprit ? 'Pornește filmul de fundal' : 'Oprește filmul de fundal';
      buton.setAttribute('aria-pressed', oprit ? 'true' : 'false');
    }

    buton.addEventListener('click', function () {
      if (film.paused) {
        film.play();
      } else {
        film.pause();
      }
    });

    film.addEventListener('play', descrie);
    film.addEventListener('pause', descrie);

    /*
     * Butonul apare abia când știm că avem ce opri. „loadeddata" înseamnă că
     * browserul chiar a decodat primul cadru; dacă fișierul lipsește sau nu
     * poate fi redat, evenimentul nu vine și butonul rămâne ascuns.
     */
    function arata() {
      descrie();
      buton.hidden = false;
    }

    if (film.readyState >= 2) {
      arata();
    } else {
      film.addEventListener('loadeddata', arata, { once: true });
    }

    film.addEventListener('error', function () {
      buton.hidden = true;
    });

    hero.appendChild(buton);

    if (pornimSinguri) {
      /*
       * Unele browsere refuză pornirea automată chiar și fără sunet.
       * Promisiunea respinsă nu este o eroare de tratat, doar starea reală:
       * filmul stă pe primul cadru, iar butonul arată „Pornește".
       */
      var pornire = film.play();
      if (pornire && typeof pornire.catch === 'function') {
        pornire.catch(descrie);
      }
    }
  })();

  /* ── Filmul de prezentare, pornit la intrarea în ecran ──────────────────── */

  (function prezentarea() {
    var film = document.querySelector('#video .film-prezentare');
    if (!film) {
      return;
    }

    if (!pornimSinguri || typeof IntersectionObserver !== 'function') {
      nuPorni(film);
      return;
    }

    /*
     * Din clipa în care vizitatorul atinge comenzile filmului, hotărârea este a
     * lui: nu îl mai pornim și nu îl mai oprim noi. Comenzile native stau în
     * shadow DOM, dar evenimentul de apăsare ajunge tot pe element.
     */
    var comandatDeOm = false;
    ['pointerdown', 'keydown'].forEach(function (eveniment) {
      film.addEventListener(eveniment, function () {
        comandatDeOm = true;
      });
    });

    /* Un film ajuns la capăt nu se reia singur la fiecare trecere pe lângă el. */
    var terminat = false;
    film.addEventListener('ended', function () {
      terminat = true;
    });

    var observator = new IntersectionObserver(function (intrari) {
      intrari.forEach(function (intrare) {
        if (comandatDeOm || terminat) {
          return;
        }

        if (intrare.isIntersecting) {
          var pornire = film.play();
          if (pornire && typeof pornire.catch === 'function') {
            /* Refuzul browserului nu este o eroare: comenzile rămân la vedere. */
            pornire.catch(function () {});
          }
        } else {
          /* Ieșit din ecran, filmul nu mai are cui să ruleze. */
          film.pause();
        }
      });
    }, { threshold: 0.4 });

    observator.observe(film);
  })();
})();
