# Site nou grafoanaytis.ro

Reconstrucția site-ului `grafoanaytis.ro` (tipografie: tipar offset și digital,
ștanțare, folio/emboss, gravură laser, ambalaje), pornind de la backend-ul PHP
custom preluat din proiectul Biovitality și de la modelul vizual `exonia.ro`.

## Stadiu curent

- [x] backend + dashboard admin portate integral și rebranduite;
- [x] datele juridice și de brand ale proiectului anterior eliminate;
- [ ] front-end refăcut după modelul exonia.ro (în lucru);
- [ ] migrare conținut din WordPress-ul actual;
- [ ] materiale de la client (logo, filme, imagini produse, texte).

## Fundația tehnică

Aplicație PHP fără framework extern, potrivită pentru shared hosting:

```txt
bootstrap.php      autoload PSR-4 (App\) + .env + config
config/app.php     configurare aplicație
public/index.php   front controller, 213 rute
src/Http/          Router + controllere (Site, Admin, Auth)
src/Support/       40 servicii (DB, Settings, View, Mailer, Cache, ...)
views/site/        șabloane publice
views/admin/       dashboard (40+ ecrane)
database/          schema.sql + seed.sql (35 tabele)
scripts/           install, seed, cron-uri
```

### Module de admin folosite pentru acest site

`Pagini` (editor HTML cu preview desktop/tabletă/telefon + coș de gunoi) ·
`Galerie` (foldere, selecție multiplă, ștergere bulk) · `Design Site`
(header/footer/meniu) · `Blog` · `Email-uri` (template-uri + test) ·
`Newsletter` · `SEO` · `Setări` · `Admini` · `Jurnal activitate` ·
`Mod mentenanță` · `Cache răspunsuri`.

Modulele de magazin (produse, comenzi, coș, checkout, Stripe, FAN Courier, ERP,
puncte de fidelitate) au fost păstrate în cod, dar nu sunt expuse în front-end.

## Modelul vizual: exonia.ro

Temă `bootscore` (Bootstrap 5) + temă-copil. Structura homepage-ului:

```txt
#top_hero            hero cu imagine de fundal, titluri animate, 2 butoane
#fp_company_about    despre companie
#fp_company_vision   viziune
#fp_company_approach abordare
#exonia_numbers      cifre / indicatori
#exonia_video        secțiune video pe fundal primary
#products_categories carusel Swiper cu categorii
```

Culoarea primară a modelului este galbenul `#ffd300` (`--bs-primary`), cu
`#cca900` la hover. Clientul a cerut înlocuirea ei cu o nuanță pală de
portocaliu.

### Diferențe cerute față de model

- fără pagina `Media`;
- pagină dedicată de `Servicii`;
- secțiune de `Certificări`;
- filmulețe pe prima pagină și secțiune de `Utilaje`;
- se păstrează banner-ul de sus cu comunicare.

## Instalare

1. Copiază `.env.example` în `.env` și completează datele bazei de date.
2. Document root pe `public/` (sau `.htaccess`-ul din rădăcină).
3. Rulează instalarea:

```bash
php scripts/install.php
php scripts/seed.php
```

4. Autentificare în `/admin/login` cu `ADMIN_DEFAULT_EMAIL` și
   `ADMIN_DEFAULT_PASSWORD` din `.env`.

## De completat înainte de lansare

Modulul de acorduri GDPR (`/gdpr-agreements`) a fost preluat din proiectul
anterior, unde deservea un eveniment al altei firme. Textul juridic al acelei
firme a fost eliminat, iar datele operatorului sunt acum marcaje de completat
în `src/Http/Controllers/SiteController.php`
(`renderGdprAgreementFormSection`): denumire societate, sediu, telefon, nr.
Registrul Comerțului, CUI, reprezentant legal. Dacă modulul nu este folosit,
recomandarea este să fie eliminat complet, împreună cu rutele lui.

## Cron-uri (opțional, funcție de modulele activate)

```bash
*/5 * * * * php /cale/catre/scripts/newsletter-campaigns.php >/dev/null 2>&1
```
