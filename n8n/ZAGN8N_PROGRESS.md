# zagn8n progress

This file tracks the ZagChain + n8n marketing automation work so it is easy to resume later.

## Stage 1 completed

- Laravel webhook sender for:
  - `user.registered`
  - `kyc.submitted`
  - `payout.requested`
- n8n import files:
  - `zagchain-events-workflow.json`
  - `zagchain-user-registered-workflow.json`
- secure `zagn8n` marketing feed endpoint for approved automation use

## Current endpoint

- Route name: `zagn8n.marketing-feed`
- Path: `/zagn8n/marketing-feed`
- Auth:
  - `X-ZagChain-Automation-Token` header
  - or bearer token
  - or `?token=...`

## Required env

```env
ZAGN8N_ENABLED=true
ZAGN8N_TOKEN=your-strong-private-token
```

## Intended use

n8n can poll this endpoint and generate:

- Instagram captions
- TikTok hooks
- short video scripts
- story text
- image headline/subheadline
- CTA variants

## Next strong steps

1. Add AI execution node after the ready-made polling workflow
2. Add AI prompt templates for:
   - investor trust
   - bank vs ZagChain comparison
   - referral growth
   - package highlights
3. Add approval flow before publishing

## Stage 2 completed

- added ready-made polling workflow:
  - `zagn8n-marketing-content-workflow.json`
- workflow now:
  - polls `zagn8n.marketing-feed`
  - sends secure token
  - prepares AI-ready prompt
  - prepares a structured content pack shell
  - sends Telegram preview
  - logs prompt to Google Sheets

## Stage 3 completed

- added AI generation workflow:
  - `zagn8n-marketing-ai-workflow.json`
- workflow now:
  - polls the secure feed
  - builds the AI prompt
  - calls the OpenAI Responses API
  - parses JSON marketing output
  - sends Telegram preview
  - stores the generated content in Google Sheets

## Stage 4 completed

- added focused comparison workflow:
  - `zagn8n-bank-vs-zagchain-ai-workflow.json`
- workflow now:
  - polls the secure feed
  - builds a bank vs ZagChain comparison prompt
  - calculates a simple example amount comparison
  - calls the OpenAI Responses API
  - parses focused comparison content
  - sends Telegram preview
  - stores output in Google Sheets

## Stage 5 completed

- added package spotlight workflow:
  - `zagn8n-package-spotlight-ai-workflow.json`
- workflow now:
  - polls the secure feed
  - selects the strongest package from live data
  - builds a package-specific AI prompt
  - calls the OpenAI Responses API
  - parses focused package marketing content
  - sends Telegram preview
  - stores output in Google Sheets

## Stage 6 completed

- added referral campaign workflow:
  - `zagn8n-referral-campaign-ai-workflow.json`
- workflow now:
  - polls the secure feed
  - builds a referral-growth AI prompt
  - calls the OpenAI Responses API
  - parses focused referral marketing content
  - sends Telegram preview
  - stores output in Google Sheets

## Stage 7 completed

- added approval workflow:
  - `zagn8n-approval-review-workflow.json`
- workflow now:
  - receives generated content by webhook
  - sends Telegram review alert
  - stores content in Google Sheets approval queue
  - tracks approval status for manual review before publishing

## Stage 8 completed

- connected the general AI generator to the approval queue
- `zagn8n-marketing-ai-workflow.json` now:
  - generates content
  - sends Telegram preview
  - logs to Google Sheets
  - forwards the generated content into the approval-review webhook

## Stage 9 completed

- connected focused workflows to the approval queue:
  - `zagn8n-bank-vs-zagchain-ai-workflow.json`
  - `zagn8n-package-spotlight-ai-workflow.json`
  - `zagn8n-referral-campaign-ai-workflow.json`
- all main `zagn8n` AI generators now:
  - generate content
  - send preview
  - log content
  - forward output into the approval-review workflow

## Stage 10 completed

- added approved content publisher workflow:
  - `zagn8n-approved-content-publisher-workflow.json`
- workflow now:
  - reads approved content only
  - builds a publish queue record
  - stores publish-ready rows in a separate queue
  - sends Telegram queue alerts

## Stage 11 completed

- added enhanced approved content publisher workflow:
  - `zagn8n-approved-content-publisher-with-status-update-workflow.json`
- workflow now:
  - reads approved content only
  - builds a publish queue record
  - stores publish-ready rows in a separate queue
  - updates the original approval row to `queued_for_publish`
  - avoids duplicate queueing on later schedule runs

## Stage 12 completed

- added Canva handoff workflow:
  - `zagn8n-canva-handoff-workflow.json`
- workflow now:
  - reads rows marked `queued_for_publish`
  - creates a design-ready handoff queue with approved copy and Canva briefs
  - updates the original approval row to `design_in_progress`
  - sends a Telegram design alert

## Stage 13 completed

- added safer Canva handoff workflow:
  - `zagn8n-canva-handoff-safe-workflow.json`
- workflow now:
  - reads rows marked `queued_for_publish`
  - processes one row per run
  - creates a design-ready handoff queue with approved copy and Canva briefs
  - updates the original approval row to `design_in_progress`
  - reduces alert floods during testing and daily use

## Stage 14 completed

- added manual Canva placeholder workflow:
  - `zagn8n-canva-manual-placeholder-workflow.json`
- workflow now:
  - reads one `queued_for_design` row
  - updates the same design row with placeholder Canva tracking values
  - sets `design_status` to `design_in_progress`
  - sets `final_review_status` to `pending_final_review`
  - sends a Telegram manual design task alert

## Stage 15 completed

- added simplified Telegram-first workflow:
  - `zagn8n-telegram-marketing-workflow.json`
- workflow now:
  - starts from Telegram commands only
  - uses one Google Sheet as the control center
  - generates one daily draft from ZagChain performance data
  - supports `approve` and `edit: ...` replies
  - updates the same row through the whole conversation
  - prepares a simple Canva handoff state after approval
