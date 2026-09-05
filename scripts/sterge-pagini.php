<?php

declare(strict_types=1);

/*
 * Șterge din tabela `pages` paginile care au ieșit din site.
 *
 * Scoaterea unei pagini din lista lui seed-pagini.php nu o șterge de nicăieri:
 * rândul rămâne în baza de date de pe server, iar ruta „/{slug*}" continuă
 * să-l servească. Pagina dispare din depozit, dar nu și din site — exact genul
 * de neconcordanță care se descoperă abia când o găsește clientul.
 *
 * Rulare:
 *
 *   php scripts/sterge-pagini.php                         // arată ce ar șterge
 *   php scripts/sterge-pagini.php --confirm               // șterge
 *   php scripts/sterge-pagini.php --confirm --doar=utilaje
 *
 * Fără „--confirm" nu se șterge nimic: doar se listează. Ștergerea unei pagini
 * scrise între timp din dashboard nu se poate întoarce, deci pasul în gol
 * trebuie să fie cel implicit.
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

/*
 * Paginile scoase la punctul 16 din revizie: pagina Utilaje și cele
 * douăsprezece pagini de serviciu care nu mai erau legate de nicăieri după ce
 * pagina Servicii a fost refăcută.
 */
const DE_STERS = [
    'utilaje',
    'servicii/tipar-offset',
    'servicii/stantare-folio-embos',
    'servicii/lipire-cutii',
    'servicii/aplicare-ferestre',
    'servicii/tipar-digital',
    'servicii/ambalaje',
    'servicii/creatie-si-design',
    'servicii/separatie-de-culoare-pe-placi-offset-kodak',
    'servicii/servicii-de-stantare',
    'servicii/inscriptionare-folio-emboss',
    'servicii/gravura-laser',
    'servicii/asistenta',
];

$confirma = in_array('--confirm', $argv, true);
$doar = '';
foreach ($argv as $arg) {
    if (str_starts_with((string) $arg, '--doar=')) {
        $doar = substr((string) $arg, strlen('--doar='));
    }
}

$sloguri = $doar !== '' ? [$doar] : DE_STERS;

$sterse = 0;
$lipsa = 0;

foreach ($sloguri as $slug) {
    $cauta = $db->prepare('SELECT id, title FROM pages WHERE slug = :slug');
    $cauta->execute([':slug' => $slug]);
    $randuri = $cauta->fetchAll(PDO::FETCH_ASSOC);

    if ($randuri === []) {
        echo "  lipsește:  /{$slug}\n";
        $lipsa++;
        continue;
    }

    foreach ($randuri as $rand) {
        if (!$confirma) {
            echo "  s-ar șterge: /{$slug} — „{$rand['title']}” (id {$rand['id']})\n";
            continue;
        }

        $sterge = $db->prepare('DELETE FROM pages WHERE id = :id');
        $sterge->execute([':id' => (int) $rand['id']]);
        echo "  șters:     /{$slug} — „{$rand['title']}” (id {$rand['id']})\n";
        $sterse++;
    }
}

if (!$confirma) {
    echo "\nNimic nu s-a șters. Adaugă --confirm ca să se șteargă.\n";
    exit(0);
}

/*
 * Cache-ul de răspunsuri ține paginile deja compuse. Fără golire, o pagină
 * ștearsă ar continua să apară până la expirarea lui.
 */
if (class_exists(ResponseCache::class) && method_exists(ResponseCache::class, 'flush')) {
    ResponseCache::flush();
}

echo "\nGata: {$sterse} șterse, {$lipsa} negăsite.\n";
