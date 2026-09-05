<?php

declare(strict_types=1);

/*
 * Catalogul de produse.
 *
 * Fotografiile sunt cele trimise de client, alese după ce le-am privit, nu după
 * nume. Textele descriu ce se vede în ele și ce spune clientul pe site-ul lui
 * vechi. Cifrele marcate [DE COMPLETAT] le confirmă el.
 *
 * Categoriile sunt de două feluri și un produs poate fi în amândouă:
 *   - familie de produs („Ambalaje cosmetice"), pentru pagina de produse;
 *   - serviciu („Lipire cutii"), pentru drumul serviciu → produse.
 */

return [
    'categorii' => [
        // Familii de produse
        'ambalaje-alimentare' => 'Ambalaje alimentare',
        'ambalaje-cosmetice' => 'Ambalaje cosmetice',
        'ambalaje-farma' => 'Ambalaje farma',
        'cutii-cu-fereastra' => 'Cutii cu fereastră',
        'etichete' => 'Etichete',
        'tiparituri' => 'Tipărituri și publicații',

        // Servicii executate pentru parteneri
        'stantare-folio-embos' => 'Ștanțare, folio și emboss',
        'lipire-cutii' => 'Lipire cutii',
        'aplicare-ferestre' => 'Aplicare ferestre',

        // Servicii pentru clienți finali. Slug-ul este identic cu al paginii
        // de serviciu, ca lista de produse să se lege singură.
        'tipar-offset' => 'Tipar offset',
        'tipar-digital' => 'Tipar digital',
        'ambalaje' => 'Ambalaje',
        'servicii-de-stantare' => 'Servicii de ștanțare',
        'inscriptionare-folio-emboss' => 'Inscripționare folio / emboss',
    ],

    'campuri' => [
        'dimensiuni' => 'Dimensiuni',
        'material' => 'Material',
        'tiraj' => 'Tiraj minim',
        'personalizare' => 'Personalizare',
        'finisaje' => 'Finisaje',
        'termen' => 'Termen de execuție',
    ],

    /*
     * Ordinea de aici este ordinea din catalog.
     *
     * Clientul a dat lista numerotată la punctul 15 din revizie, de la cutiile
     * de cofetărie până la etichetele autoadezive. Scriptul de semănare o
     * traduce în coloana „sort_order", din zece în zece, iar pagina Produse
     * sortează după ea. Mutarea unui produs se face mutându-l aici.
     *
     * Față de lista de dinainte:
     *   - „Cutii cu fereastră pentru retail" se numește acum „Cutii cu
     *     fereastră PVC";
     *   - „Cărți, albume și lucrări de specialitate" s-a despărțit în „Cărți"
     *     și „Albume", cu fotografiile pe care le-a numit clientul: coperta
     *     Lidianca la cărți, Adormirea Maicii Domnului la albume;
     *   - fotografia jurnalului de evenimente a ieșit din „Mape de prezentare"
     *     și și-a primit produsul ei;
     *   - „Cutii pentru dulciuri și cadouri alimentare" a fost scos;
     *   - trei produse sunt noi: ambalajele pentru sticle de vin, etichetele
     *     pentru textile și jurnalul de evenimente.
     *
     * Fotografiile noi stau în „/assets/img/produse", nu în galerie: „uploads"
     * este ignorat de git și n-ar ajunge pe server la „git pull".
     */
    'produse' => [
        [
            'slug' => 'cutii-cu-fereastra-pentru-cofetarie',
            'nume' => 'Cutii cu fereastră pentru cofetărie',
            'subtitlu' => 'Fereastra din folie PVC se aplică automat, iar prăjitura se vede fără să fie atinsă',
            'descriere' => "Cutii de carton cu mâner și fereastră transparentă, pentru cofetării, patiserii și laboratoare de dulciuri.\n\nFereastra se aplică pe mașină automată, dintr-o folie PVC croită după decupajul cutiei, deci marginea rămâne dreaptă pe tot tirajul. Cartonul se tipărește offset înainte de ștanțare, așa că imprimeul intră până în muchia pliului.\n\n[DE COMPLETAT: fotografii proprii cu cutii cu fereastră executate de Grafoanaytis]",
            'aplicabilitate' => "Cofetării și patiserii|Laboratoare de dulciuri|Magazine cu vânzare la bucată",
            'imagine' => 'prajituri.webp',
            'galerie' => ['prajituri.webp', 'prajituri2.webp', 'alimtenar.webp', 'mochi.webp'],
            'categorii' => ['ambalaje-alimentare', 'cutii-cu-fereastra', 'aplicare-ferestre', 'tipar-offset', 'ambalaje'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton duplex sau microondulat, contact alimentar',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset până la formatul 50×70 cm, policromie',
                'finisaje' => 'Lăcuire, plastifiere, fereastră din folie PVC',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'ambalaje-pentru-produse-alimentare',
            'nume' => 'Ambalaje pentru produse alimentare',
            'subtitlu' => 'Cutii și tăvi pentru produse care ajung direct la raft',
            'descriere' => "Ambalaje de carton pentru produse alimentare ambalate la producător: cutii pliate, tăvi și ambalaje de grup.\n\nMaterialele sunt potrivite pentru contact alimentar, iar imprimeul se face cu cerneluri fără migrare.",
            'aplicabilitate' => "Producători de alimente|Retail alimentar|Ambalaje de grup",
            'imagine' => 'alimentar2.webp',
            'galerie' => ['alimentar2.webp', 'alimtenar.webp', 'dulciuri.webp', 'mochi.webp'],
            'categorii' => ['ambalaje-alimentare', 'lipire-cutii', 'tipar-offset', 'ambalaje', 'servicii-de-stantare'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton duplex și microondulat, contact alimentar',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Lăcuire, ștanțare, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'ambalaje-pentru-sticle-de-vin',
            'nume' => 'Ambalaje pentru sticle de vin',
            'subtitlu' => 'Cutii pentru o sticlă, cu carton negru și folio auriu',
            'descriere' => "Cutii pliate pentru sticle de vin și băuturi fine, tipărite pe carton grafic și înnobilate cu folio la cald.\n\nConturul și decupajul se ștanțează dintr-o singură trecere, iar cutia se livrează plană, gata de format. Unde sticla trebuie să se vadă, se aplică fereastră din folie PVC pe mașină automată.",
            'aplicabilitate' => "Crame și producători de vin|Cadouri corporate|Băuturi premium",
            'imagine' => '/assets/img/produse/ambalaje-sticle-vin-1.webp',
            'galerie' => [
                '/assets/img/produse/ambalaje-sticle-vin-1.webp',
                '/assets/img/produse/ambalaje-sticle-vin-2.webp',
                '/assets/img/produse/ambalaje-sticle-vin-3.webp',
            ],
            'categorii' => ['ambalaje-alimentare', 'stantare-folio-embos', 'lipire-cutii', 'tipar-offset', 'ambalaje', 'inscriptionare-folio-emboss'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton grafic 300–350 g, cu sau fără cașerare',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie plus culori speciale',
                'finisaje' => 'Folio la cald, emboss, lăcuire, ștanțare, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'suporturi-pentru-bauturi',
            'nume' => 'Suporturi și ambalaje pentru băuturi',
            'subtitlu' => 'Suporturi de carton pentru sticle și pahare, cu mâner ștanțat',
            'descriere' => "Suporturi pentru bere și băuturi la sticlă, suporturi pentru pahare și cutii pentru vin.\n\nMânerul se ștanțează odată cu conturul, deci nu apare o operație în plus care să scumpească tirajul.",
            'aplicabilitate' => "Berării și crame|HoReCa|Cadouri corporate",
            'imagine' => 'bere.webp',
            'galerie' => ['bere.webp', 'bere2.webp', 'cupholder1.webp', 'vin1.webp'],
            'categorii' => ['ambalaje-alimentare', 'stantare-folio-embos', 'tipar-offset', 'ambalaje', 'servicii-de-stantare'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton microondulat sau duplex gros',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Ștanțare cu mâner, lăcuire',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'cutii-pentru-ceai-si-infuzii',
            'nume' => 'Cutii pentru ceai și infuzii',
            'subtitlu' => 'Cutii înalte, cu pliuri care țin plicurile drepte până la ultimul',
            'descriere' => "Cutii pentru ceai la plic și infuzii, cu clapetă de închidere repetată.\n\nSe livrează plane și lipite, gata de umplut pe linia clientului.",
            'aplicabilitate' => "Ceaiuri și infuzii|Condimente|Produse bio și naturale",
            'imagine' => 'matcha.webp',
            'galerie' => ['matcha.webp', 'matcha2.webp', 'ceai.webp', 'sirin.webp'],
            'categorii' => ['ambalaje-alimentare', 'lipire-cutii', 'tipar-offset', 'ambalaje'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton duplex, contact alimentar',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Lăcuire, plastifiere, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'ambalaje-pentru-produse-farmaceutice',
            'nume' => 'Ambalaje pentru produse farmaceutice',
            'subtitlu' => 'Cutii pliate cu text de prospect tipărit lizibil la corp mic',
            'descriere' => "Cutii pentru medicamente, suplimente și dispozitive medicale, livrate plane și lipite pe trei puncte.\n\nTextul obligatoriu se tipărește offset, unde corpul mic rămâne citibil, iar cutiile se pot livra cu ștanțare pentru Braille.",
            'aplicabilitate' => "Medicamente și suplimente|Dispozitive medicale|Produse de farmacie",
            'imagine' => 'ambalaj-farma-3.webp',
            'galerie' => ['ambalaj-farma-3.webp', 'ambalaj-farma-4.webp', 'ambalaj-farma.webp', 'ambalaj-farma2.webp', 'blister.webp'],
            'categorii' => ['ambalaje-farma', 'lipire-cutii', 'stantare-folio-embos', 'tipar-offset', 'ambalaje', 'servicii-de-stantare'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton grafic alb, cerneluri fără migrare',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Lăcuire, ștanțare, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'cutii-cu-fereastra-pvc',
            'nume' => 'Cutii cu fereastră PVC',
            'subtitlu' => 'Produsul se vede din raft, fără să fie scos din ambalaj',
            'descriere' => "Cutii pliate cu fereastră din folie PVC, pentru produse care se vând la raft: jucării, articole de papetărie, produse de îngrijire, seturi cadou.\n\nFereastra se aplică automat, iar decupajul se ștanțează odată cu conturul cutiei, deci nu apare o operație în plus care să scumpească tirajul.",
            'aplicabilitate' => "Retail și magazine de proximitate|Jucării și papetărie|Seturi cadou",
            'imagine' => 'geam5.webp',
            'galerie' => ['geam5.webp', 'geam.webp', 'geam2.webp', 'geam4.webp', 'geam8.webp'],
            'categorii' => ['cutii-cu-fereastra', 'aplicare-ferestre', 'ambalaje', 'tipar-offset'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton duplex sau grafic, folie PVC pentru fereastră',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Ștanțare, aplicare fereastră, lăcuire, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'cutii-pentru-cosmetice-cu-folio',
            'nume' => 'Cutii pentru cosmetice cu folio și emboss',
            'subtitlu' => 'Folio auriu la cald și relief pe carton negru, pentru produse care se vând din raft',
            'descriere' => "Cutii pliate pentru creme, seruri și seturi cadou, cu imprimare folio la cald și emboss.\n\nFolio-ul și relieful se execută pe mașină automată de ștanțat și imprimat, la 10.000 de coli pe oră, deci un tiraj mare nu schimbă aspectul primei cutii față de ultima.",
            'aplicabilitate' => "Cosmetice și îngrijire personală|Seturi cadou|Produse de raft premium",
            'imagine' => 'box3.webp',
            'galerie' => ['box3.webp', 'box2.webp', 'box1.webp', 'cosmetic2.webp'],
            'categorii' => ['ambalaje-cosmetice', 'stantare-folio-embos', 'lipire-cutii', 'tipar-offset', 'ambalaje', 'servicii-de-stantare', 'inscriptionare-folio-emboss'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton grafic 250–350 g, cu sau fără cașerare',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie plus culori speciale',
                'finisaje' => 'Folio la cald, emboss, lăcuire, plastifiere mată sau lucioasă',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'etichete-si-ambalaje-pentru-textile',
            'nume' => 'Etichete și ambalaje pentru textile',
            'subtitlu' => 'Etichete agățate, benzi și cutii pentru articole de îmbrăcăminte',
            'descriere' => "Etichete de produs, benzi de ambalare și cutii de prezentare pentru șosete, tricotaje și articole de protecție.\n\nSe tipăresc offset sau digital, după tiraj, și se ștanțează pe contur, cu decupajul de agățare tăiat odată cu forma — deci fără o operație în plus care să scumpească tirajul.",
            'aplicabilitate' => "Producători de textile|Retail de îmbrăcăminte|Articole tehnice și de protecție",
            'imagine' => '/assets/img/produse/etichete-ambalaje-textile.webp',
            'galerie' => ['/assets/img/produse/etichete-ambalaje-textile.webp'],
            'categorii' => ['etichete', 'tipar-offset', 'tipar-digital', 'servicii-de-stantare', 'ambalaje'],
            'campuri' => [
                'dimensiuni' => 'După forma produsului și a stenderului',
                'material' => 'Carton grafic, hârtie autoadezivă, folie',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset sau digital, policromie',
                'finisaje' => 'Ștanțare pe contur, decupaj de agățare, lăcuire, plastifiere',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'cutii-pentru-cadouri-si-obiecte-de-cult',
            'nume' => 'Cutii pentru cadouri și obiecte de cult',
            'subtitlu' => 'Cutii rigide și pliate, cu imprimeu pe toată suprafața',
            'descriere' => "Cutii pentru cadouri, obiecte de cult și produse de colecție.\n\nSe execută atât pliate, cât și rigide, cu cașerare.",
            'aplicabilitate' => "Magazine de cadouri|Instituții de cult|Produse de colecție",
            'imagine' => 'tamaie.webp',
            'galerie' => ['tamaie.webp', 'tamaie2.webp', 'cutie-123.webp'],
            'categorii' => ['ambalaje-cosmetice', 'stantare-folio-embos', 'lipire-cutii', 'tipar-offset', 'ambalaje', 'inscriptionare-folio-emboss'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: dimensiunile uzuale]',
                'material' => 'Carton grafic, cu sau fără cașerare',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Folio, emboss, lăcuire, lipire automată',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'carti',
            'nume' => 'Cărți',
            'subtitlu' => 'Editură și tipografie în același loc, deci un singur interlocutor',
            'descriere' => "Manuale, monografii, lucrări de specialitate și beletristică, în broșare lipită sau cusută.\n\nEditura Grafoanaytis, înființată în 2006, se ocupă de partea editorială, iar tipografia de execuție — de la ISBN până la tirajul livrat.",
            'aplicabilitate' => "Edituri și autori|Instituții de cultură|Lucrări academice",
            'imagine' => 'carte4.webp',
            'galerie' => ['carte4.webp', 'carte-3.webp', 'carte-groasa.webp'],
            'categorii' => ['tiparituri', 'tipar-offset', 'tipar-digital'],
            'campuri' => [
                'dimensiuni' => 'Formate uzuale de carte și la cerere',
                'material' => 'Hârtie offset, volumetrică sau cretată',
                'tiraj' => 'De la un exemplar, în tipar digital',
                'personalizare' => 'Tipar offset pentru tiraje medii și mari, digital pentru tiraje mici',
                'finisaje' => 'Broșare lipită sau cusută, copertă cartonată, folio',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'albume',
            'nume' => 'Albume',
            'subtitlu' => 'Albume ilustrate, pe hârtie cretată, cu copertă cartonată',
            'descriere' => "Albume de artă, monografii ilustrate și lucrări comemorative, tipărite pe hârtie cretată, unde fotografia își ține culoarea.\n\nCoperta se poate casera, plastifia sau înnobila cu folio, iar interiorul se coase, nu se lipește: un album deschis stă deschis.",
            'aplicabilitate' => "Instituții de cultură și de cult|Monografii locale|Lucrări aniversare",
            'imagine' => 'cart2.webp',
            'galerie' => ['cart2.webp', 'cart1.webp'],
            'categorii' => ['tiparituri', 'tipar-offset', 'stantare-folio-embos', 'inscriptionare-folio-emboss'],
            'campuri' => [
                'dimensiuni' => 'Formate de album și la cerere',
                'material' => 'Hârtie cretată pentru interior, carton pentru copertă',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Coasere, copertă cartonată, cașerare, folio, plastifiere',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'agende-si-calendare-personalizate',
            'nume' => 'Agende și calendare personalizate',
            'subtitlu' => 'Agende cusute și calendare, cu coperta personalizată',
            'descriere' => "Agende, calendare de perete și de birou, tipărite și finisate în aceeași hală.\n\nCoperta poate primi folio, emboss sau plastifiere soft-touch.",
            'aplicabilitate' => "Cadouri corporate|Instituții și administrație|Campanii de final de an",
            'imagine' => 'agenda3.webp',
            'galerie' => ['agenda3.webp', 'agenda1.webp', 'agenda2.webp', 'agenda4.webp'],
            'categorii' => ['tiparituri', 'stantare-folio-embos', 'tipar-offset', 'inscriptionare-folio-emboss'],
            'campuri' => [
                'dimensiuni' => 'A4, A5 și formate la cerere',
                'material' => 'Hârtie offset și carton pentru coperți',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Folio, emboss, plastifiere, coasere sau spiralare',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'mape-de-prezentare',
            'nume' => 'Mape de prezentare',
            'subtitlu' => 'Mape cu buzunar ștanțat, pentru documente și oferte',
            'descriere' => "Mape de prezentare cu unul sau două buzunare, ștanțate și bigate, livrate plane.\n\nSe pot finisa cu folio sau plastifiere soft-touch.",
            'aplicabilitate' => "Prezentări comerciale|Conferințe și târguri|Instituții",
            'imagine' => 'mapa.webp',
            'galerie' => ['mapa.webp', 'mapa2.webp'],
            'categorii' => ['tiparituri', 'stantare-folio-embos', 'tipar-offset', 'servicii-de-stantare'],
            'campuri' => [
                'dimensiuni' => 'Pentru documente A4',
                'material' => 'Carton grafic 300–350 g',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Ștanțare, biguire, folio, plastifiere',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'jurnal-evenimente-imobil',
            'nume' => 'Jurnal evenimente imobil',
            'subtitlu' => 'Registrul de evenimente al imobilului, tipărit și broșat',
            'descriere' => "Jurnalul de evenimente al imobilului, executat pentru InfoCons: interior tipărit, copertă plastifiată și broșare.\n\nSe reia în tiraje repetate, cu aceeași machetă, deci exemplarul de anul acesta arată ca cel de anul trecut.",
            'aplicabilitate' => "Asociații de proprietari|Administratori de imobile|Instituții",
            'imagine' => 'evenimente-imobil.webp',
            'galerie' => ['evenimente-imobil.webp'],
            'categorii' => ['tiparituri', 'tipar-offset'],
            'campuri' => [
                'dimensiuni' => '[DE COMPLETAT: formatul]',
                'material' => 'Hârtie offset pentru interior, carton pentru copertă',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Plastifiere, biguire, broșare',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'reviste-brosuri-si-pliante',
            'nume' => 'Reviste, broșuri și pliante',
            'subtitlu' => 'Tiraje medii și mari, livrate în cel mai scurt timp',
            'descriere' => "Reviste, cataloage, broșuri și pliante, capsate sau lipite.\n\nCapacitățile de producție permit tiraje medii și mari fără să crească termenul.",
            'aplicabilitate' => "Campanii de promovare|Cataloage de produs|Publicații periodice",
            'imagine' => 'revista2.webp',
            'galerie' => ['revista2.webp', 'revista1.webp', 'revista3.webp', 'brosura.webp', 'brosura2.webp'],
            'categorii' => ['tiparituri', 'tipar-offset'],
            'campuri' => [
                'dimensiuni' => 'A4, A5, DL și formate la cerere',
                'material' => 'Hârtie cretată sau offset',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset, policromie',
                'finisaje' => 'Capsare, broșare lipită, biguire, plastifiere',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
        [
            'slug' => 'etichete-autoadezive',
            'nume' => 'Etichete autoadezive',
            'subtitlu' => 'Etichete pe rolă sau la coală, pentru borcane, sticle și cutii',
            'descriere' => "Etichete pentru produse alimentare, cosmetice și industriale, tipărite offset sau digital, în funcție de tiraj.\n\nPentru tiraje mici, tiparul digital scoate costul de plăci din calcul.",
            'aplicabilitate' => "Producători mici și mijlocii|Produse artizanale|Serii scurte și repetate",
            'imagine' => 'etichete3.webp',
            'galerie' => ['etichete3.webp', 'etichete2.webp', 'etichete4.webp', 'etichete5.webp'],
            'categorii' => ['etichete', 'tipar-digital', 'tipar-offset'],
            'campuri' => [
                'dimensiuni' => 'La cerere, după forma recipientului',
                'material' => 'Hârtie autoadezivă albă sau kraft, folie',
                'tiraj' => '[DE COMPLETAT: tirajul minim]',
                'personalizare' => 'Tipar offset sau digital, policromie',
                'finisaje' => 'Lăcuire, plastifiere, ștanțare pe contur',
                'termen' => '[DE COMPLETAT]',
            ],
        ],
    ],
];
