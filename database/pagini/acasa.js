/*
 * Filmul de fundal din hero.
 *
 * Rulează singur, în buclă și fără sunet. Trei lucruri trebuie rezolvate în
 * jurul lui:
 *
 * 1. O imagine în mișcare pornită automat, mai lungă de cinci secunde, trebuie
 *    să poată fi oprită de vizitator — WCAG 2.2.2. De aceea butonul de mai jos.
 * 2. Cine și-a cerut din sistem mai puțină mișcare pe ecran nu trebuie să
 *    primească un film care pornește peste el.
 * 3. Cine are economia de date pornită plătește megaocteții. Nici acolo nu
 *    pornim singuri.
 *
 * Butonul se construiește din script, nu din markup: dacă filmul lipsește sau
 * browserul refuză să-l pornească, nu rămâne pe pagină un buton fără rost.
 */
(function () {
  var film = document.querySelector('#hero .hero-video');
  if (!film) {
    return;
  }

  var fereastra = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var miscareRedusa = fereastra ? fereastra.matches : false;
  var legatura = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  var economieDeDate = !!(legatura && legatura.saveData);

  var pornimSinguri = !miscareRedusa && !economieDeDate;

  if (!pornimSinguri) {
    film.removeAttribute('autoplay');
    film.pause();
    /*
     * Fără pornire automată nu are rost să tragem tot fișierul: „metadata"
     * aduce doar antetul, cât să existe primul cadru și butonul de pornire.
     */
    film.preload = 'metadata';
  }

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
   * browserul chiar a decodat primul cadru; dacă fișierul lipsește sau nu poate
   * fi redat, evenimentul nu vine și butonul rămâne ascuns.
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

  var hero = document.getElementById('hero');
  if (hero) {
    hero.appendChild(buton);
  }

  if (pornimSinguri) {
    /*
     * Unele browsere refuză pornirea automată chiar și fără sunet. Promisiunea
     * respinsă nu este o eroare de tratat, doar starea reală: filmul stă pe
     * primul cadru, iar butonul arată „Pornește".
     */
    var pornire = film.play();
    if (pornire && typeof pornire.catch === 'function') {
      pornire.catch(descrie);
    }
  }
})();
