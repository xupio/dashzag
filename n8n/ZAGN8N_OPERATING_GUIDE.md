# zagn8n operating guide

This guide explains the intended day-to-day flow for the ZagChain marketing automation system.

## Goal

The final system goal is:

1. generate one strong content pack daily
2. send it for review
3. move approved items to the design queue
4. keep final posting manual

This keeps the process fast, organized, and safer than fully automatic publishing.

## Main workflow roles

### `zagn8n-marketing-ai-workflow`

Purpose:

- pulls approved ZagChain marketing data
- creates one content pack
- sends Telegram preview
- logs the result into `zagn8n_ai_content`
- forwards content to approval review

Recommended mode:

- active
- runs once daily

### `zagn8n-approval-review-workflow`

Purpose:

- receives generated content
- sends Telegram review alert
- stores the row in `zagn8n_approval_queue`

Recommended mode:

- active
- listens by webhook

### `zagn8n-approved-content-publisher-with-status-update-workflow`

Purpose:

- reads approved content
- writes a posting-ready row into `zagn8n_publish_queue`
- updates the original approval row to `queued_for_publish`

Recommended mode:

- active on a calm schedule
- or manual while tuning

### `zagn8n-canva-handoff-safe-workflow`

Purpose:

- reads rows marked `queued_for_publish`
- moves one row at a time into `zagn8n_design_queue`
- updates the original approval row to `design_in_progress`
- sends one Telegram design alert

Recommended mode:

- active on a calm schedule
- or manual while tuning

## Main sheets

### `zagn8n_ai_content`

Use for:

- generated content log
- AI output archive

### `zagn8n_approval_queue`

Use for:

- review
- approval
- queue handoff status

### `zagn8n_publish_queue`

Use for:

- posting-ready queue
- manual publish tracking

### `zagn8n_design_queue`

Use for:

- design-ready handoff rows
- Canva brief tracking

## Status flow

Use these statuses consistently.

### Approval queue statuses

- `pending_review`
- `approved`
- `queued_for_publish`
- `design_in_progress`

### Design queue statuses

- `queued_for_design`
- `designed`

### Publish queue statuses

- `queued`
- `posted`

## Daily routine

1. let `zagn8n-marketing-ai-workflow` create one content pack
2. review the Telegram preview and the row in `zagn8n_approval_queue`
3. if the content is good, set `approval_status = approved`
4. the publisher workflow moves it into `zagn8n_publish_queue`
5. the safe Canva handoff workflow moves it into `zagn8n_design_queue`
6. after the design is ready, mark the design row as `designed`
7. after manual posting, mark the publish row as `posted`

## Operational rules

- keep content generation automatic
- keep approval human
- keep final design approval human
- keep final posting manual
- use calmer schedules to avoid floods
- use one-row safe handoff workflows where possible

## Recommended schedules

### `zagn8n-marketing-ai-workflow`

- once daily
- recommended time: 10:00 AM Dubai time

### `zagn8n-approved-content-publisher-with-status-update-workflow`

- every 1 hour
- or manual while tuning

### `zagn8n-canva-handoff-safe-workflow`

- every 1 hour
- or manual while tuning

## Success checklist

The system is healthy when:

- one content pack is generated daily
- one approval row is created correctly
- approved rows move into the publish queue
- queued rows move into the design queue
- original statuses update correctly
- Telegram alerts are useful and not flooding

## Notes

- if Telegram floods, stop the schedule trigger first
- if rows do not update, check the `generated_at` match value
- if a workflow behaves unpredictably, test it manually before reactivating the trigger
