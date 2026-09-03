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

$meniu = [
    ['Acasă', '/'],
    ['Companie', '/companie'],
    ['Servicii', '/servicii'],
    ['Produse', '/produse'],
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
      <a class="navbar-brand fw-bold me-0" href="/">
        <img src="/uploads/gallery/logo-grafoanaytis-alb-bk.png" alt="Grafoanaytis — offset &amp; digital solutions" height="44"
             onerror="this.replaceWith(document.createTextNode('Grafoanaytis'))">
      </a>
      <!--
        Sigla asociației, cerută de client lângă sigla firmei. Duce la pagina de
        certificări, unde se vede la mărime citibilă: în antet, rândurile de text
        din siglă ajung la câțiva pixeli înălțime.
      -->
      <a class="antet-sigle__partener" href="/certificari"
         title="Membru fondator al Asociației Furnizorilor de Ambalaje Sustenabile">
        <img src="/uploads/gallery/afas-logo.png" height="44"
             alt="AFAS — Asociația Furnizorilor de Ambalaje Sustenabile">
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
<footer class="bg-dark text-white text-opacity-75 py-5 mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-12 col-md-5">
        <p class="h5 text-white mb-2">Grafoanaytis</p>
        <p class="mb-0">Tipografie pe piața prahoveană din 2004. Tipar offset și digital,
        ambalaje cu finisaje superioare.</p>
      </div>
      <div class="col-6 col-md-3">
        <p class="text-white fw-semibold mb-2">Navigare</p>
        <ul class="list-unstyled mb-0">
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/companie">Companie</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/servicii">Servicii</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/utilaje">Utilaje</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/certificari">Certificări</a></li>
        </ul>
      </div>
      <div class="col-12 col-md-4">
        <p class="text-white fw-semibold mb-2">Contact</p>
        <p class="mb-1">Str. Văleni nr. 141 (incinta Romfarmachim)<br>Ploiești, România</p>
        <p class="mb-1"><a class="link-light" href="tel:0244510507">0244 510 507</a></p>
        <p class="mb-1"><a class="link-light" href="mailto:grafoanaytis@yahoo.com">grafoanaytis@yahoo.com</a></p>
        <p class="mb-0">Luni – Vineri, 08:00 – 16:00</p>
      </div>
    </div>
    <hr class="border-secondary my-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <p class="small mb-0">&copy; {{an}} Grafoanaytis. Toate drepturile rezervate.</p>
      <!-- Autorități de protecție a consumatorului. Nu sunt certificări ale
           tipografiei, de aceea stau aici, nu în secțiunea de certificări. -->
      <div class="d-flex align-items-center gap-3">
        <img src="/uploads/gallery/autoritate-anpc.webp" alt="ANPC" height="34" loading="lazy">
        <img src="/uploads/gallery/autoritate-infocons.webp" alt="InfoCons" height="34" loading="lazy">
      </div>
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
    'design_menu_html' => '<a href="/">Acasă</a><a href="/companie">Companie</a><a href="/servicii">Servicii</a><a href="/utilaje">Utilaje</a><a href="/certificari">Certificări</a><a href="/contact">Contact</a>',
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
