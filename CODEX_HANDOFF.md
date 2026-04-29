# Codex Handoff

## Project
- ZagChain Laravel app
- Main working area: `n8n/`
- Main repo path: `C:\xampp\htdocs\zag2 - Copy\example-app`

## What Is Working
- Telegram-first marketing workflow exists in:
  - `n8n/zagn8n-telegram-marketing-workflow.json`
- Flow supports:
  - `daily`
  - `approve`
  - `edit: ...`
- The workflow:
  - reads ZagChain marketing feed
  - generates a draft with OpenAI
  - sends the draft to Telegram
  - updates one Google Sheet control row
  - can open Canva after approval

## One-Sheet Control Model
- Sheet name:
  - `zagn8n_marketing_control`
- Core columns:
  - `created_at`
  - `chat_id`
  - `trigger_word`
  - `performance_date`
  - `performance_summary`
  - `draft_text`
  - `approval_status`
  - `edit_request`
  - `version`
  - `canva_status`
  - `canva_design_id`
  - `canva_design_link`
  - `last_action`

## Canva Status
- Canva connection was made to the point where a design can be created and opened.
- The permanent Canva edit link pattern is:
  - `https://www.canva.com/design/{design_id}/edit`
- The current Canva integration work is functional but was noisy because of expiring access tokens.
- Future stable version should use:
  - a valid refresh token flow
  - or Canva Autofill with a Brand Template for true automatic final design layout

## Important Files
- `n8n/zagn8n-telegram-marketing-workflow.json`
- `n8n/README.md`
- `n8n/ZAGN8N_PROGRESS.md`
- `n8n/ZAGN8N_OPERATING_GUIDE.md`
- `n8n/zagn8n-operating-guide-print.html`
- `zagn8n-block-diagram.html`

## Secondary Market Work Added
- Added migration/model foundation for miners, holdings, listings, sales, transactions, and status history.

### Migrations
- `database/migrations/2026_04_23_120000_add_secondary_market_fields_to_miners_table.php`
- `database/migrations/2026_04_23_120100_create_share_holdings_table.php`
- `database/migrations/2026_04_23_120200_create_share_listings_table.php`
- `database/migrations/2026_04_23_120300_create_share_sales_table.php`
- `database/migrations/2026_04_23_120400_create_share_market_transactions_table.php`
- `database/migrations/2026_04_23_120500_create_miner_status_histories_table.php`

### Models
- `app/Models/ShareHolding.php`
- `app/Models/ShareListing.php`
- `app/Models/ShareSale.php`
- `app/Models/ShareMarketTransaction.php`
- `app/Models/MinerStatusHistory.php`

## Not Yet Done
- `php artisan migrate` has not been run for the new secondary-market tables in this session.
- Secondary-market service logic is not built yet.
- Automatic Canva refresh-token flow is not finalized.
- Canva Autofill brand-template flow is not built yet.

## Best Next Steps
1. Run migrations for the new secondary-market tables.
2. Build the resale transaction service:
   - create listing
   - lock shares
   - complete sale
   - move holdings
   - record platform fee
3. Stabilize Canva auth with refresh token flow.
4. If full automatic design is needed later, switch Canva from blank design creation to Brand Template + Autofill.

## If Returning On Another Computer
1. Clone or open this repo.
2. Open:
   - `CODEX_HANDOFF.md`
   - `n8n/ZAGN8N_PROGRESS.md`
   - `n8n/ZAGN8N_OPERATING_GUIDE.md`
3. Start from the next-step list above.
