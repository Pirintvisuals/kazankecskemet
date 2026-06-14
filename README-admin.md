# Blog + /admin – kezelési és telepítési útmutató

WordPress-stílusú, **adatbázis nélküli** (flat-file) blogmotor a Kazán Szerviz Kecskemét
oldalhoz. Polyák Zoltán bejelentkezik az `/admin` felületre, vizuális szerkesztővel
megírja a cikket, és a *Publikálás*-ra kattintva az azonnal megjelenik a blogon.

> **Ez PHP-t igényel a szerveren.** Statikus tárhelyen (pl. csak HTML) nem fut.
> A cPanel-es tárhelyek szinte mindig tudnak PHP-t – ezt kell megerősíteni a szolgáltatónál.

---

## 1. Mire van szükség

- **PHP 7.4 vagy újabb** (8.x ajánlott)
- Standard PHP-kiterjesztések: `dom`, `mbstring`, `json` (ezek alapból be vannak kapcsolva)
- Írható `content/` és `uploads/` mappa (a tárhelyen 0755/0775)
- **Nincs szükség MySQL adatbázisra.**

---

## 2. Helyi tesztelés (a saját gépeden)

A gépen jelenleg **nincs PHP**, ezért a teszteléshez telepíts egyet. Legegyszerűbb:

### A) XAMPP (ajánlott, kattintós)
1. Töltsd le: <https://www.apachefriends.org> → telepítsd.
2. Másold a projekt teljes tartalmát ide: `C:\xampp\htdocs\kazan\`
3. XAMPP Control Panel → **Start** az *Apache* mellett.
4. Böngészőben: <http://localhost/kazan/admin/setup.php>

### B) PHP beépített szerver (parancssor)
Ha külön telepíted a PHP-t (`php` a PATH-on), a projekt mappájában:
```
php -S localhost:8000
```
Majd: <http://localhost:8000/admin/setup.php>

> A `serve.mjs` (Node) **nem futtat PHP-t** – a blog/admin teszteléséhez a fentiek egyike kell.
> A statikus oldalak (index.html stb.) viszont PHP alatt is működnek.

---

## 3. Első beállítás (egyszer)

1. Nyisd meg: **`/admin/setup.php`**
2. Adj meg egy felhasználónevet és egy **erős jelszót** (min. 10 karakter).
3. A rendszer létrehozza az `inc/config.php` fájlt (ebben csak a jelszó *hash-e* van, maga a jelszó nem).
4. **Töröld a `admin/setup.php` fájlt** a szerverről (a felület figyelmeztet erre).
5. Lépj be: **`/admin/login.php`**

---

## 4. Napi használat

- **`/admin`** → bejelentkezés → cikklista.
- **+ Új cikk**: cím, tartalom (vizuális szerkesztő: félkövér, címsorok, listák, link, kép), kiemelt kép.
- **Vázlat** = csak neked látszik. **Publikálva** = azonnal megjelenik a blogon.
- A cikkek a `/blog.php` oldalon, az egyes cikkek a `/post.php?slug=...` címen jelennek meg.
- Szerkesztés / törlés a listából.

---

## 5. Telepítés élesbe (cPanel / FTP)

1. Töltsd fel a teljes projektet a `public_html`-be (FTP-vel vagy a cPanel Fájlkezelővel).
2. Ellenőrizd, hogy a **`content/`** és **`uploads/`** mappa írható (jogosultság 0755 vagy 0775).
3. Nyisd meg: `https://azoldal.hu/admin/setup.php` → hozd létre az admint.
4. **Töröld a `admin/setup.php`-t.**
5. Ellenőrizd, hogy a védelmek élnek (lásd lejjebb). Apache-on a `.htaccess` automatikusan érvényes.

A `kazanszervizkecskemet.hu/admin` címen lesz elérhető a felület.

---

## 6. Biztonság (mit csináltam)

- **Jelszó**: `password_hash` (bcrypt) – sosem tároljuk nyíltan.
- **Munkamenet**: HttpOnly + SameSite=Lax süti, HTTPS alatt Secure; belépéskor session-id csere.
- **CSRF**: minden űrlap és a képfeltöltés token-védett.
- **Brute-force védelem**: 6 hibás belépés után átmeneti zárolás (15 perc).
- **XSS**: a szerkesztő HTML-jét szerveroldalon *allow-list* szűri (`inc/sanitize.php`) – `<script>`, `on*`, `javascript:` stb. eltávolítva.
- **Képfeltöltés**: valódi kép-ellenőrzés (nem csak kiterjesztés), csak JPG/PNG/WebP/GIF, max 5 MB, véletlen fájlnév, és az `uploads/`-ban a szkriptfuttatás `.htaccess`-szel letiltva.
- **Mappavédelem**: `inc/` és `content/` közvetlen HTTP-elérése tiltva; a PHP-fájlok `defined('APP')` őrrel védettek.

> A védelmek nagy része nem csak `.htaccess`-re épül, így akkor is működik a lényeg, ha a tárhely nem olvas `.htaccess`-t.

---

## 7. Mentés / biztonsági másolat

Minden tartalom fájlokban van – a biztonsági mentés egyszerű:
- **`content/posts/`** → a cikkek (JSON)
- **`uploads/`** → a feltöltött képek

Ezt a két mappát időnként másold le. (Adatbázis-mentés nem kell.)

---

## 8. Fájltérkép

```
inc/
  bootstrap.php   közös betöltő, útvonalak
  helpers.php     escape, slug, dátum, olvasási idő
  sanitize.php    HTML allow-list fertőtlenítő (XSS-védelem)
  posts.php       flat-file bejegyzés-tárolás (CRUD)
  uploads.php     biztonságos képfeltöltés
  auth.php        belépés, munkamenet, CSRF, throttle
  config.php      (setup.php hozza létre – titkos, repóba nem kerül)
admin/
  setup.php       egyszeri beállítás (utána törlendő)
  login.php / logout.php
  index.php       cikklista (dashboard)
  edit.php        szerkesztő (TinyMCE)
  delete.php      törlés
  upload.php      soron belüli képfeltöltés végpont
  _layout.php     admin fejléc/lábléc
  admin.css       admin stílus
content/posts/    cikkek JSON-ban (2 minta cikk mellékelve)
uploads/          feltöltött képek
blog.php          nyilvános bloglista (a régi blog.html helyett)
post.php          nyilvános cikkoldal
```

---

## 9. Megjegyzések

- A TinyMCE szerkesztő a jsDelivr CDN-ről, GPL licenccel töltődik (nincs API-kulcs, nincs díj).
- A korábbi `blog.html` (statikus minta) helyett mostantól a dinamikus `blog.php` él; a „Blog” menüpont minden oldalon erre mutat.
- Szép URL (`/blog/cikk-cime`) később `.htaccess` átírással hozzáadható; jelenleg a `post.php?slug=...` forma minden tárhelyen biztosan működik.
