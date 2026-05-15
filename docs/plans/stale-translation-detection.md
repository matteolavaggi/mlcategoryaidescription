# Piano: traduzioni “stale”, fill missing e costi

Documento di lavoro (derivato dal piano Cursor). Non è una guida utente finale.

## Contesto costi (aggiornamento 2026)

Assunzione di progetto: per il vostro mix di utilizzo, **GPT-5.4 mini** risulta circa **1/10 del costo di Google Translate**. In quel caso il ragionamento “GT quasi sempre più economico della rigenerazione LLM completa” va **ricalibrato**: conviene confrontare **sempre** i listini effettivi (token input+output vs caratteri tradotti) sul proprio account.

Implicazione prodotto: **`fill_missing`** + rigenerazione/ traduzione via **mini** sulle sole lingue che servono può essere la scelta economica dominante rispetto a GT, se il modello mini copre bene il compito (traduzione controllata da IT con prompt fisso).

## Cosa fa già il modulo (sintesi tecnica)

- `fill_missing` salta i campi già non vuoti → testi DE/ES corti ma “presenti” **non** vengono aggiornati.
- Job `translate_only` + `overwrite` nel builder può forzare Google su tutte le coppie categoria/lingua, ma la creazione job oggi filtra categorie “mancanti” e non “stale”.
- Tabella [`mlcategoryai_generation_log`](../../sql/install.php): traccia `id_category`, `id_lang`, `field_type`, `generated_at`, ecc. È usata in modo solida dove si chiama `logGeneration` (flussi OpenAI); **il batch Google `translateCategoryBatch` oggi non scrive in generation_log** → i timestamp lì non raccontano tutta la storia delle traduzioni GT.

## Obiettivo funzionale

Processare solo **mancanti** o **stale**, senza rifare tutto, usando come sorgente l’**IT attuale** (o la lingua primaria configurata).

## Opzione A — Euristica semplice: rapporto lunghezze (es. &lt; 50% dell’IT)

**Idea:** per campo (idealmente `description`, dove il segnale è forte), confrontare lunghezza testo (es. dopo `strip_tags`) target vs primaria; se `len(target) / len(primary) < soglia` (es. **0.5**) e la primaria supera un **minimo di caratteri**, marca come stale.

**Pro:** zero migrazione DB, immediata, funziona anche per contenuti importati a mano senza log.

**Contro:** lingue naturalmente più terse, meta title/description corti, HTML molto diverso → falsi positivi/negativi. Convieni usare soglia **configurabile**, minimo lunghezza sulla primaria, e limitare la regola forte a `description` (meta solo come segnale secondario o con soglia diversa).

## Opzione B — Tabella di tracking (`mlcategoryai_generation_log`)

**Idea:** stale se esiste log primario **più recente** del log (o assenza di log) per la stessa coppia categoria+campo nella lingua target.

**Pro:** allineato a “abbiamo rigenerato IT dopo aver fatto DE”.

**Contro oggi:** log incompleto per GT; contenuti mai passati dal modulo non hanno righe. Serve **estendere il logging** anche alle traduzioni GT (e idealmente salvare `source` o `model_used` distintivo) perché questa opzione sia affidabile.

## Raccomandazione

1. **Fase 1:** implementare **soglia lunghezza** (default 0.5) + minimo caratteri sulla primaria + focus su **`description`** — massima semplicità, allineata a “fill missing only” esteso a stale.
2. **Fase 2 (opzionale):** regola **ibrida**: stale se **(rapporto &lt; soglia) OR (max(generated_at) primary per campo) &gt; max(generated_at) target per campo)** dopo aver aggiunto log anche alle traduzioni GT/mini.

## Scelta UX (da confermare in implementazione)

- Modalità dedicata `fill_missing_or_stale`, oppure checkbox “Includi anche traduzioni stale” accanto a `fill_missing`.

## File toccati in implementazione (riferimento)

- [`classes/MlCategoryAiJobQueue.php`](../../classes/MlCategoryAiJobQueue.php) — `buildItemsListGoogleTranslate`, eventuali finder categorie.
- [`classes/MlCategoryAiGenerator.php`](../../classes/MlCategoryAiGenerator.php) — overwrite campo quando stale.
- [`controllers/admin/AdminMlCategoryAiAjaxController.php`](../../controllers/admin/AdminMlCategoryAiAjaxController.php) — parametri job / preview conteggi.
- [`mlcategoryaidescription.php`](../../mlcategoryaidescription.php) — chiavi `Configuration`.
- Template/JS configurazione batch.

## Todo implementativi

1. Regole stale configurabili (soglia, min primario, campi).
2. Integrazione job queue + eventuale translate-only.
3. Path overwrite per campi non vuoti quando stale.
4. UI + CHANGELOG.
5. (Opzionale) Log GT in `mlcategoryai_generation_log` per abilitare regola timestamp.

---

*Nota sul confronto costi generico Google vs LLM nel piano originale: va sempre sostituito con i prezzi effettivi del modello scelto (token vs caratteri) sul proprio listino.*
