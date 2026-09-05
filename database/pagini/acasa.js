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

    /*
     * Trecerea de la un mesaj la altul, în doi timpi.
     *
     * Mesajul care pleacă urcă și se stinge; cel care vine urcă din jos și se
     * aprinde. Între ei textul se schimbă într-un moment în care nu se vede
     * nimic, iar poziția de pornire a celui nou se pune cu tranziția oprită —
     * altfel browserul ar anima și saltul de jos, și s-ar vedea un tremurat.
     *
     * „offsetHeight" citit între cele două stări nu este de prisos: forțează
     * browserul să recalculeze acum, deci starea de pornire chiar există
     * înainte de a fi schimbată. Fără el, cele două schimbări s-ar contopi
     * într-un singur cadru și textul ar apărea brusc, fără drum.
     */
    function arata(index) {
      cutie.setAttribute('data-iese', '');

      window.setTimeout(function () {
        titlu.textContent = mesaje[index][0];
        subtitlu.textContent = mesaje[index][1];

        cutie.removeAttribute('data-iese');
        cutie.setAttribute('data-asezare', '');
        void text.offsetHeight;
        cutie.removeAttribute('data-asezare');
      }, 320);
    }

    function porneste() {
      ceas = window.setInterval(function () {
        pozitie = (pozitie + 1) % mesaje.length;
        arata(pozitie);
      }, 4200);
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

    var banda = carusel.querySelector('.carusel-clienti__banda');
    if (!banda) {
      return;
    }

    /*
     * Derularea se face din poziția de derulare a cutiei, nu dintr-o animație
     * CSS pe bandă.
     *
     * Varianta cu „@keyframes translateX" mergea, dar nu putea fi apucată cu
     * mâna: transformarea este scrisă de animație, iar o tragere ar fi trebuit
     * să i se suprapună, ceea ce înseamnă fie oprirea animației și recalcularea
     * poziției la fiecare apăsare, fie două surse de adevăr pentru aceeași
     * mișcare. Aici mișcarea este una singură — „scrollLeft" — iar tragerea,
     * degetul, rotița și tastele o schimbă toate la fel.
     *
     * Lista este dublată în pagină, deci la jumătatea lățimii banda arată
     * exact ca la început: scăzând jumătatea când o depășim, bucla nu se vede.
     */
    carusel.classList.add('carusel-clienti--tragere');

    /* 98 de pixeli pe secundă: aceeași viteză ca animația de dinainte. */
    var VITEZA = 98 / 1000;

    var oprit = false;
    var deasupra = false;
    var seTrage = false;
    var pornireX = 0;
    var pornireScroll = 0;
    var ultimulCadru = 0;

    function jumatate() {
      return banda.scrollWidth / 2;
    }

    function normalizeaza() {
      var j = jumatate();
      if (j <= 0) {
        return;
      }
      if (carusel.scrollLeft >= j) {
        carusel.scrollLeft -= j;
      } else if (carusel.scrollLeft < 0) {
        carusel.scrollLeft += j;
      }
    }

    function cadru(acum) {
      var trecut = ultimulCadru ? acum - ultimulCadru : 0;
      ultimulCadru = acum;

      /*
       * Un salt mai mare de o zecime de secundă înseamnă filă revenită din
       * fundal sau un cadru pierdut: banda ar sări. Îl tratăm ca pe un cadru
       * obișnuit.
       */
      if (trecut > 100) {
        trecut = 16;
      }

      if (!oprit && !deasupra && !seTrage && !miscareRedusa && !document.hidden) {
        carusel.scrollLeft += VITEZA * trecut;
        normalizeaza();
      }

      window.requestAnimationFrame(cadru);
    }

    window.requestAnimationFrame(cadru);

    /* Sigla privită nu trebuie să fugă de sub cursor. */
    carusel.addEventListener('pointerenter', function (e) {
      if (e.pointerType !== 'touch') {
        deasupra = true;
      }
    });
    carusel.addEventListener('pointerleave', function () { deasupra = false; });
    carusel.addEventListener('focusin', function () { deasupra = true; });
    carusel.addEventListener('focusout', function () { deasupra = false; });

    /* ── Tragerea cu mâna ─────────────────────────────────────────────── */

    carusel.addEventListener('pointerdown', function (e) {
      /*
       * Pe ecran tactil derularea cu degetul o face browserul singur, mai bine
       * decât am face-o noi: nu ne băgăm.
       */
      if (e.pointerType === 'touch') {
        return;
      }
      seTrage = true;
      pornireX = e.clientX;
      pornireScroll = carusel.scrollLeft;
      carusel.classList.add('se-trage');
      carusel.setPointerCapture(e.pointerId);
    });

    carusel.addEventListener('pointermove', function (e) {
      if (!seTrage) {
        return;
      }
      /* Fără asta, mouse-ul ținut apăsat marchează siglele ca pe un text. */
      e.preventDefault();
      carusel.scrollLeft = pornireScroll - (e.clientX - pornireX);
      normalizeaza();
    });

    function incheieTragerea(e) {
      if (!seTrage) {
        return;
      }
      seTrage = false;
      carusel.classList.remove('se-trage');
      if (e && e.pointerId !== undefined && carusel.hasPointerCapture(e.pointerId)) {
        carusel.releasePointerCapture(e.pointerId);
      }
    }

    carusel.addEventListener('pointerup', incheieTragerea);
    carusel.addEventListener('pointercancel', incheieTragerea);

    /*
     * Fără rândul ăsta, tragerea nu funcționează deloc pe sigle.
     *
     * O imagine este, implicit, un obiect pe care browserul îl poate lua și
     * duce în altă filă. La a doua mișcare cu butonul apăsat pornește „drag"-ul
     * nativ, care fură pointerul și trimite „pointercancel" — adică exact
     * evenimentul prin care noi încheiem tragerea. Se vedea ca o bandă care se
     * mișcă doi-trei pixeli și se oprește.
     */
    carusel.addEventListener('dragstart', function (e) {
      e.preventDefault();
    });

    /* ── Butonul de oprire ────────────────────────────────────────────── */

    /*
     * O mișcare pornită singură, mai lungă de cinci secunde, trebuie să poată
     * fi oprită de vizitator — WCAG 2.2.2, aceeași regulă ca la filmul de
     * fundal. Butonul se construiește din script, nu din markup: dacă banda nu
     * se mișcă singură, nu are ce opri.
     *
     * Tragerea rămâne însă și atunci: „mai puțină mișcare" înseamnă „nu porni
     * tu nimic", nu „nu-l lăsa pe om să miște".
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
      oprit = !oprit;
      buton.textContent = oprit ? 'Pornește derularea' : 'Oprește derularea';
      buton.setAttribute('aria-pressed', oprit ? 'true' : 'false');
    });

    carusel.insertAdjacentElement('afterend', buton);
  })();

})();
