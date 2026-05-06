# Warehouse

Laravel Livewire aplikácia pre evidenciu kamenných dosiek, materiálov, skladových pohybov, inventúr a zamestnancov.

Projekt je spravený ako jednoduchý firemný skladový systém. Cieľ nebol vyrobiť prehnanú "enterprise" architektúru s vrstvami, ktoré si iba posúvajú rovnaké dáta ako horúci zemiak. Kód používa Livewire komponenty, Eloquent modely a čitateľnú Laravel logiku bez zbytočných Pointless Middleman tried.

## Čo aplikácia rieši

Firma potrebuje vedieť, aké materiály a konkrétne dosky má v sklade, kde sa nachádzajú, v akom sú stave, kto ich pridal, kedy prišli, či boli odoslané, a čo sa s nimi v systéme dialo.

Základné pojmy:

- **Material** - kategória alebo typ povrchu, napríklad Carrara, Emperador alebo Travertine.
- **Item / Slab** - konkrétny fyzický kus dosky v sklade.
- **Inventory** - kontrola skladu pomocou čiarového kódu alebo kódu položky.
- **Item flow** - príjem a odoslanie tovaru.
- **Stock movements** - audit zmien v sklade.
- **Employees** - správa používateľov a rolí.

## Hlavné funkcie

### Prihlásenie a používatelia

- Prihlásenie cez email a heslo.
- Verejná registrácia nie je dostupná.
- Používateľov vytvára iba Admin.
- Každý používateľ má rolu.
- Každý používateľ má vlastný profil.
- Používateľ si môže meniť iba profilovú fotku.
- Admin môže upravovať údaje zamestnancov.

Demo účty po seedovaní databázy:

```text
admin@example.com / password
manager@example.com / password
worker@example.com / password
```

### Roly

#### Admin

Admin má plný prístup do systému.

Môže:

- otvoriť všetky stránky,
- vytvárať zamestnancov,
- upravovať zamestnancov,
- meniť roly používateľov,
- deaktivovať alebo mazať používateľov,
- spravovať materiály,
- spravovať položky/dosky,
- používať príjem a odoslanie tovaru,
- vidieť skladové pohyby,
- vidieť históriu položiek,
- spúšťať a dokončovať inventúry.

#### Manager

Manager pracuje so skladom, ale nespravuje zamestnancov.

Môže:

- spravovať materiály,
- spravovať položky/dosky,
- prijímať tovar na sklad,
- odosielať tovar zo skladu,
- meniť stav položiek,
- vidieť skladové pohyby,
- vidieť históriu položiek,
- spúšťať a dokončovať inventúry.

#### Worker

Worker má jednoduchý pracovný prístup.

Môže:

- vidieť Dashboard,
- vidieť zoznam položiek,
- vyhľadávať položky,
- filtrovať položky podľa materiálu a stavu,
- otvoriť detail položky,
- používať inventúru,
- spustiť inventúru,
- dokončiť inventúru,
- upraviť iba vlastnú profilovú fotku.

Worker nevidí:

- Employees,
- Materials,
- Item flow,
- Stock movements,
- históriu pohybov na detaile položky,
- predchádzajúce inventúry.

Toto je zámer. Worker má robiť svoju prácu rýchlo, nie klikať po účtovníckej histórii skladu.

## Dashboard

Dashboard dáva rýchly prehľad o sklade.

Zobrazuje:

- počet materiálov,
- počet položiek/dosiek,
- počet dostupných položiek,
- počet poškodených položiek,
- počet chýbajúcich položiek,
- celkovú plochu dosiek v m2,
- posledné dosky,
- stav skladu podľa statusu,
- poslednú inventúru,
- položky vyžadujúce pozornosť.

Admin a Manager vidia aj skladovú aktivitu. Worker ju nevidí.

Hlavné tlačidlo na Dashboarde sa správa podľa roly:

- Admin / Manager: **Add or find slab**
- Worker: **Find slab**

## Items / Slabs

Stránka Items je hlavná pracovná stránka skladu.

Funkcie:

- zoznam všetkých aktívnych dosiek,
- fotka materiálu pri každej doske,
- kód dosky,
- čiarový kód,
- materiál,
- rozmery,
- vypočítaná plocha,
- umiestnenie,
- status,
- detail položky,
- vyhľadávanie,
- filtrovanie podľa statusu,
- filtrovanie podľa materiálu,
- reset filtrov,
- export do CSV.

Admin a Manager navyše môžu:

- pridať položku,
- upraviť položku,
- archivovať položku.

Worker môže položky iba prezerať, hľadať a filtrovať.

## Statusy položiek

Položky používajú jasné stavy:

- Available
- Reserved
- Sold
- Damaged
- Missing

Statusy sú riešené enumom `SlabStatus`, nie rozhádzanými stringami v Blade súboroch. To je jedna z mála abstrakcií, ktorá má zmysel, lebo chráni pred preklepmi a drží labely/farby pokope.

## Detail položky

Detail položky obsahuje praktické informácie pre sklad:

- názov/kód položky,
- materiál,
- status,
- umiestnenie,
- rozmery,
- plocha,
- zdroj,
- dodávateľ,
- dátum prijatia,
- dátum odoslania,
- poznámky,
- kto položku pridal,
- čiarový kód,
- QR kód,
- tlač štítku.

Admin a Manager vidia aj:

- históriu skladových pohybov,
- históriu inventúr pre danú položku.

Worker tieto historické časti nevidí.

## Materiály

Stránka Materials slúži na správu typov povrchov.

Funkcie:

- zoznam materiálov,
- vyhľadávanie materiálov,
- pridanie materiálu,
- úprava materiálu,
- fotka materiálu,
- aktívny/neaktívny stav,
- bezpečné mazanie materiálu.

Fotka patrí materiálu, nie konkrétnej doske. Doska zobrazuje fotku svojho materiálu. Toto je jednoduchšie a normálnejšie ako nahrávať rovnaký obrázok ku každej doske.

### Bezpečné mazanie materiálu

Ak materiál nepoužíva žiadna doska, dá sa vymazať priamo.

Ak materiál používajú existujúce dosky, aplikácia nevymaže dosky. Namiesto toho vyžiada náhradný materiál a presunie dosky naň.

Žiadne kaskádové zničenie skladu. Také veci patria do kategórie "vyzeralo to elegantne, kým to nezmazalo reálne dáta".

## Item flow

Item flow rieši reálnu skladovú prácu:

- príjem položky na sklad,
- odoslanie položky zo skladu.

Pri príjme sa zadáva:

- materiál,
- kód položky,
- barcode,
- rozmery,
- umiestnenie,
- zdroj,
- dodávateľ,
- dátum prijatia,
- poznámka.

Ak sa barcode nezadá, systém ho vygeneruje automaticky vo formáte napríklad:

```text
WH-000001
```

Pri odoslaní položky sa uloží dátum odoslania a pohyb sa zapíše do histórie.

## Čiarové kódy a QR kódy

Každá doska má vlastný čiarový kód.

Použité balíky:

- `picqer/php-barcode-generator` pre čiarové kódy,
- `endroid/qr-code` pre QR kódy.

V detaile položky sa zobrazuje:

- barcode,
- QR kód,
- skladový štítok,
- možnosť tlače štítku.

QR kód smeruje na detail konkrétnej položky.

## Inventúra

Inventúra je samostatná stránka.

Používateľ môže:

- spustiť novú inventúru,
- skenovať alebo zadať barcode,
- použiť kód položky namiesto barcode,
- potvrdiť skutočné umiestnenie,
- potvrdiť skutočný status,
- doplniť poznámku,
- označiť položku ako nájdenú,
- dokončiť inventúru,
- exportovať inventúru do CSV.

Inventúra kontroluje iba položky, ktoré majú byť fyzicky v sklade. Predané položky sa nemenia len preto, že ich niekto neoskenoval.

Dôležitá logika:

- Ak je položka nájdená a status sa nemení, zostane jej pôvodný status.
- Ak bola položka Missing a pri ďalšej inventúre sa nájde, vráti sa jej predchádzajúci status.
- Ak sa položka neoskenuje a mala byť v sklade, označí sa ako Missing.
- Sold položky inventúra neprepisuje.

To je normálne skladové správanie. Inventúra nemá magicky robiť všetko dostupným len preto, že to niekto našiel.

## Stock movements

Stock movements je audit skladových zmien.

Zobrazuje:

- zmeny položiek,
- zmeny materiálov,
- kto zmenu spravil,
- email používateľa,
- rolu používateľa,
- status,
- dátum zmeny,
- filtrovanie podľa roly,
- filtrovanie podľa dátumu,
- prepínanie medzi item changes a material changes.

Logujú sa aj bežné zmeny, napríklad:

- vytvorenie položky,
- zmena statusu,
- zmena umiestnenia,
- príjem položky,
- odoslanie položky,
- archivácia položky,
- zmeny materiálu,
- zmeny fotky materiálu,
- inventúrne výsledky.

## Employees

Employees je dostupné iba pre Admina.

Admin môže:

- zobraziť všetkých zamestnancov,
- vyhľadávať podľa mena alebo emailu,
- filtrovať podľa roly,
- vidieť počty používateľov podľa rolí,
- vytvoriť zamestnanca,
- upraviť zamestnanca,
- zmeniť rolu,
- zmeniť status účtu,
- zmeniť heslo,
- vymazať zamestnanca,
- otvoriť profil zamestnanca.

Pri vytvorení zamestnanca sa zadáva:

- meno,
- email,
- heslo,
- potvrdenie hesla,
- rola,
- telefón,
- pozícia,
- oddelenie,
- status.

Heslo je pri editácii voliteľné. Ak Admin nezadá nové heslo, staré sa nemení.

## Profil používateľa

Každý prihlásený používateľ má profil.

Profil zobrazuje:

- profilovú fotku alebo iniciály,
- meno,
- email,
- rolu,
- status účtu,
- telefón,
- pozíciu,
- oddelenie,
- dátum vytvorenia účtu,
- dátum poslednej úpravy.

Používateľ si môže:

- nahrať profilovú fotku,
- zmeniť profilovú fotku,
- odstrániť profilovú fotku.

Používateľ si nemôže sám meniť meno, email, rolu, status, pozíciu ani oddelenie. To spravuje Admin.

Validácia fotky:

- iba obrázok,
- `jpg`, `jpeg`, `png`, `webp`,
- maximálne 2 MB.

## Jazyk

Aplikácia podporuje prepínanie jazyka v hlavičke:

- EN
- SK

Texty rozhrania sú preložené cez Laravel JSON language súbor.

Názov **Warehouse** v hlavičke sa neprekladá, pretože je to názov aplikácie/brand, nie bežný text rozhrania.

## UX a dizajn

Použitý štýl:

- svetlý admin dashboard,
- jednoduché tabuľky,
- čisté formuláre,
- výrazné status badge,
- oranžový/yellow akcent,
- minimum dekorácií,
- dôraz na rýchlu skladovú prácu.

Farby:

- `#FDD07D`
- `#EB9800`
- `#333333`
- `#EDEDED`
- biela / near-white pozadie

UX rozhodnutia:

- Worker vidí iba veci potrebné na prácu.
- Admin a Manager vidia históriu, audit a správu skladu.
- Mazanie materiálu neni nebezpečné.
- Dosky sa archivujú namiesto tvrdého mazania.
- Filtre sa dajú jedným klikom resetovať.
- Inventory nerobí hlúpe status zmeny.
- Fotka je na materiáli, nie na každej doske zvlášť.

## Architektúra

Projekt používa bundle štruktúru podľa zadania:

```text
app/Bundles/Warehouse/
├── Livewire
├── Migrations
├── Models
├── Repositories
├── Routes
├── Services
└── Utils
```

Views sú tu:

```text
resources/views/Bundles/Warehouse/Livewire/
```

### Automatická registrácia bundle častí

`App\Providers\BundleServiceProvider` automaticky načítava:

- routy z `app/Bundles/*/Routes`,
- migrácie z `app/Bundles/*/Migrations`,
- views z `resources/views/Bundles/{BundleName}`,
- Livewire komponenty z `app/Bundles/*/Livewire`.

Vďaka tomu netreba ručne registrovať každý nový komponent alebo route súbor.

### Livewire komponenty

Hlavné komponenty:

- `Dashboard`
- `Slabs`
- `SlabDetails`
- `Materials`
- `ItemFlow`
- `Inventory`
- `StockMovements`
- `Employees`
- `Profile`

Komponenty používajú Eloquent priamo tam, kde je to čitateľnejšie. Nie je tu Service, ktorý iba zavolá Repository, ktoré iba zavolá Model. To je Pointless Middleman a presne taký odpad sa tu zámerne nepíše.

### Modely

Hlavné modely:

- `Material`
- `Slab`
- `StockMovement`
- `InventoryCheck`
- `InventoryCheckItem`
- `User`

Používajú sa Eloquent relationships:

- materiál má dosky,
- doska patrí materiálu,
- doska má skladové pohyby,
- doska má inventúrne záznamy,
- inventúra má položky,
- pohyb má aktéra/používateľa.

### Utils

V `Utils` sú iba veci, ktoré majú reálny dôvod:

- `SlabStatus` enum,
- `UserRole` enum.

Nie je tam `SomeHelperManagerProvider`, ktorý ukrýva jeden string. Také triedy sú len architektonická hmla.

### Services a Repositories

Priečinky existujú, pretože ich vyžadovalo zadanie.

Sú prázdne zámerne.

Nepoužívajú sa zbytočné Service/Repository triedy, ak by iba posúvali dáta ďalej bez logiky. Laravel Eloquent je na bežné query a CRUD úplne normálny nástroj.

## Použité balíky

Backend:

- Laravel 12
- Livewire 3
- WireUI
- Spatie Laravel Permission
- Picqer Barcode Generator
- Endroid QR Code

Frontend:

- Tailwind CSS
- AlpineJS
- Vite

## Lokálne spustenie

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Projekt je pripravený pre MySQL databázu `zadanie` na:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zadanie
DB_USERNAME=root
DB_PASSWORD=
```

Ak sa používajú nahraté fotky:

```bash
php artisan storage:link
```

## Overenie

Použité overenie:

```bash
php artisan test
npm run build
```

Aktuálny stav testov:

```text
31 passed
```

## Čo je na projekte dobré

- Má reálne roly a prístupové pravidlá.
- Má Admin-only správu zamestnancov.
- Worker nie je preťažený zbytočnými stránkami.
- Má praktický skladový workflow pre príjem a odoslanie.
- Má inventúru použiteľnú aj bez fyzického skenera, cez ručné zadanie barcode.
- Má barcode aj QR pre každú položku.
- Má audit zmien.
- Má profil používateľa s fotkou.
- Má bezpečné správanie pri mazaní materiálov.
- Má archiváciu položiek namiesto bezhlavého mazania.
- Má CSV exporty.
- Má dvojjazyčné rozhranie.
- Má jednoduchú bundle architektúru.
- Nemá zbytočné Pointless Middleman vrstvy.
- Kód je písaný tak, aby sa dal čítať o tretej ráno bez toho, aby človek musel otvoriť päť súborov kvôli jednému `where()`.
