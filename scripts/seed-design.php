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
use App\Support\Settings;

$config = require __DIR__ . '/../config/app.php';
$db = Database::connection($config['db']);

if (!$db instanceof PDO) {
    exit("Conexiunea la baza de date nu este disponibilă.\n");
}

$suprascrie = in_array('--suprascrie', $argv, true);

$meniu = [
    ['Acasă', '/'],
    ['Despre noi', '/despre-noi'],
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
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" aria-label="Navigare principală">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">
      <img src="/uploads/gallery/grafoanaytis-logo.png" alt="Grafoanaytis" height="38"
           onerror="this.replaceWith(document.createTextNode('Grafoanaytis'))">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#meniu-principal"
            aria-controls="meniu-principal" aria-expanded="false" aria-label="Deschide meniul">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="meniu-principal">
      <ul class="navbar-nav">
{$linkuriMeniu}      </ul>
      <a href="/contact" class="btn btn-primary fw-semibold ms-lg-3 mt-2 mt-lg-0">Cere ofertă</a>
    </div>
  </div>
</nav>
HTML;

$footer = <<<'HTML'
<footer class="bg-dark text-white-50 py-5 mt-5">
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
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/despre-noi">Despre noi</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/servicii">Servicii</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/utilaje">Utilaje</a></li>
          <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="/certificari">Certificări</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <p class="text-white fw-semibold mb-2">Contact</p>
        <p class="mb-1">Str. Văleni nr. 141 (incinta Romfarmachim)<br>Ploiești, România</p>
        <p class="mb-1"><a class="link-light" href="tel:0244510507">0244 510 507</a></p>
        <p class="mb-1"><a class="link-light" href="mailto:grafoanaytis@yahoo.com">grafoanaytis@yahoo.com</a></p>
        <p class="mb-0">Luni – Vineri, 08:00 – 16:00</p>
      </div>
    </div>
    <hr class="border-secondary my-4">
    <p class="small mb-0">&copy; <?= date('Y') ?> Grafoanaytis. Toate drepturile rezervate.</p>
  </div>
</footer>
HTML;

/*
 * Continut editabil de client: aici protectia are sens, ca o rulare repetata
 * sa nu stearga ce a schimbat el din Design Site.
 */
$continut = [
    'design_header_html' => $header,
    'design_menu_html' => '<a href="/">Acasă</a><a href="/despre-noi">Despre noi</a><a href="/servicii">Servicii</a><a href="/utilaje">Utilaje</a><a href="/certificari">Certificări</a><a href="/contact">Contact</a>',
    'design_footer_html' => $footer,
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

echo "\nGata: " . count($deScris) . " setări scrise.\n";
