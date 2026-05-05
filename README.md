# Work OS

A WordPress plugin for managing your freelance career from wp-admin. Research companies, draft proposals, track outcomes, store documents, generate blog posts, and sync your GitHub portfolio — all in one place.

---

## Features

**Today** — Dashboard showing active proposals and recent memory entries with quick-action links.

**CV** — Live CV preview pulled from your ACF profile. Print to PDF straight from the browser.

**Documents** — Store your CV, certificates, and contracts as WordPress media attachments. Copy a shareable link to any file in two clicks.

**Research** — Paste a job description and click Research. Gemini searches the company and returns a brief. Click Analyse and Claude scores your fit against your full profile, GitHub activity, and memory log.

**Proposals** — Log a proposal with company, budget, status, and notes. AI drafts a pitch letter from your research context. Expand any row in the archive to read the full draft and fit analysis.

**Memory** — Freeform notes about what you learned, who you met, what you decided. Claude reads these when analysing fit for new proposals.

**Blog** — Pick a topic and format, optionally anchor it to a memory event. Claude writes a full post ready to edit. Publish as a WordPress draft in one click.

**GitHub Sync** — Compares your public GitHub repos against your portfolio projects CPT. Repos with a substantial README that are missing from your portfolio get a "Generate draft" button — Claude reads the actual README and repo metadata, then creates a project CPT draft with all ACF fields populated (challenge, solution, tech stack, key features, GitHub URL). Drafts are flagged for review before publishing. Repos you want to skip permanently can be blocklisted.

**Portfolio** — Analyse your `project` CPT entries and get AI suggestions for improving descriptions, headlines, and tech stack coverage.

**E/A Bericht** — Einnahmen-Ausgaben-Rechnung generator for Austrian Kleinunternehmer. Pulls paid invoices and expense receipts from FastBill for any date range, converts foreign-currency amounts to EUR via Frankfurter.app, categorises expenses by vendor pattern matching, and renders a formal PDF report (cover page + category summary) matching the Austrian accountant format. Amounts are editable inline, manual entries can be added (depreciation, rent, etc.), and the report survives page reloads via localStorage.

**Settings** — API keys (Claude, Gemini) and GitHub token. CV contact details (phone, address, LinkedIn, GitHub). FastBill credentials (email + API key). Custom AI prompt rules for proposals and blog generation.

---

## Requirements

- WordPress 6.3+
- PHP 8.0+
- [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/)
- An About page with ACF experience and skills fields populated
- Claude API key — for proposals, blog generation, fit analysis, and GitHub project generation
- Gemini API key — for company research
- GitHub personal access token — optional, raises API rate limit from 60 to 5,000 requests/hour

---

## Installation

1. Clone or download this repo into `wp-content/plugins/work-os/`
2. Activate the plugin in wp-admin under **Plugins**
3. Go to **Work OS > Settings** and add your Claude and Gemini API keys
4. Fill in your CV contact details including your GitHub profile URL
5. Optionally add a GitHub fine-grained personal access token (public repositories, read-only) to avoid rate limit errors on GitHub Sync

Database tables are created automatically on activation. Version bumps run `dbDelta` on `admin_init` — no manual migrations needed.

**No secrets in code.** All API keys and tokens are stored in the WordPress database via the Settings page. Nothing sensitive is committed to this repository.

---

## Database

Four custom tables in your WordPress database:

| Table | Contents |
|---|---|
| `{prefix}work_os_proposals` | Proposals with status, notes, draft text, fit analysis |
| `{prefix}work_os_memory` | Memory events with kind, tags, and note text |
| `{prefix}work_os_research_log` | Raw research and analysis output per company |
| `{prefix}work_os_documents` | Document titles, categories, and media attachment IDs |
| `{prefix}work_os_ea_vendor_mappings` | Vendor pattern-to-category rules for E/A Bericht expense categorisation |

WordPress options used:

| Option | Contents |
|---|---|
| `work_os_github_token` | GitHub personal access token |
| `work_os_repo_blocklist` | Serialised array of repo names permanently skipped in GitHub Sync |
| `work_os_fastbill_email` | FastBill account email for E/A Bericht |
| `work_os_fastbill_api_key` | FastBill API key for E/A Bericht |

---

## API usage

All external API calls are triggered manually by clicking a button — nothing runs automatically.

| Action | API |
|---|---|
| Research a company | Gemini 2.5 Flash with Google Search grounding |
| Analyse fit | Claude Sonnet |
| Generate blog post | Claude Sonnet |
| Draft proposal letter | Claude Sonnet |
| Generate project draft from GitHub repo | Claude Sonnet |
| Fetch repo list + READMEs | GitHub REST API v3 |
| Fetch invoices and expenses for E/A Bericht | FastBill REST API |
| Convert foreign-currency invoice amounts to EUR | Frankfurter.app (cached 7 days via `set_transient`) |

---

## Profile data

The CV, fit analysis, blog generation, and project generation all pull from a single source: the ACF fields on your About page. Fields used:

- Name, headline, location, summary
- Experience items (company, role, period, description, tech stack)
- Tech skills (from `tech` custom post type)
- Education and languages
- Portfolio projects (from `project` custom post type)

Keep your About page up to date and every feature in Work OS reflects it automatically.

---

## Project CPT fields (GitHub Sync)

When a project draft is generated from a GitHub repo, the following ACF fields are populated:

| Field | ACF group | Source |
|---|---|---|
| Title | post | Generated from repo name and purpose |
| Excerpt | post | One-sentence summary |
| Challenge | `project_content` | Problem the project solves |
| Solution | `project_content` | Architecture and approach |
| Tech Stack | `project_overview` | Technologies matched against existing `tech` CPT entries |
| Key Features | `key_features` (repeater) | 2–4 distinct features from the README |
| GitHub URL | `project_links` | Repo `html_url` |

All drafts are saved with `post_status = draft` and flagged with `_work_os_needs_review`. An admin notice appears on the edit screen until you review and remove the flag.

---

## Development

No build tools. No Node. No Composer. Pure PHP 8.0+.

```
work-os/
├── admin/                  # Admin page templates
│   ├── page-today.php
│   ├── page-cv.php
│   ├── page-documents.php
│   ├── page-research.php
│   ├── page-proposals.php
│   ├── page-memory.php
│   ├── page-blog.php
│   ├── page-github.php
│   ├── page-portfolio.php
│   ├── page-ea.php         # E/A Bericht (FastBill + PDF export)
│   └── page-settings.php
├── includes/
│   ├── api/                # REST API handlers (one class per resource)
│   │   ├── class-settings.php
│   │   ├── class-profile.php
│   │   ├── class-memory.php
│   │   ├── class-research.php
│   │   ├── class-proposals.php
│   │   ├── class-blog.php
│   │   ├── class-documents.php
│   │   ├── class-portfolio-analyzer.php
│   │   ├── class-github-sync.php
│   │   ├── class-ea-report.php  # FastBill fetch, EUR conversion, vendor mapping CRUD
│   │   └── class-router.php
│   ├── class-admin.php     # Menu registration, asset enqueueing
│   └── class-db.php        # Table definitions via dbDelta
└── work-os.php             # Plugin bootstrap
```

REST API base: `wp-json/work-os/v1/`

All endpoints require `manage_options` capability.

---

## License

GPL-2.0-or-later
