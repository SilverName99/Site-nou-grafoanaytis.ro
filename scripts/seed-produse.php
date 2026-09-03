<?php

declare(strict_types=1);

/*
 * Încarcă în bază catalogul din database/produse/.
 *
 * Ca la pagini: fișierele din git sunt sursa, dar conținutul rămâne editabil din
 * dashboard. Fără --suprascrie, produsele care există deja nu sunt atinse, ca o
 * rulare repetată să nu șteargă ce a schimbat clientul.
 *
 *   php scripts/seed-produse.php
 *   php scripts/seed-produse.php --suprascrie
 *   php scripts/seed-produse.php --suprascrie --curata   (scoate produsele străine)
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\ResponseCache;

$config = require __DIR__ . '/../config/app.php';
$db = Database::connection($config['db']);

if (!$db instanceof PDO) {
    fwrite(STDERR, "Conexiunea la baza de date nu este disponibilă.\n");
    exit(1);
}

$suprascrie = in_array('--suprascrie', $argv, true);
$date = require __DIR__ . '/../database/produse/produse.php';
$dirSablon = __DIR__ . '/../database/produse';

/* ── Categoriile ─────────────────────────────────────────────────────── */

$idCategorii = [];
foreach ($date['categorii'] as $slug => $nume) {
    $stmt = $db->prepare('SELECT id FROM product_categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        $db->prepare('INSERT INTO product_categories (name, slug) VALUES (?, ?)')->execute([$nume, $slug]);
        $id = (int) $db->lastInsertId();
        echo "categorie adăugată:  {$nume}\n";
    } else {
        $db->prepare('UPDATE product_categories SET name = ? WHERE id = ?')->execute([$nume, (int) $id]);
    }

    $idCategorii[$slug] = (int) $id;
}

/* ── Câmpurile suplimentare ──────────────────────────────────────────── */

$idCampuri = [];
$ordine = 0;
foreach ($date['campuri'] as $cheie => $nume) {
    $ordine++;
    $stmt = $db->prepare('SELECT id FROM product_extra_fields WHERE field_key = ? LIMIT 1');
    $stmt->execute([$cheie]);
    $id = $stmt->fetchColumn();

    if ($id === false) {
        $db->prepare(
            'INSERT INTO product_extra_fields (name, field_key, field_type, sort_order, is_active)
             VALUES (?, ?, "text", ?, 1)'
        )->execute([$nume, $cheie, $ordine]);
        $id = (int) $db->lastInsertId();
        echo "câmp adăugat:        {$nume}\n";
    }

    $idCampuri[$cheie] = (int) $id;
}

/* ── Șablonul de pagină ──────────────────────────────────────────────── */

$html = (string) file_get_contents($dirSablon . '/sablon-ambalaj.html');
$css = is_file($dirSablon . '/sablon-ambalaj.css')
    ? (string) file_get_contents($dirSablon . '/sablon-ambalaj.css')
    : null;

$stmt = $db->prepare('SELECT id FROM product_templates WHERE slug = ? LIMIT 1');
$stmt->execute(['ambalaj']);
$idSablon = $stmt->fetchColumn();

if ($idSablon === false) {
    $db->prepare(
        'INSERT INTO product_templates (name, slug, description, html_content, css_content, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    )->execute(['Ambalaj și tipăritură', 'ambalaj', 'Pagina de produs după modelul exonia.ro', $html, $css]);
    $idSablon = (int) $db->lastInsertId();
    echo "șablon adăugat:      Ambalaj și tipăritură\n";
} else {
    $db->prepare('UPDATE product_templates SET html_content = ?, css_content = ?, is_active = 1 WHERE id = ?')
        ->execute([$html, $css, (int) $idSablon]);
    echo "șablon actualizat:   Ambalaj și tipăritură\n";
}
$idSablon = (int) $idSablon;

/* ── Produsele ───────────────────────────────────────────────────────── */

$adaugate = 0;
$actualizate = 0;
$sarite = 0;

foreach ($date['produse'] as $produs) {
    $stmt = $db->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
    $stmt->execute([$produs['slug']]);
    $id = $stmt->fetchColumn();

    if ($id !== false && !$suprascrie) {
        echo "sărit:               {$produs['nume']}\n";
        $sarite++;
        continue;
    }

    $galerie = array_map(
        static fn (string $f): string => '/uploads/gallery/' . $f,
        $produs['galerie']
    );

    /*
     * Prima categorie din listă este familia produsului: ea intră în
     * „category_id" și apare pe pagina de produs ca etichetă. Restul sunt
     * legături, ca produsul să apară și sub serviciile care îl execută.
     */
    $familie = $idCategorii[$produs['categorii'][0]] ?? null;

    $valori = [
        'name' => $produs['nume'],
        'slug' => $produs['slug'],
        'short_description' => $produs['subtitlu'],
        'description' => $produs['descriere'],
        'product_highlights' => $produs['aplicabilitate'],
        'image_url' => '/uploads/gallery/' . $produs['imagine'],
        'gallery_images_json' => json_encode($galerie, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'category_id' => $familie,
        /*
         * Coloana veche „category" ține numele scris, iar pagina de produs de
         * acolo îl citește pentru eticheta de sub titlu — nu din category_id.
         * Fără ea, eticheta rămânea un dreptunghi gol.
         */
        'category' => $date['categorii'][$produs['categorii'][0]] ?? null,
        'product_template_id' => $idSablon,
        /* Site de prezentare: nu se afișează prețuri, se cere ofertă. */
        'price' => 0,
        'is_active' => 1,
    ];

    if ($id === false) {
        $coloane = implode(', ', array_keys($valori));
        $semne = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_keys($valori)));
        $db->prepare("INSERT INTO products ({$coloane}) VALUES ({$semne})")->execute($valori);
        $id = (int) $db->lastInsertId();
        echo "adăugat:             {$produs['nume']}\n";
        $adaugate++;
    } else {
        $seteaza = implode(', ', array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($valori)));
        $valori['id'] = (int) $id;
        $db->prepare("UPDATE products SET {$seteaza}, deleted_at = NULL WHERE id = :id")->execute($valori);
        $id = (int) $id;
        echo "actualizat:          {$produs['nume']}\n";
        $actualizate++;
    }

    /* Legăturile cu toate categoriile, familie și servicii deopotrivă. */
    $db->prepare('DELETE FROM product_category_links WHERE product_id = ?')->execute([$id]);
    $leaga = $db->prepare('INSERT INTO product_category_links (product_id, category_id) VALUES (?, ?)');
    foreach ($produs['categorii'] as $slugCategorie) {
        if (isset($idCategorii[$slugCategorie])) {
            $leaga->execute([$id, $idCategorii[$slugCategorie]]);
        }
    }

    /* Valorile câmpurilor suplimentare. */
    $db->prepare('DELETE FROM product_extra_field_values WHERE product_id = ?')->execute([$id]);
    $scrieCamp = $db->prepare(
        'INSERT INTO product_extra_field_values (product_id, field_id, value) VALUES (?, ?, ?)'
    );
    foreach ($produs['campuri'] as $cheie => $valoare) {
        if (isset($idCampuri[$cheie]) && trim((string) $valoare) !== '') {
            $scrieCamp->execute([$id, $idCampuri[$cheie], $valoare]);
        }
    }
}

/* ── Curățarea produselor străine ────────────────────────────────────────
   Instalarea a fost preluată dintr-un magazin, deci în bază pot exista produse
   care nu au ce căuta pe site-ul tipografiei — „Miere de Manuka", „Spirulină".
   Se scot doar la cerere și doar prin marcare ca șterse, nu prin DELETE: dacă
   se dovedește că mai trebuiau, se recuperează dintr-o singură comandă.
   ────────────────────────────────────────────────────────────────────── */

if (in_array('--curata', $argv, true)) {
    $sluguri = array_column($date['produse'], 'slug');
    $semne = implode(',', array_fill(0, count($sluguri), '?'));
    $stmt = $db->prepare(
        "SELECT id, name FROM products WHERE deleted_at IS NULL AND slug NOT IN ({$semne})"
    );
    $stmt->execute($sluguri);
    $straine = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($straine as $strain) {
        $db->prepare('UPDATE products SET deleted_at = NOW(), is_active = 0 WHERE id = ?')
            ->execute([(int) $strain['id']]);
        echo "scos:                {$strain['name']}\n";
    }

    if ($straine === []) {
        echo "curat:               niciun produs străin în bază\n";
    }
}

/* ── Produsele asemănătoare ──────────────────────────────────────────────
   Aplicația le arată doar dacă sunt alese explicit pentru fiecare produs, în
   „similar_products_json". Alegerea de mână, la paisprezece produse, s-ar
   strica la prima adăugare, deci se calculează: pentru fiecare produs, cele
   mai apropiate patru, ordonate după câte categorii au în comun cu el.
   ────────────────────────────────────────────────────────────────────── */

$categoriiPeProdus = [];
$stmt = $db->query(
    'SELECT l.product_id, l.category_id
     FROM product_category_links l
     JOIN products p ON p.id = l.product_id AND p.deleted_at IS NULL'
);
foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $rand) {
    $categoriiPeProdus[(int) $rand['product_id']][] = (int) $rand['category_id'];
}

$scrieSimilare = $db->prepare('UPDATE products SET similar_products_json = ? WHERE id = ?');
$legate = 0;
foreach ($categoriiPeProdus as $idProdus => $categoriile) {
    $scoruri = [];
    foreach ($categoriiPeProdus as $idAltul => $aleLui) {
        if ($idAltul === $idProdus) {
            continue;
        }
        $comune = count(array_intersect($categoriile, $aleLui));
        if ($comune > 0) {
            $scoruri[$idAltul] = $comune;
        }
    }
    /* La scor egal contează ordinea din catalog, ca rezultatul să fie stabil. */
    arsort($scoruri);
    $alese = array_slice(array_keys($scoruri), 0, 4);
    if ($alese !== []) {
        $scrieSimilare->execute([json_encode(array_values($alese)), $idProdus]);
        $legate++;
    }
}
echo "similare:            {$legate} produse au primit produse asemănătoare\n";

$golite = ResponseCache::purgePageCache();
if ($golite > 0) {
    echo "\ncache: {$golite} pagini golite din cache.\n";
}

echo "\nGata: {$adaugate} adăugate, {$actualizate} actualizate, {$sarite} sărite.\n";
