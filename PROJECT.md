## Piano implementazione modulo `mlgooglenoindex`

---

### Obiettivo

Aggiungere `<meta name="robots" content="noindex,follow">` su tutte le pagine con paginazione (`page > 1`) o filtri attivi, per eliminare l'index bloat mantenendo il flusso di link equity.

---

### Struttura modulo

```
/modules/mlgooglenoindex/
├── mlgooglenoindex.php
├── config.xml
└── logo.png
```

---

### Logica

| Condizione | Robots |
|------------|--------|
| `?page=1` o nessun param | index,follow (default) |
| `?page=2+` | noindex,follow |
| `?order=`, `?SubmitCurrency=`, `?id_currency=` | noindex,follow |
| Filtri AmazingFilter (`/f-*` o `?af=`) | noindex,follow |

---

### Hook

**`displayHeader`** — inietta il meta tag dentro `<head>`

---

### Step implementazione

1. Creare cartella `/modules/mlgooglenoindex/`
2. Creare i file `mlgooglenoindex.php` e `config.xml`
3. Installare da backoffice
4. Verificare hook in Design → Posizioni
5. Testare con `curl` su URL paginati/filtrati
6. Clear cache
7. Monitorare Search Console (settimane successive)

---

### Test validazione

```bash
# Page 1 → index
curl -s "https://www.sixrace.it/en/brakes" | grep -i "noindex"
# (nessun risultato = OK)

# Page 2+ → noindex
curl -s "https://www.sixrace.it/en/brakes?page=5" | grep -i "noindex"
# <meta name="robots" content="noindex,follow">
```

---

Procedo a scrivere il codice completo del modulo?