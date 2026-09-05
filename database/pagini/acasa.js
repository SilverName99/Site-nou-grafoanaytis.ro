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

  /* ── Mesajele care se schimbă peste film (pct. 3) ────────────────────── */

  (function mesajeleDinHero() {
    var cutie = document.querySelector('.hero-mesaje');
    var date = document.getElementById('hero-mesaje-date');
    if (!cutie || !date) {
      return;
    }

    var mesaje;
    try {
      mesaje = JSON.parse(date.textContent);
    } catch (e) {
      /* Un JSON stricat nu trebuie să lase pagina fără titlu: rămâne primul. */
      return;
    }
    if (!Array.isArray(mesaje) || mesaje.length < 2) {
      return;
    }

    var text = cutie.querySelector('.hero-mesaje__text');
    var titlu = cutie.querySelector('.hero-mesaje__titlu');
    var subtitlu = cutie.querySelector('.hero-mesaje__subtitlu');
    if (!text || !titlu || !subtitlu) {
      return;
    }


    /*
     * Cine a cerut mai puțină mișcare rămâne cu primul mesaj. Nu e o
     * degradare: mesajele sunt variații ale aceleiași oferte, nu informații
     * diferite, deci nu se pierde nimic.
     */
    if (miscareRedusa) {
      return;
    }

    /*
     * Rezervarea de înălțime, măsurată în pagina reală.
     *
     * Trece pe rând fiecare mesaj, notează cât ocupă blocul, apoi îl fixează
     * pe cel mai înalt. Măsurătoarea se face fără tranziție, într-un singur
     * cadru, deci nu se vede. Se reface la redimensionare, fiindcă numărul de
     * rânduri depinde de lățime.
     */
    function rezervaInaltimea() {
      var tOrig = titlu.textContent;
      var sOrig = subtitlu.textContent;
      var maxim = 0;

      text.style.minHeight = '';
      for (var i = 0; i < mesaje.length; i++) {
        titlu.textContent = mesaje[i][0];
        subtitlu.textContent = mesaje[i][1];
        maxim = Math.max(maxim, text.offsetHeight);
      }

      titlu.textContent = tOrig;
      subtitlu.textContent = sOrig;
      text.style.minHeight = maxim + 'px';
    }

    rezervaInaltimea();

    var ceasDeRedimensionare = null;
    window.addEventListener('resize', function () {
      window.clearTimeout(ceasDeRedimensionare);
      ceasDeRedimensionare = window.setTimeout(rezervaInaltimea, 200);
    });

    var pozitie = 0;
    var ceas = null;

    function arata(index) {
      cutie.setAttribute('data-in-schimbare', '');
      window.setTimeout(function () {
        titlu.textContent = mesaje[index][0];
        subtitlu.textContent = mesaje[index][1];
        cutie.removeAttribute('data-in-schimbare');
      }, 400);
    }

    function porneste() {
      ceas = window.setInterval(function () {
        pozitie = (pozitie + 1) % mesaje.length;
        arata(pozitie);
      }, 6000);
    }

    function opreste() {
      window.clearInterval(ceas);
      ceas = null;
    }

    porneste();

    /* Fila ascunsă nu are cui să rotească mesaje. */
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        opreste();
      } else if (!ceas) {
        porneste();
      }
    });

    /* Butonul filmului oprește și mesajele: e aceeași mișcare, pentru privitor. */
    cutie.closest('#hero').addEventListener('click', function (e) {
      if (e.target.closest('.hero-video-comanda')) {
        if (ceas) { opreste(); } else { porneste(); }
      }
    });
  })();

  /* ── Banda de sigle (pct. 22) ─────────────────────────────────────────── */

  (function bandaDeClienti() {
    var carusel = document.querySelector('[data-carusel-clienti]');
    if (!carusel) {
      return;
    }

    /*
     * O mișcare pornită singură, mai lungă de cinci secunde, trebuie să poată
     * fi oprită de vizitator — WCAG 2.2.2, aceeași regulă ca la filmul de
     * fundal. Butonul se construiește din script, nu din markup: dacă banda nu
     * se mișcă (mai puțină mișcare cerută din sistem), nu apare deloc.
     */
    if (miscareRedusa) {
      return;
    }

    var buton = document.createElement('button');
    buton.type = 'button';
    buton.className = 'carusel-clienti-comanda';
    buton.textContent = 'Oprește derularea';
    buton.setAttribute('aria-pressed', 'false');

    buton.addEventListener('click', function () {
      var oprit = carusel.hasAttribute('data-oprit');
      if (oprit) {
        carusel.removeAttribute('data-oprit');
      } else {
        carusel.setAttribute('data-oprit', '');
      }
      buton.textContent = oprit ? 'Oprește derularea' : 'Pornește derularea';
      buton.setAttribute('aria-pressed', oprit ? 'false' : 'true');
    });

    carusel.insertAdjacentElement('afterend', buton);
  })();

})();
