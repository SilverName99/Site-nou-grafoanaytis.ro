<?php

declare(strict_types=1);

/*
 * Încarcă în tabela `pages` paginile scrise în database/pagini/.
 *
 * Fiecare pagină este un fișier .html, opțional însoțit de un .css și un .js cu
 * același nume. Conținutul ajunge în Dashboard → Pagini, de unde poate fi
 * editat mai departe.
 *
 * Implicit scriptul adaugă doar paginile care lipsesc, ca o rulare repetată să
 * nu șteargă modificările făcute din dashboard. Suprascrierea se cere explicit:
 *
 *   php scripts/seed-pagini.php                 // adaugă ce lipsește
 *   php scripts/seed-pagini.php --suprascrie    // rescrie și paginile existente
 *   php scripts/seed-pagini.php --doar=acasa    // limitează la un slug
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$config = require __DIR__ . '/../config/app.php';
$db = Database::connection($config['db']);

if (!$db instanceof PDO) {
    exit("Conexiunea la baza de date nu este disponibilă.\n");
}

/**
 * Numele fisierului => titlul afisat in dashboard si in meniu.
 *
 * Slug-ul se obtine din numele fisierului inlocuind „__" cu „/", fiindca un
 * slug poate contine cale (ruta publica /{slug*} accepta si bara oblica), dar
 * un nume de fisier nu.
 */
const PAGINI = [
    'acasa' => 'Acasă',
    'despre-noi' => 'Despre noi',
    'servicii' => 'Servicii',
    'servicii__tipar-offset' => 'Tipar offset',
    'servicii__tipar-digital' => 'Tipar digital',
    'servicii__ambalaje' => 'Ambalaje',
    'servicii__creatie-si-design' => 'Creație și design',
    'servicii__separatie-de-culoare-pe-placi-offset-kodak' => 'Separație de culoare pe plăci offset (Kodak)',
    'servicii__servicii-de-stantare' => 'Ștanțare',
    'servicii__inscriptionare-folio-emboss' => 'Inscripționare Folio / Emboss',
    'servicii__gravura-laser' => 'Gravură laser',
    'servicii__asistenta' => 'Asistență',
    'utilaje' => 'Utilaje',
    'certificari' => 'Certificări',
    'contact' => 'Contact',
];

$suprascrie = in_array('--suprascrie', $argv, true);
$doar = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--doar=')) {
        $doar = substr($arg, strlen('--doar='));
    }
}

$dir = __DIR__ . '/../database/pagini';
$adaugate = 0;
$actualizate = 0;
$sarite = 0;

foreach (PAGINI as $fisier => $titlu) {
    $slug = str_replace('__', '/', $fisier);
    if ($doar !== '' && $doar !== $slug && $doar !== $fisier) {
        continue;
    }

    $caleHtml = $dir . '/' . $fisier . '.html';
    if (!is_file($caleHtml)) {
        continue;
    }

    $html = (string) file_get_contents($caleHtml);
    $css = is_file($dir . '/' . $fisier . '.css') ? (string) file_get_contents($dir . '/' . $fisier . '.css') : null;
    $js = is_file($dir . '/' . $fisier . '.js') ? (string) file_get_contents($dir . '/' . $fisier . '.js') : null;

    $stmt = $db->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        $db->prepare(
            'INSERT INTO pages (title, slug, html_content, css_content, js_content, is_published)
             VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([$titlu, $slug, $html, $css, $js]);
        echo "adăugat:     /{$slug}\n";
        $adaugate++;
        continue;
    }

    if (!$suprascrie) {
        echo "sărit:       /{$slug} (există deja; folosiți --suprascrie ca să îl rescrieți)\n";
        $sarite++;
        continue;
    }

    $db->prepare(
        'UPDATE pages
         SET title = ?, html_content = ?, css_content = ?, js_content = ?, deleted_at = NULL
         WHERE id = ?'
    )->execute([$titlu, $html, $css, $js, $id]);
    echo "actualizat:  /{$slug}\n";
    $actualizate++;
}

echo "\nGata: {$adaugate} adăugate, {$actualizate} actualizate, {$sarite} sărite.\n";
if ($sarite > 0 && !$suprascrie) {
    echo "Paginile existente au fost lăsate neatinse, ca să nu se piardă editările din dashboard.\n";
}
