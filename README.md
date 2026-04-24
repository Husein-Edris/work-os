# Work OS

A WordPress plugin for managing your freelance career from wp-admin. Research companies, draft proposals, track outcomes, store documents, and generate blog posts — all in one place.

---

## Features

**Today** — Dashboard showing active proposals and recent memory entries with quick-action links.

**CV** — Live CV preview pulled from your ACF profile. Print to PDF straight from the browser.

**Documents** — Store your CV, certificates, and contracts as WordPress media attachments. Copy a shareable link to any file in two clicks.

**Research** — Paste a job description and click Research. Gemini searches the company and returns a brief. Click Analyse and Claude scores your fit against your full profile, GitHub activity, and memory log.

**Proposals** — Log a proposal with company, budget, status, and notes. AI drafts a pitch letter from your research context. Expand any row in the archive to read the full draft and fit analysis.

**Memory** — Freeform notes about what you learned, who you met, what you decided. Claude reads these when analysing fit for new proposals.

**Blog** — Pick a topic and format, optionally anchor it to a memory event. Claude writes a full post ready to edit. Publish as a WordPress draft in one click.

**Settings** — API keys for Claude and Gemini. CV contact details (phone, address, LinkedIn, GitHub).

---

## Requirements

- WordPress 6.3+
- PHP 8.0+
- [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/)
- An About page with ACF experience and skills fields populated
- Claude API key — for proposals, blog generation, and fit analysis
- Gemini API key — for company research

---

## Installation

1. Clone or download this repo into `wp-content/plugins/work-os/`
2. Activate the plugin in wp-admin under **Plugins**
3. Go to **Work OS > Settings** and add your Claude and Gemini API keys
4. Fill in your CV contact details (phone, address, LinkedIn, GitHub)

Database tables are created automatically on activation. Version bumps run `dbDelta` on `admin_init` — no manual migrations needed.

---

## Database

Four custom tables in your WordPress database:

| Table | Contents |
|---|---|
| `{prefix}work_os_proposals` | Proposals with status, notes, draft text, fit analysis |
| `{prefix}work_os_memory` | Memory events with kind, tags, and note text |
| `{prefix}work_os_research_log` | Raw research and analysis output per company |
| `{prefix}work_os_documents` | Document titles, categories, and media attachment IDs |

---

## API usage

The plugin calls two external APIs when you manually trigger an action:

| Action | API used |
|---|---|
| Research a company | Gemini 2.5 Flash with Google Search grounding |
| Analyse fit | Claude Sonnet (reads your full profile + GitHub + memory) |
| Generate blog post | Claude Sonnet |
| Draft proposal letter | Claude Sonnet |

No data is sent automatically. All calls are triggered by you clicking a button.

---

## Profile data

The CV, fit analysis, and blog generation all pull from a single source: the ACF fields on your About page. Fields used:

- Name, headline, location, summary
- Experience items (company, role, period, description, tech stack)
- Tech skills (from `tech` custom post type)
- Education and languages
- Portfolio projects

Keep your About page up to date and every feature in Work OS reflects it automatically.

---

## Development

No build tools. No Node. No Composer. Pure PHP 8.0+.

```
work-os/
├── admin/              # Admin page templates
├── includes/
│   ├── api/            # REST API handlers (one class per resource)
│   ├── class-admin.php # Menu registration, asset enqueueing
│   └── class-db.php    # Table definitions via dbDelta
└── work-os.php         # Plugin bootstrap
```

REST API base: `wp-json/work-os/v1/`

All endpoints require `manage_options` capability.

---

## License

GPL-2.0-or-later
