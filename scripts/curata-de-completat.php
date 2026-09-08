<?php

declare(strict_types=1);

/*
 * Scoate din baza de date marcajele „[DE COMPLETAT]".
 *
 * Fișele de produs au fost scrise cu locuri libere acolo unde clientul trebuia
 * să confirme cifrele — dimensiuni, tiraj minim, termen de execuție. Cât timp
 * el nu le-a trimis, marcajul a rămas scris pe site, la vedere, în tabelul de
 * caracteristici: „Tiraj minim — [DE COMPLETAT: tirajul minim]". Un vizitator
 * nu are de unde ști că e o notiță între noi doi; citește o fișă neterminată.
 *
 * Mai bine lipsă decât marcaj: rândurile din tabel se ascund singure când nu au
 * valoare („.caracteristici__rand:has(dd:empty)" din șablonul de produs), deci
 * ștergerea valorii scoate rândul cu totul, nu lasă un rând gol.
 *
 * Fișierul „database/produse/produse.php" a fost curățat odată cu scriptul
 * acesta, așa că o rulare nouă a lui seed-produse.php nu le mai pune înapoi.
 * Scriptul e pentru baza care rulează deja: acolo valorile sunt scrise de la
 * seed-ul de dinainte și nu pleacă de la sine.
 *
 * Rulare:
 *
 *   php scripts/curata-de-completat.php            // arată ce ar schimba
 *   php scripts/curata-de-completat.php --confirm  // schimbă
 *   php scripts/curata-de-completat.php --confirm --tot
 *
 * Fără „--confirm" nu se scrie nimic. Cu „--tot" curăță și restul tabelelor,
 * nu doar produsele; fără el, ce găsește în altă parte doar raportează, ca să
 * nu umble singur prin conținut scris din dashboard.
 *
 * Se poate rula de câte ori vrei: ce e deja curat nu mai are ce pierde.
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

$confirma = in_array('--confirm', $argv, true);
$tot = in_array('--tot', $argv, true);

/*
 * Marcajul, așa cum arată el: paranteza dreaptă, „DE COMPLETAT" și, uneori, o
 * explicație după două puncte. Pe toate le prinde același tipar.
 */
const TIPAR = '/\[\s*DE COMPLETAT[^\]]*\]/u';

/* Tabelele curățate din oficiu. Restul se raportează, atât. */
const ALE_PRODUSELOR = ['products', 'product_extra_field_values'];

/**
 * Umple un text până la o lățime dată.
 *
 * „str_pad" numără octeți, nu litere, iar diacriticele românești ocupă doi
 * octeți fiecare: cu ea, „Cărți" ieșea din coloană cu două spații. Aici se
 * numără literele.
 */
function coloana(string $text, int $latime): string
{
    $lipsa = $latime - mb_strlen($text);

    return $lipsa > 0 ? $text . str_repeat(' ', $lipsa) : $text;
}

/**
 * Scoate marcajul dintr-un text și strânge urmele lui: spațiile rămase la
 * capăt de rând și rândurile goale peste măsură. Un fragment scos dintr-un
 * paragraf singur ar lăsa altfel trei rânduri goale în mijlocul descrierii.
 */
function fara_marcaj(string $text): string
{
    $curat = (string) preg_replace(TIPAR, '', $text);
    $curat = (string) preg_replace('/[ \t]+$/m', '', $curat);
    $curat = (string) preg_replace('/\R{3,}/u', "\n\n", $curat);

    return trim($curat);
}

/* ── Ce e de făcut ──────────────────────────────────────────────────────── */

$deSters = [];   // rânduri de valoare care sunt numai marcaj
$deScris = [];   // texte din care se scoate marcajul
$altundeva = []; // ce s-a găsit în afara produselor

$tabele = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];

foreach ($tabele as $tabel) {
    $coloane = $db->query("SHOW COLUMNS FROM `{$tabel}`")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /* Cheia primară, ca să știm ce rând anume rescriem. */
    $cheie = null;
    foreach ($coloane as $coloana) {
        if (($coloana['Key'] ?? '') === 'PRI') {
            $cheie = (string) $coloana['Field'];
            break;
        }
    }

    if ($cheie === null) {
        continue;
    }

    foreach ($coloane as $coloana) {
        $nume = (string) $coloana['Field'];

        if (!preg_match('/char|text/i', (string) $coloana['Type'])) {
            continue;
        }

        /*
         * „LIKE BINARY", nu „LIKE": colațiunea bazei nu ține cont de litere
         * mari și mici, așa că un „LIKE" simplu se oprea și la o propoziție
         * obișnuită în care scria „de completat". Marcajul e cu majuscule.
         */
        $stmt = $db->query(
            "SELECT `{$cheie}` AS cheie, `{$nume}` AS continut
             FROM `{$tabel}` WHERE `{$nume}` LIKE BINARY '%DE COMPLETAT%'"
        );
        $randuri = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($randuri as $rand) {
            $vechi = (string) $rand['continut'];
            $nou = fara_marcaj($vechi);

            if (!in_array($tabel, ALE_PRODUSELOR, true) && !$tot) {
                $altundeva[] = ['tabel' => $tabel, 'coloana' => $nume, 'cheie' => $rand['cheie']];
                continue;
            }

            /*
             * O valoare de câmp care era numai marcaj nu se golește, se șterge:
             * seed-produse.php nu scrie niciodată valori goale, deci un rând gol
             * ar fi o urmă pe care nimic nu o mai curăță.
             */
            if ($tabel === 'product_extra_field_values' && $nou === '') {
                $deSters[] = (int) $rand['cheie'];
                continue;
            }

            $deScris[] = [
                'tabel' => $tabel,
                'coloana' => $nume,
                'cheieNume' => $cheie,
                'cheie' => $rand['cheie'],
                'vechi' => $vechi,
                'nou' => $nou,
            ];
        }
    }
}

/* ── Raportul ───────────────────────────────────────────────────────────── */

if ($deSters === [] && $deScris === [] && $altundeva === []) {
    echo "Nu mai există niciun „[DE COMPLETAT]\" în baza de date.\n";
    exit(0);
}

if ($deSters !== []) {
    /* Numele produsului și al câmpului, ca raportul să se poată citi. */
    $semne = implode(',', array_fill(0, count($deSters), '?'));
    $stmt = $db->prepare(
        "SELECT p.name AS produs, f.name AS camp, v.value AS valoare
         FROM product_extra_field_values v
         JOIN products p ON p.id = v.product_id
         JOIN product_extra_fields f ON f.id = v.field_id
         WHERE v.id IN ({$semne})
         ORDER BY p.name, f.sort_order"
    );
    $stmt->execute($deSters);

    echo "\nCaracteristici de șters (rândul dispare din fișă):\n\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $rand) {
        echo '  ' . coloana((string) $rand['produs'], 44)
            . coloana((string) $rand['camp'], 21)
            . $rand['valoare'] . "\n";
    }
}

if ($deScris !== []) {
    echo "\nTexte din care se scoate marcajul:\n\n";
    foreach ($deScris as $sarcina) {
        printf("  %s #%s, „%s\"\n", $sarcina['tabel'], (string) $sarcina['cheie'], $sarcina['coloana']);
        foreach (preg_split('/\R/', $sarcina['vechi']) ?: [] as $linie) {
            if (str_contains($linie, 'DE COMPLETAT')) {
                echo '      - ' . trim($linie) . "\n";
            }
        }
    }
}

if ($altundeva !== []) {
    echo "\nGăsit și în afara produselor, neatins (rulează cu „--tot\" ca să le curețe și pe astea):\n\n";
    foreach ($altundeva as $loc) {
        printf("  %s #%s, „%s\"\n", $loc['tabel'], (string) $loc['cheie'], $loc['coloana']);
    }
}

if (!$confirma) {
    echo "\nNimic nu s-a schimbat. Rulează din nou cu „--confirm\" ca să se aplice.\n";
    exit(0);
}

/* ── Scrierea ───────────────────────────────────────────────────────────── */

$db->beginTransaction();

try {
    if ($deSters !== []) {
        $semne = implode(',', array_fill(0, count($deSters), '?'));
        $db->prepare("DELETE FROM product_extra_field_values WHERE id IN ({$semne})")->execute($deSters);
    }

    foreach ($deScris as $sarcina) {
        $db->prepare(
            "UPDATE `{$sarcina['tabel']}` SET `{$sarcina['coloana']}` = ? WHERE `{$sarcina['cheieNume']}` = ?"
        )->execute([$sarcina['nou'], $sarcina['cheie']]);
    }

    $db->commit();
} catch (Throwable $eroare) {
    $db->rollBack();
    fwrite(STDERR, "Nu s-a putut scrie: " . $eroare->getMessage() . "\n");
    exit(1);
}

printf(
    "\nGata: %d caracteristici șterse, %d texte curățate.\n",
    count($deSters),
    count($deScris)
);

/*
 * Paginile servite se țin în cache, deci fără golirea lui marcajul ar mai fi
 * apărut până la prima expirare.
 */
$golite = ResponseCache::purgePageCache();
if ($golite > 0) {
    echo "cache: {$golite} pagini golite din cache.\n";
}
