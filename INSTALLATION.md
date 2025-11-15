# Inštalácia AceChange SEO Plugin do WordPress

## ⚠️ Dôležité: Prečo nefunguje priamy download z GitHub

Keď si stiahneš ZIP z GitHub, dostaneš **celé repository**, nie len plugin:

```
acechange-playground/
├── acechange-seo-plugin/    ← Plugin je TU
│   ├── acechange-seo.php
│   └── ...
├── README.md
└── .gitignore
```

WordPress ale očakáva:
```
acechange-seo-plugin/
├── acechange-seo.php         ← Plugin header musí byť HNEĎ tu
└── ...
```

---

## ✅ Riešenie 1: FTP Upload (NAJJEDNODUCHŠIE)

### Krok 1: Stiahni repository
```
https://github.com/cryptotrust1/acechange-playground/archive/refs/heads/claude/seo-plugin-documentation-tests-01DA1CVVs4UD9qc4AM2a7N2S.zip
```

### Krok 2: Rozbaľ ZIP
- Rozbaľ stiahnutý ZIP
- Nájdi priečinok: `acechange-playground-claude-seo-plugin.../acechange-seo-plugin/`

### Krok 3: Upload cez FTP
1. Pripoj sa na FTP (FileZilla, Cyberduck, atď.)
2. Choď do: `/wp-content/plugins/`
3. Nahraj celý priečinok `acechange-seo-plugin/`

### Krok 4: Aktivuj
1. WordPress Admin → Pluginy
2. Nájdi "AceChange SEO Plugin"
3. Klikni "Aktivovať"

**✅ HOTOVO!**

---

## ✅ Riešenie 2: Vytvor správny ZIP

### Pre Windows:

1. Stiahni a rozbaľ repository
2. Otvor priečinok `acechange-seo-plugin/`
3. Vyber **VŠETKY súbory** v tomto priečinku (nie samotný priečinok!)
4. Pravé tlačidlo → Send to → Compressed (zipped) folder
5. Premenuj na: `acechange-seo-plugin.zip`
6. Upload do WordPress: Plugins → Add New → Upload Plugin

### Pre Mac:

1. Stiahni a rozbaľ repository
2. Otvor Terminal
3. Spusti:
   ```bash
   cd ~/Downloads/acechange-playground-*/
   zip -r acechange-seo-plugin.zip acechange-seo-plugin/ -x "*.git*" -x "*/tests/*"
   ```
4. Upload `acechange-seo-plugin.zip` do WordPress

### Pre Linux:

1. Clone repository:
   ```bash
   git clone https://github.com/cryptotrust1/acechange-playground.git
   cd acechange-playground
   ```

2. Spusti build script:
   ```bash
   chmod +x build-plugin.sh
   ./build-plugin.sh
   ```

3. Upload vygenerovaný ZIP do WordPress

---

## ✅ Riešenie 3: Direct Download (READY-TO-USE ZIP)

Pripravil som pre teba hotový ZIP:

### Download link:
Vytvorím GitHub Release s hotovým ZIP súborom...

*(Momentálne musíš použiť Riešenie 1 alebo 2)*

---

## 🔍 Overenie správnej inštalácie

Po nahratí cez FTP (alebo ZIP upload) skontroluj:

1. **Cesta musí byť:**
   ```
   /wp-content/plugins/acechange-seo-plugin/acechange-seo.php
   ```

2. **V WordPress Admin → Pluginy** by si mal vidieť:
   ```
   AceChange SEO Plugin
   Version: 1.0.0
   By AceChange
   ```

3. **Ak nevidíš plugin:**
   - Skontroluj cestu (viď bod 1)
   - Skontroluj permissions: `chmod 755` na priečinok, `chmod 644` na súbory

---

## 📋 Checklist po inštalácii

- [ ] Plugin je viditeľný v Pluginy menu
- [ ] Aktivoval si plugin
- [ ] Vidíš "AceChange SEO" v admin menu
- [ ] Nastavil si všetky funkcie v Nastaveniach
- [ ] Pridal si sitemap do Google Search Console

---

## ❓ Problémy?

### "No valid plugins were found"
**Príčina:** Nesprávna štruktúra ZIP
**Riešenie:** Použite FTP upload (Riešenie 1)

### "Plugin is missing the header"
**Príčina:** Chýba súbor `acechange-seo.php`
**Riešenie:** Skontrolujte cestu, musí byť: `plugins/acechange-seo-plugin/acechange-seo.php`

### "Permission denied"
**Príčina:** Zlé file permissions
**Riešenie:**
```bash
chmod 755 /wp-content/plugins/acechange-seo-plugin/
chmod 644 /wp-content/plugins/acechange-seo-plugin/*.php
```

---

## 🚀 Rýchla inštalácia (1 príkaz)

Ak máš SSH prístup k serveru:

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/cryptotrust1/acechange-playground.git temp-repo
mv temp-repo/acechange-seo-plugin ./
rm -rf temp-repo
```

Potom aktivuj v WordPress admin paneli.

---

**Potrebuješ pomoc? Otvor GitHub Issue:**
https://github.com/cryptotrust1/acechange-playground/issues
