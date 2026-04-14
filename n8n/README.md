# ZagChain n8n import

Import this file into n8n:

- `n8n/zagchain-events-workflow.json`
- `n8n/zagchain-user-registered-workflow.json`

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
