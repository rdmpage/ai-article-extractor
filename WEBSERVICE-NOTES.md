# Web Service Architecture Notes

Notes from a design discussion, March 2026. Covers turning this CLI-based pipeline into a deployable web service.

---

## Current Architecture Summary

A PHP CLI pipeline that:
1. Fetches BHL item/page metadata + OCR text → flat JSON file cache
2. Builds a "doc" object (`{ItemID}.json`) that all scripts mutate sequentially
3. Uses ChatGPT (`gpt-4o-mini`) to extract TOC entries and article metadata
4. Exports RIS/TSV

`go.php` orchestrates by calling `system("php tool.php {item}.json")` in sequence. All state is flat files on disk.

---

## Key Challenges for Web Deployment

### Long-running processes
The biggest structural issue. A typical run involves:
- `bhl_fetch.php` — can take minutes to hours (throttled with `usleep()`)
- Multiple OpenAI calls (~1–5s each) × N articles per volume
- Optional local image download + OCR per page

None of these can run synchronously in an HTTP request. A **job queue** is required (background worker polling a jobs table).

### OpenAI API key
Options, in order of simplicity:
- **Users supply their own key** — entered in the submission form, stored only for the duration of the job, never persisted. Least risk, most friction.
- **Operator absorbs the cost** — needs rate limiting, auth, and usage caps.
- **Charge per job** — Stripe + metered billing. Significant added complexity.

### Flat-file state with no concurrency control
Every script reads and writes the same `{ItemID}.json`. Two concurrent jobs for the same ItemID would corrupt each other. Solution: **per-job working directories** (temp dir keyed by job ID), deleted on completion.

### macOS-only OCR binary (`./OCR`)
The `macOCR` binary uses Apple's Vision framework and won't run on Linux. See OCR section below for alternatives. Re-OCR is optional — BHL already provides OCR text via its API, which is good enough for most volumes.

### Hardcoded per-title logic
Dozens of `switch ($doc->bhl_title_id)` blocks inject per-journal prompts, ISSN corrections, etc. These need to be externalised to a config file or database table. The knowledge is valuable — it just needs a better home.

### Code duplication across pipeline scripts
`doc2toc.php`, `toc2parts.php`, `issue2parts.php`, `article2parts.php`, `doc2page_series.php` all implement variations of the same loop:
1. Find relevant pages
2. Get OCR text
3. Build a prompt (with per-title customisation inlined)
4. Call OpenAI
5. Parse/clean JSON response
6. Write back to doc

The clean version is a single `extract()` function that takes page text, a prompt template, and an expected JSON schema. Per-journal knowledge becomes **data** (a config entry per journal) rather than code scattered across `switch` blocks.

---

## Recommended Architecture (Minimal Viable)

Keep the PHP pipeline logic, add a thin web layer and job queue.

```
Browser → PHP web form (submit BHL ItemID + OpenAI key)
              ↓
          jobs table in SQLite
              ↓
          worker (PHP daemon or cron) picks up pending jobs
              ↓
          pipeline runs in isolated temp dir per job
              ↓
          result (RIS/TSV) available for download for 24h, then cleaned up
```

### Two-container Docker Compose setup

```
web (nginx + php-fpm)   — serves submission form and result download
worker (PHP CLI daemon) — polls job queue, runs pipeline per job
```

Both share a named volume `/data` containing:
- `cache.db` — SQLite database (BHL cache + chat cache + job queue)
- `jobs/` — per-job working directories

---

## SQLite Migration (do this before Docker)

Replace the flat file caches with SQLite. This simplifies the volume story (one file, one mount) and enables cache sharing across jobs.

### BHL API cache
Currently thousands of `cache/{TitleID}/page-{PageID}.json` files. Replace with:

```sql
CREATE TABLE bhl_cache (
    entity_type TEXT,      -- 'title', 'item', 'page', 'part'
    entity_id   INTEGER,
    fetched_at  INTEGER,
    data        TEXT,      -- JSON blob
    PRIMARY KEY (entity_type, entity_id)
);
```

Logic in `bhl.php` changes from `file_exists()` / `file_get_contents()` to `SELECT` / `INSERT OR REPLACE`. The cache is then shared across all jobs automatically.

### Chat cache
Currently MD5-keyed JSON files in `chat-cache/`. Replace with:

```sql
CREATE TABLE chat_cache (
    prompt_hash TEXT PRIMARY KEY,  -- MD5 of prompt + text
    response    TEXT,
    created_at  INTEGER
);
```

### Working doc
Keep `{ItemID}.json` as a file per job in the temp dir — it's mutable, job-scoped, and doesn't benefit from being in SQLite.

---

## Docker Setup

### What the Dockerfile needs
- PHP 8.2 + php-fpm
- nginx
- supervisord (manages both web and worker processes)
- SQLite (`pdo_sqlite` extension)
- Tesseract (optional fallback OCR — see below)
- No macOS binaries

### Per-journal config
The `switch ($doc->bhl_title_id)` knowledge should live in a YAML or JSON config file (or a SQLite table) that can be contributed to without touching PHP code. This schema should be designed before writing the new repo.

### Batch use case
A list of journals/items to process overnight is a good early target — simpler than interactive web UI (no frontend needed, just a queue and worker), and forces the right architectural decisions around job isolation early.

---

## OCR Options

The macOS `./OCR` binary uses Apple's **Vision framework** — neural-network based, notably better than Tesseract on historical and degraded text.

### Cross-platform alternatives

| Option | Type | Accuracy | Notes |
|---|---|---|---|
| **Tesseract 5** | Local, free | Good | LSTM-based, practical default for Docker |
| **Surya** | Local, free | Better than Tesseract | Python, 2024, good on historical text |
| **EasyOCR** | Local, free | Reasonable | Slow without GPU |
| **Google Vision / AWS Textract / Azure** | Cloud, paid | Very good | Adds cost and external dependency |
| **GPT-4o vision** | Cloud, paid | Excellent | See below |

### Recommended approach

1. **Default: use BHL's own OCR** — already there, free, good enough for most volumes
2. **When re-OCR needed: GPT-4o vision** — send the page image URL directly, get back clean text; avoids adding a separate OCR dependency
3. **Tesseract 5** — if a fully offline/free Docker option is needed

### Sending images to GPT-4o from PHP

Same `/v1/chat/completions` endpoint, same `openai_call()` function. The only change is that `content` becomes an array of typed parts:

```php
$data = [
    "model" => "gpt-4o",
    "messages" => [[
        "role" => "user",
        "content" => [
            [
                "type" => "image_url",
                "image_url" => ["url" => $image_url]  // BHL image URLs are public
            ],
            [
                "type" => "text",
                "text" => $prompt
            ]
        ]
    ]],
    "max_tokens" => 4096
];
```

BHL page images are publicly accessible URLs so they can be passed directly — no need to download the image first.

### Combining OCR + extraction into one call

Since the image goes to the same model doing the metadata extraction, the OCR step and the extraction step can be collapsed into one API call per page:

```php
$prompt = "Extract article metadata from this journal page as JSON with keys:
           title, authors, journal, volume, issue, pages, year.
           If this is not an article start page, return null.";
```

This is simpler than the current two-step flow (download image → run OCR → send text to AI) and GPT-4o reads the actual image rather than potentially garbled OCR text.

---

## New Repo Recommendations

The current repo is a research workspace and shows it (toggled `if (1)`/`if (0)` blocks, hardcoded arrays, scripts mutating shared files). Start fresh rather than retrofit.

### Worth carrying over as-is
- `swa.php` — Smith-Waterman implementation, solid and self-contained
- `parse-volume.php` — clean utility, no side effects
- Core logic from `openai.php` and `bhl.php` — sound, just needs file cache replaced with SQLite
- `shared.php` — small utility functions

### Needs a real rewrite
- `go.php` — pipeline-detection logic is the right idea; needs to be a function returning a workflow type, not a script with hardcoded item lists
- `bhl2doc.php` — doc-building logic is valuable but buried under title-specific hacks
- All `doc2*.php` / `toc2*.php` scripts — prompting logic worth keeping, but as callable functions not CLI scripts that read/write shared files

### First steps for the new repo
1. Design the per-journal config schema (YAML/JSON/SQLite table)
2. SQLite schema for BHL cache, chat cache, job queue
3. Clean `bhl.php` and `openai.php` as a library layer
4. Single `extract()` function replacing the duplicated AI call pattern
5. Pipeline engine that consumes journal config as data
6. Docker setup once the above is stable
