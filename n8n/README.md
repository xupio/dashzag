# ZagChain n8n import

Import this file into n8n:

- `n8n/zagchain-events-workflow.json`
- `n8n/zagchain-user-registered-workflow.json`
- `n8n/zagn8n-marketing-content-workflow.json`
- `n8n/zagn8n-marketing-ai-workflow.json`
- `n8n/zagn8n-bank-vs-zagchain-ai-workflow.json`
- `n8n/zagn8n-package-spotlight-ai-workflow.json`
- `n8n/zagn8n-referral-campaign-ai-workflow.json`
- `n8n/zagn8n-approval-review-workflow.json`
- `n8n/zagn8n-approved-content-publisher-workflow.json`
- `n8n/zagn8n-approved-content-publisher-with-status-update-workflow.json`
- `n8n/zagn8n-canva-handoff-workflow.json`
- `n8n/zagn8n-canva-handoff-safe-workflow.json`
- `n8n/zagn8n-canva-manual-placeholder-workflow.json`
- `n8n/zagn8n-telegram-marketing-workflow.json`

What it does:

- receives ZagChain webhook events on one endpoint
- checks the `X-ZagChain-Webhook-Secret` header
- routes these events:
  - `user.registered`
  - `kyc.submitted`
  - `payout.requested`
- returns a clean JSON response

The second file is more practical for immediate use:

- `n8n/zagchain-user-registered-workflow.json`

It includes prepared nodes for:

- admin email
- Telegram alert
- Google Sheets logging

You only need to replace the placeholder secret, admin email, Telegram chat ID, and Google Sheet ID.

The `zagn8n` marketing file is for content generation:

- `n8n/zagn8n-marketing-content-workflow.json`

It:

- polls `/zagn8n/marketing-feed`
- sends the private token
- builds an AI-ready marketing prompt
- prepares a content-pack shell
- sends a Telegram preview
- logs the prompt to Google Sheets

The next `zagn8n` file adds real AI generation:

- `n8n/zagn8n-marketing-ai-workflow.json`

It:

- polls the secure `zagn8n` feed
- builds the AI prompt
- calls the OpenAI Responses API
- asks for JSON output
- parses ready Instagram / TikTok content
- sends a Telegram preview
- logs the content to Google Sheets
- can forward generated content into the approval-review webhook

To enable the approval handoff:

1. Import `zagn8n-approval-review-workflow.json`
2. Copy its webhook URL
3. Open `zagn8n-marketing-ai-workflow.json`
4. Replace:
   - `https://your-n8n-domain/webhook/zagn8n-approval-review`
5. Then new generated content will be sent into the approval queue automatically

The same approval handoff pattern is now prepared in:

- `zagn8n-bank-vs-zagchain-ai-workflow.json`
- `zagn8n-package-spotlight-ai-workflow.json`
- `zagn8n-referral-campaign-ai-workflow.json`

The next focused theme file is:

- `n8n/zagn8n-bank-vs-zagchain-ai-workflow.json`

It is optimized for one high-conversion angle:

- bank savings vs ZagChain growth visibility

It calculates a simple example comparison and then asks AI to create:

- Instagram caption
- TikTok hook
- short scripts
- story text
- image copy
- CTA
- Canva briefs

Another focused theme file is:

- `n8n/zagn8n-package-spotlight-ai-workflow.json`

It is optimized for:

- promoting the strongest package from the live feed

It turns the current top package into:

- Instagram caption
- TikTok hook
- short scripts
- story text
- image copy
- CTA
- Canva briefs

Another focused theme file is:

- `n8n/zagn8n-referral-campaign-ai-workflow.json`

It is optimized for:

- referral growth
- warm-contact invitations
- trust-based sharing
- onboarding momentum

Approval flow file:

- `n8n/zagn8n-approval-review-workflow.json`

It is used to:

- receive generated content for review
- send Telegram review alerts
- store content in a Google Sheets approval queue
- track `pending_review`, `approved`, `needs_edit`, or `rejected`

Approved publishing queue file:

- `n8n/zagn8n-approved-content-publisher-workflow.json`

It is used to:

- read only approved content from the approval queue
- create publish-ready queue rows
- send Telegram queue alerts
- prepare the handoff into Canva or a social scheduler

Enhanced approved publishing queue file:

- `n8n/zagn8n-approved-content-publisher-with-status-update-workflow.json`

It is used to:

- read only approved content from the approval queue
- create publish-ready queue rows
- update the original approval row to `queued_for_publish`
- prevent duplicate queueing on the next schedule run

Canva handoff queue file:

- `n8n/zagn8n-canva-handoff-workflow.json`

It is used to:

- read rows that are ready for design work
- create design-ready queue rows with image/video briefs
- update the original approval row to `design_in_progress`
- notify you when a Canva handoff task is ready

Safer Canva handoff queue file:

- `n8n/zagn8n-canva-handoff-safe-workflow.json`

It is used to:

- process one `queued_for_publish` row per run
- reduce alert flooding while testing or in production
- move content into the design queue safely
- update the original approval row to `design_in_progress`

Manual Canva placeholder file:

- `n8n/zagn8n-canva-manual-placeholder-workflow.json`

It is used to:

- read one `queued_for_design` row
- fill placeholder Canva tracking values in the design queue
- move the row to `design_in_progress`
- notify you that a manual Canva task is ready

Simplified single-workflow file:

- `n8n/zagn8n-telegram-marketing-workflow.json`

It is used to:

- start from Telegram commands only
- keep one Google Sheet as the control center
- create one daily draft from ZagChain performance data
- let you reply `approve` or `edit: ...`
- update the same control row through the whole conversation
- prepare a simple Canva handoff state after approval
- act as the clean base for a later direct Canva auto-create step after approval

To use the Telegram-first workflow fully, replace:

- `replace-with-your-zagn8n-token`
- `replace-with-your-openai-api-key`
- `replace-with-your-google-sheet-id`
- `replace-with-your-canva-access-token`

After import:

1. Open the `Verify And Route` node.
2. Replace `replace-with-your-n8n-secret` with your real secret.
3. Copy the webhook URL from n8n.
4. Set ZagChain `.env`:

```env
N8N_WEBHOOK_ENABLED=true
N8N_WEBHOOK_URL=https://your-n8n-domain/webhook/zagchain
N8N_WEBHOOK_SECRET=your-real-secret
```

Recommended next n8n nodes after `Verify And Route`:

- `Switch` on `route`
- Gmail / SMTP
- Telegram / Slack
- Google Sheets / Airtable / Notion / HubSpot
