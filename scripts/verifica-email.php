<?php

declare(strict_types=1);

/*
 * Unde ajung mesajele trimise din site — și dacă pot ajunge.
 *
 * Adresele de destinație stau în tabela `settings`, nu în cod: valorile din
 * PHP sunt doar rezerva pentru o instalare nouă. Pe un site care rulează deja,
 * rândul din bază câștigă, oricât de corect ar fi codul. De aceea verificarea
 * se face aici, pe baza serverului, nu în depozit.
 *
 *   php scripts/verifica-email.php             // arată cum stau lucrurile
 *   php scripts/verifica-email.php --repara    // scrie adresele corecte
 *
 * Fără „--repara" nu se schimbă nimic.
 *
 * Ce NU face scriptul: nu scrie parola de SMTP. Ea se pune din Dashboard →
 * Emailuri sau direct în setări, pe server, și nu are ce căuta într-un fișier
 * din depozit.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;

const CUTIA_POSTALA = 'grafoanaytis@yahoo.com';

/** Adresele care trebuie să arate spre cutia poștală a tipografiei. */
const DE_POTRIVIT = [
    'contact_form_recipients' => CUTIA_POSTALA,
    'gdpr_operator_email' => CUTIA_POSTALA,
];

$config = require __DIR__ . '/../config/app.php';
$db = Database::connection($config['db']);

if (!$db instanceof PDO) {
    fwrite(STDERR, "Conexiunea la baza de date nu este disponibilă.\n");
    exit(1);
}

$repara = in_array('--repara', $argv, true);

function setare(PDO $db, string $cheie): ?string
{
    $stmt = $db->prepare('SELECT value FROM settings WHERE `key` = :k LIMIT 1');
    $stmt->execute(['k' => $cheie]);
    $v = $stmt->fetchColumn();
    return $v === false ? null : (string) $v;
}

function scrieSetare(PDO $db, string $cheie, string $valoare): void
{
    $stmt = $db->prepare(
        'INSERT INTO settings (`key`, value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );
    $stmt->execute(['k' => $cheie, 'v' => $valoare]);
}

echo "\n── Unde ajung mesajele ──────────────────────────────────────────\n\n";

$deReparat = [];
foreach (DE_POTRIVIT as $cheie => $trebuie) {
    $acum = setare($db, $cheie);
    $bine = $acum !== null && strtolower(trim($acum)) === strtolower($trebuie);
    printf("  %-26s %s%s\n", $cheie, $acum === null || $acum === '' ? '(nesetat)' : $acum,
        $bine ? '   ✓' : '   ← trebuie ' . $trebuie);
    if (!$bine) {
        $deReparat[$cheie] = $trebuie;
    }
}

echo "\n── Dacă pot pleca ───────────────────────────────────────────────\n\n";

$metoda = strtolower(trim((string) setare($db, 'email_delivery_method')));
$gazda = trim((string) setare($db, 'smtp_host'));
/*
 * Cheile sunt „smtp_username" și „smtp_password", nu „smtp_user"/„smtp_pass".
 * Prima versiune a scriptului le citea pe cele scurte, care nu există în
 * tabelă: raporta „(gol)" pe un server unde datele erau puse corect și
 * mesajele chiar plecau. O verificare care minte e mai rea decât niciuna.
 */
$utilizator = trim((string) setare($db, 'smtp_username'));
$parola = trim((string) setare($db, 'smtp_password'));
$expeditor = trim((string) setare($db, 'order_email_from_address'));

printf("  %-26s %s\n", 'email_delivery_method', $metoda !== '' ? $metoda : '(nesetat)');
printf("  %-26s %s\n", 'smtp_host', $gazda !== '' ? $gazda : '(gol)');
printf("  %-26s %s\n", 'smtp_username', $utilizator !== '' ? $utilizator : '(gol)');
printf("  %-26s %s\n", 'smtp_password', $parola !== '' ? '(pusă)' : '(goală)');
printf("  %-26s %s\n", 'order_email_from_address', $expeditor !== '' ? $expeditor : '(gol)');

$probleme = [];

if ($metoda === 'smtp' && $gazda === '') {
    $probleme[] = 'Metoda este „smtp", dar nu există server SMTP. Mesajele cad pe '
        . 'funcția mail() a serverului, pe care Hostinger o aruncă tăcut — adică '
        . 'nu ajung nicăieri și nimeni nu află.';
}

if ($metoda === 'smtp' && $gazda !== '' && ($utilizator === '' || $parola === '')) {
    $probleme[] = 'Serverul SMTP este scris, dar lipsește utilizatorul sau parola. '
        . 'Fără ele, autentificarea eșuează la fiecare mesaj.';
}

if ($expeditor === '' || !filter_var($expeditor, FILTER_VALIDATE_EMAIL)
    || str_ends_with(strtolower($expeditor), '@localhost')) {
    $probleme[] = 'Expeditorul „' . ($expeditor !== '' ? $expeditor : '(gol)')
        . '" nu este o adresă adevărată. Multe servere resping mesajul înainte '
        . 'să-l vadă cineva, iar Yahoo este printre ele.';
} elseif ($utilizator !== '' && strcasecmp($expeditor, $utilizator) !== 0) {
    /*
     * Plicul poartă contul SMTP, dar antetul „From" poartă adresa asta. Când
     * cele două domenii nu se potrivesc, DMARC-ul domeniului din „From" decide
     * soarta mesajului — și pentru yahoo.com decizia obișnuită este „aruncă".
     * Poate ajunge azi și poate cădea mâine în spam, fără să se schimbe nimic
     * la noi.
     */
    $probleme[] = 'Expeditorul „' . $expeditor . '" este alt domeniu decât contul '
        . 'SMTP „' . $utilizator . '". Mesajul pleacă de pe serverul unui domeniu '
        . 'și se dă drept altul, iar asta îl trece pe mâna DMARC-ului. Cel mai '
        . 'sigur este ca expeditorul să fie chiar contul SMTP; destinatarul '
        . 'rămâne oricum ' . CUTIA_POSTALA . '.';
}

if ($probleme === []) {
    echo "\n  Configurația de trimitere pare în regulă.\n";
} else {
    echo "\n";
    foreach ($probleme as $i => $problema) {
        echo '  ' . ($i + 1) . '. ' . wordwrap($problema, 68, "\n     ") . "\n\n";
    }
}

/*
 * Ultimele încercări de trimitere.
 *
 * Aici se vede adevărul, nu în setări: cui i-a plecat mesajul și dacă a plecat.
 * Tabela se scrie la fiecare încercare, reușită sau nu, împreună cu textul
 * întors de serverul de mail.
 */
echo "── Ultimele încercări de trimitere ──────────────────────────────\n\n";

try {
    $istoric = $db->query(
        'SELECT recipient, email_type, status, error_message, created_at
         FROM email_send_history
         ORDER BY id DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($istoric === []) {
        echo "  Nicio încercare înregistrată încă.\n\n";
    } else {
        foreach ($istoric as $rand) {
            printf(
                "  %s  %-28s %-8s %s\n",
                (string) $rand['created_at'],
                (string) $rand['recipient'],
                (string) $rand['status'],
                mb_substr(trim((string) ($rand['error_message'] ?? '')), 0, 46)
            );
        }
        echo "\n";
    }
} catch (Throwable) {
    echo "  Tabela de istoric încă nu există: nu s-a trimis niciun mesaj.\n\n";
}

echo "── Ce trimite site-ul ───────────────────────────────────────────\n\n";
echo "  Formularul de contact  → contact_form_recipients\n";
echo "  Cererile de ofertă     → contact_form_recipients (aceeași listă)\n";
echo "  Ambele se salvează și în baza de date, deci nu se pierd nici dacă\n";
echo "  e-mailul nu pleacă: Dashboard → Mesaje contact și Cereri ofertă.\n\n";

if ($deReparat === []) {
    echo "Adresele sunt deja bune.\n";
    exit(0);
}

if (!$repara) {
    echo "Nimic nu s-a schimbat. Adaugă --repara ca să se scrie adresele corecte.\n";
    exit(0);
}

foreach ($deReparat as $cheie => $valoare) {
    scrieSetare($db, $cheie, $valoare);
    echo "scris: {$cheie} = {$valoare}\n";
}

echo "\nGata: " . count($deReparat) . " setări scrise.\n";
