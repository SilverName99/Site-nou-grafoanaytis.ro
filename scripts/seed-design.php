<?php

declare(strict_types=1);

/*
 * Configurează Design Site (header, meniu, footer) și oprește elementele de
 * magazin moștenite din proiectul anterior.
 *
 * Conținutul rămâne editabil din Dashboard → Design Site. Ca și la pagini,
 * scriptul nu suprascrie ce există deja decât dacă i se cere explicit:
 *
 *   php scripts/seed-design.php
 *   php scripts/seed-design.php --suprascrie
 *
 * Suprascrierea poate fi limitată la o singură setare, ca schimbarea unui rând
 * din antet să nu ceară rescrierea meniului și a subsolului odată cu el:
 *
 *   php scripts/seed-design.php --suprascrie --doar=design_header_html
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\ResponseCache;
use App\Support\Settings;

$config = require __DIR__ . '/../config/app.php';
$db = Database::connection($config['db']);

if (!$db instanceof PDO) {
    /*
     * Ieșire cu cod de eroare, nu cu zero.
     *
     * „exit(mesaj)" returnează 0, deci un lanț „git pull && seed && seed" mergea
     * mai departe și raporta succes chiar dacă baza de date era căzută și nu se
     * scrisese nimic. Codul 1 oprește lanțul acolo unde trebuie.
     */
    fwrite(STDERR, "Conexiunea la baza de date nu este disponibilă.\n");
    exit(1);
}

$suprascrie = in_array('--suprascrie', $argv, true);

/*
 * Setarea pe care o vizează rularea, dacă s-a cerut una anume.
 *
 * Fără ea, singura cale de a corecta un rând din antet pe un site pornit era
 * „--suprascrie" peste tot — adică și peste meniul și subsolul pe care
 * clientul poate să le fi ajustat între timp din Design Site.
 */
$doar = '';
foreach ($argv as $arg) {
    if (str_starts_with((string) $arg, '--doar=')) {
        $doar = substr((string) $arg, strlen('--doar='));
    }
}

$meniu = [
    ['Acasă', '/'],
    ['Companie', '/companie'],
    /* Punctul 9 din revizie: Produse înaintea Serviciilor. */
    ['Produse', '/produse'],
    ['Servicii', '/servicii'],
    ['Utilaje', '/utilaje'],
    ['Certificări', '/certificari'],
    ['Contact', '/contact'],
];

$linkuriMeniu = '';
foreach ($meniu as [$eticheta, $url]) {
    $linkuriMeniu .= sprintf(
        '        <li class="nav-item"><a class="nav-link" href="%s">%s</a></li>' . "\n",
        htmlspecialchars($url, ENT_QUOTES),
        htmlspecialchars($eticheta, ENT_QUOTES)
    );
}

$header = <<<HTML
<!--
  Banner de comunicare a proiectului cu finanțare europeană.

  Stă în antet, deasupra meniului, deci apare pe toate paginile: obligația de
  informare nu ține de o pagină anume. Nu este lipicios — se derulează în sus,
  iar meniul rămâne.

  Fundal de brand cu text închis: 9,09:1. Toată banda este o singură legătură,
  ca ținta de atingere să fie mare, nu doar câteva cuvinte subliniate. Fișierul
  este servit din site, nu din galerie, care acceptă doar imagini și mp4.
-->
<div id="banner-comunicare" class="bg-brand">
  <a class="banner-comunicare__legatura container d-block text-center py-2"
     href="/documente/comunicat-presa-proiect-332198.pdf" target="_blank" rel="noopener"
     aria-label="Comunicat de presă privind proiectul 332198, fișier PDF, se deschide într-o filă nouă">
    <span class="banner-comunicare__rand">Comunicare implementare proiect „Dezvoltarea activității GRAFOANAYTIS SRL prin achiziția de echipamente”, Cod proiect 332198</span>
    <span class="banner-comunicare__rand">Scurtă descriere a proiectului – PR-SM-2021-2027 GRAFOANAYTIS_332198</span>
  </a>
</div>
<!--
  Meniul se desface abia de la 1200px. Cu șapte intrări, butonul de ofertă și
  două sigle, la 992px rândul se rupea în două: „Despre noi" și „Cere ofertă"
  treceau pe al doilea rând, iar antetul creștea la 105px.
-->
<nav class="navbar navbar-expand-xl bg-white border-bottom sticky-top" aria-label="Navigare principală">
  <div class="container">
    <div class="antet-sigle">
      <!--
        Sigla nouă (punctul 2 din revizie). Fișierul îl încarcă clientul în
        galerie; „onerror" lasă numele firmei ca text dacă lipsește, ca antetul
        să nu rămână gol.
      -->
      <a class="navbar-brand fw-bold me-0" href="/">
        <img src="/uploads/gallery/grafo-logo-nou.png" alt="Grafoanaytis — printing and packaging solutions" height="44"
             onerror="this.onerror=null;this.src='/assets/img/sigla/grafoanaytis.png'">
      </a>
      <!--
        Mențiunea de membru fondator, scoasă la vedere (punctul 31).
        Stătea doar în atributul „title", adică o vedea numai cine ținea
        cursorul pe siglă — pe telefon, nimeni. În macheta clientului este
        scrisă pe două rânduri, lângă emblemă.
      -->
      <a class="antet-sigle__partener" href="/certificari">
        <span class="antet-sigle__mentiune" aria-hidden="true">Membru<br>fondator</span>
        <img src="/uploads/gallery/afas-logo-nou.png" height="44"
             alt="Membru fondator al Asociației Furnizorilor de Ambalaje Sustenabile"
             onerror="this.onerror=null;this.src='/assets/img/certificari/afas-logo.png'">
      </a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#meniu-principal"
            aria-controls="meniu-principal" aria-expanded="false" aria-label="Deschide meniul">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="meniu-principal">
      <ul class="navbar-nav">
{$linkuriMeniu}      </ul>
      <a href="/contact" class="btn btn-primary fw-semibold ms-xl-3 mt-2 mt-xl-0 text-nowrap">Cere ofertă</a>
    </div>
  </div>
</nav>
HTML;

$footer = <<<'HTML'
<!--
  Subsolul, după modelul exonia (punctul 30 din revizie).

  Sigla centrată sus, sub ea un singur rând cu adresa, e-mailul și telefonul,
  apoi rândul de jos cu drepturile de autor, socializarea și creditul de
  webdesign. Coloana de navigare și programul de lucru au ieșit: modelul nu le
  are, iar clientul a cerut copia lui.

  Datele de contact sunt cele din documentul de prezentare al firmei: două
  numere, atribuite pe nume, adresa din Ploiești și adresa de e-mail.
-->
<footer class="subsol bg-dark text-white text-opacity-75 py-5 mt-5">
  <div class="container text-center">

    <a class="subsol__sigla" href="/">
      <img src="/assets/img/sigla/grafoanaytis-alb.png" alt="Grafoanaytis — printing and packaging solutions" height="46">
    </a>

    <ul class="subsol__contact">
      <li>Ploiești, Strada Văleni nr. 141</li>
      <li><a class="link-light" href="mailto:grafoanaytis@yahoo.com">grafoanaytis@yahoo.com</a></li>
      <li><a class="link-light" href="tel:+40722374275">+40 722 374 275</a> <span class="subsol__nume">Cornel Moise</span></li>
      <li><a class="link-light" href="tel:+40746244067">+40 746 244 067</a> <span class="subsol__nume">Sorin Dumitrașcu</span></li>
    </ul>

    <div class="subsol__jos">
      <p class="subsol__drepturi">&copy; {{an}} Grafoanaytis. Toate drepturile rezervate.</p>

      <!--
        Socializarea. Deocamdată doar Instagram: este singurul cont pe care
        firma îl trece în propriul material de prezentare. Facebook, YouTube și
        LinkedIn se adaugă când primim adresele — o pictogramă care nu duce
        nicăieri e mai rea decât una lipsă.
      -->
      <a class="subsol__retea" href="https://www.instagram.com/grafo_anaytis" target="_blank" rel="noopener"
         aria-label="Grafoanaytis pe Instagram">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="5"/>
          <circle cx="12" cy="12" r="4"/>
          <circle cx="17.2" cy="6.8" r="1.1" fill="currentColor" stroke="none"/>
        </svg>
      </a>

      <!--
        Autorități de protecție a consumatorului. Nu sunt certificări ale
        tipografiei, de aceea stau aici, nu în pagina de certificări.
      -->
      <div class="subsol__autoritati">
        <a href="https://anpc.ro" target="_blank" rel="noopener">
          <img src="/uploads/gallery/autoritate-anpc.webp" alt="ANPC — Autoritatea Națională pentru Protecția Consumatorilor" height="30" loading="lazy">
        </a>
        <a href="https://infocons.ro" target="_blank" rel="noopener">
          <img src="/uploads/gallery/autoritate-infocons.webp" alt="InfoCons — Protecția consumatorilor" height="30" loading="lazy">
        </a>
      </div>

      <p class="subsol__credit">Webdesign <a class="link-light" href="https://andaxi.ro" target="_blank" rel="noopener">Andaxi Web Solutions</a></p>
    </div>
  </div>
</footer>
HTML;

$headerJs = <<<'JS'
/*
 * Marcheaza in meniu pagina pe care se afla vizitatorul.
 *
 * Headerul este HTML salvat in setari, deci nu poate decide singur ce pagina
 * este activa. Comparatia se face pe calea din adresa, iar potrivirea pe
 * prefix acopera si subpaginile: /servicii/tipar-offset activeaza „Servicii".
 */
(function () {
  var cale = window.location.pathname.replace(/\/+$/, '') || '/';
  var linkuri = document.querySelectorAll('.navbar-nav .nav-link');
  var potrivit = null;

  linkuri.forEach(function (link) {
    var href = (link.getAttribute('href') || '').replace(/\/+$/, '') || '/';
    if (href === cale) {
      potrivit = link;
    } else if (href !== '/' && cale.indexOf(href + '/') === 0 && !potrivit) {
      potrivit = link;
    }
  });

  if (potrivit) {
    potrivit.classList.add('active');
    potrivit.setAttribute('aria-current', 'page');
  }
})();
JS;

/*
 * Continut editabil de client: aici protectia are sens, ca o rulare repetata
 * sa nu stearga ce a schimbat el din Design Site.
 */
$continut = [
    'design_header_html' => $header,
    /*
     * Meniul secundar, folosit de dashboard. Îi lipsea „Produse" cu totul, deși
     * antetul o are — deci cele două liste spuneau lucruri diferite despre
     * site. Acum urmează aceeași ordine ca antetul.
     */
    'design_menu_html' => '<a href="/">Acasă</a><a href="/companie">Companie</a><a href="/produse">Produse</a><a href="/servicii">Servicii</a><a href="/utilaje">Utilaje</a><a href="/certificari">Certificări</a><a href="/contact">Contact</a>',
    'design_footer_html' => $footer,
    'design_header_js' => $headerJs,
];

/*
 * Comutatoare de magazin mostenite din proiectul anterior. Se scriu mereu.
 *
 * Nu sunt continut, ci decizii care tin de tipul site-ului: un site de
 * prezentare nu are cos si nu are banda de oferte. In plus, scripts/seed.php
 * le lasa pe „1", asa ca la o instalare noua protectia de mai sus le-ar
 * confunda cu editari facute de client si le-ar sari, lasand cosul flotant
 * si tabul de oferte vizibile pe site.
 */
$comutatoare = [
    'floating_cart_enabled' => '0',
    'store_bbd_sidebar_enabled' => '0',
    /*
     * Site de prezentare: coșul și checkout-ul răspund 404. Codul rămâne, deci
     * trecerea la magazin online cere doar stingerea comutatorului.
     */
    'presentation_mode_enabled' => '1',
];

if ($doar !== '' && !array_key_exists($doar, $continut) && !array_key_exists($doar, $comutatoare)) {
    fwrite(STDERR, "Setarea „{$doar}” nu este scrisă de acest script.\n");
    exit(1);
}

if ($doar !== '') {
    $continut = array_intersect_key($continut, [$doar => true]);
    $comutatoare = array_intersect_key($comutatoare, [$doar => true]);
}

$existente = Settings::all($db);
$deScris = $comutatoare;
foreach ($comutatoare as $cheie => $valoare) {
    echo "scris:       {$cheie}\n";
}
foreach ($continut as $cheie => $valoare) {
    $curent = (string) ($existente[$cheie] ?? '');
    if ($curent !== '' && !$suprascrie) {
        echo "sărit:       {$cheie} (are deja valoare; folosiți --suprascrie)\n";
        continue;
    }
    $deScris[$cheie] = $valoare;
    echo "scris:       {$cheie}\n";
}

if ($deScris !== []) {
    Settings::save($db, $deScris);
}

/* Antetul și subsolul intră în HTML-ul salvat pe disc, deci și el trebuie golit. */
$golite = ResponseCache::purgePageCache();
if ($golite > 0) {
    echo "cache:       {$golite} pagini golite din cache.\n";
}

echo "\nGata: " . count($deScris) . " setări scrise.\n";
