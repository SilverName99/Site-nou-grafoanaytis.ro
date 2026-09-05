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
     * Mișcarea este o mutare a benzii, nu o derulare a cutiei.
     *
     * A treia variantă, și ultima. Prima muta banda dintr-o animație CSS: se
     * vedea frumos, dar nu putea fi apucată cu mâna, fiindcă transformarea o
     * scria animația. A doua o muta din „scrollLeft", ca s-o poată apuca
     * oricine: mergea, dar sacadat. Derularea unei cutii trece prin firul
     * principal — la fiecare cadru browserul așază din nou cele treizeci și opt
     * de sigle și repictează banda mascată. Vizibil, mai ales pe un ecran lat.
     *
     * „translate3d" nu așază și nu pictează nimic: mută un strat deja desenat,
     * treabă pe care o face placa video. Cadrul rămâne ieftin oricâte sigle ar
     * fi, iar sursa de adevăr rămâne una singură — „pozitie" — pe care o scriu
     * la fel și ceasul, și degetul, și tastele.
     */
    carusel.classList.add('carusel-clienti--tragere');

    /* 98 de pixeli pe secundă: aceeași viteză ca la prima variantă. */
    var VITEZA = 98 / 1000;

    var pozitie = 0;
    var oprit = false;
    var seTrage = false;
    var pornireX = 0;
    var pornirePozitie = 0;
    var ultimulCadru = 0;

    /*
     * Lungimea unei jumătăți, adică a listei nedublate.
     *
     * Se măsoară de la marginea benzii până la primul element dublat — cele din
     * a doua jumătate poartă „aria-hidden", fiindcă pentru un cititor de ecran
     * sunt aceleași sigle citite a doua oară. Distanța dintre două margini nu
     * se schimbă cu transformarea benzii, deci se poate citi oricând, chiar în
     * timpul mișcării.
     *
     * „scrollWidth / 2" ar fi fost aproape, dar nu exact: rotunjirea la pixel
     * întreg lasă o fracțiune care se aduna la fiecare buclă, până când siglele
     * ajungeau vizibil decalate.
     */
    var primulDublat = banda.querySelector('[aria-hidden="true"]');

    function jumatate() {
      if (!primulDublat) {
        return banda.scrollWidth / 2;
      }
      return primulDublat.getBoundingClientRect().left - banda.getBoundingClientRect().left;
    }

    function aseaza() {
      var j = jumatate();
      if (j > 0) {
        /* Modulo pozitiv: merge și când tragerea a dus poziția sub zero. */
        pozitie = ((pozitie % j) + j) % j;
      }
      banda.style.transform = 'translate3d(' + (-pozitie).toFixed(2) + 'px, 0, 0)';
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

      /*
       * Starea „mouse deasupra" se citește din DOM, nu se ține minte.
       *
       * O variabilă pusă pe „pointerenter" și scoasă pe „pointerleave" iese
       * greșit exact în drumul obișnuit al cititorului: treci cu mouse-ul peste
       * bandă, derulezi mai departe, banda pleacă de sub cursor — și
       * „pointerleave" nu mai vine niciodată, fiindcă mouse-ul n-a mișcat.
       *
       * Focalizarea se citește cu „:focus-visible", nu cu „:focus": banda are
       * „tabindex", deci o apucare cu mouse-ul o și focalizează, iar cu „:focus"
       * ar fi rămas oprită după fiecare tragere.
       */
      var deasupra = carusel.matches(':hover') || carusel.matches(':focus-visible');

      if (!oprit && !deasupra && !seTrage && !miscareRedusa && !document.hidden) {
        pozitie += VITEZA * trecut;
        aseaza();
      }

      window.requestAnimationFrame(cadru);
    }

    aseaza();
    window.requestAnimationFrame(cadru);

    /* Lățimile se schimbă la redimensionare; poziția se așază din nou. */
    var ceasRedimensionare = null;
    window.addEventListener('resize', function () {
      window.clearTimeout(ceasRedimensionare);
      ceasRedimensionare = window.setTimeout(aseaza, 150);
    });

    /* ── Tragerea cu mâna ─────────────────────────────────────────────── */

    carusel.addEventListener('pointerdown', function (e) {
      seTrage = true;
      pornireX = e.clientX;
      pornirePozitie = pozitie;
      carusel.classList.add('se-trage');
      carusel.setPointerCapture(e.pointerId);
    });

    carusel.addEventListener('pointermove', function (e) {
      if (!seTrage) {
        return;
      }
      /* Fără asta, mouse-ul ținut apăsat marchează siglele ca pe un text. */
      e.preventDefault();
      pozitie = pornirePozitie - (e.clientX - pornireX);
      aseaza();
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
     * Fără rândul ăsta, tragerea nu funcționează pe sigle.
     *
     * O imagine este, implicit, un obiect pe care browserul îl poate lua și
     * duce în altă filă. La a doua mișcare cu butonul apăsat pornește „drag"-ul
     * nativ, care fură pointerul și trimite „pointercancel" — adică exact
     * evenimentul prin care noi încheiem tragerea.
     */
    carusel.addEventListener('dragstart', function (e) {
      e.preventDefault();
    });

    /*
     * De la tastatură.
     *
     * Banda nu mai este o cutie derulabilă, deci săgețile nu mai fac nimic
     * singure. Cine ajunge pe ea cu „Tab" trebuie totuși s-o poată mișca, iar
     * un pas de o siglă este pasul firesc.
     */
    carusel.addEventListener('keydown', function (e) {
      var pas = 0;
      if (e.key === 'ArrowRight') {
        pas = 1;
      } else if (e.key === 'ArrowLeft') {
        pas = -1;
      } else {
        return;
      }
      e.preventDefault();
      var element = banda.firstElementChild;
      var latime = element ? element.getBoundingClientRect().width + 16 : 216;
      pozitie += pas * latime;
      aseaza();
    });

    /* ── Butonul de oprire ────────────────────────────────────────────── */

    /*
     * O mișcare pornită singură, mai lungă de cinci secunde, trebuie să poată
     * fi oprită de vizitator — WCAG 2.2.2, aceeași regulă ca la filmul de
     * fundal. Butonul se construiește din script: dacă banda nu se mișcă
     * singură, nu are ce opri.
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
