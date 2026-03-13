# Project Notes

## Product Direction
- The application is now structured as a cryptocurrency cloud-mining platform centered around miners, share-based packages, referrals, wallet earnings, payout operations, and admin review flows.
- Users begin on a `Free Starter` path, can unlock `Basic 100` through referral activity, and can later invest in paid mining packages.
- The platform supports user-facing investment activity and admin-facing operations, analytics, rewards, package control, miner control, and notification management.

## Core Platform State
- Laravel app with NobleUI integrated.
- Multi-miner support is active.
- Seed/default platform currently includes at least:
  - `Alpha One`
  - `Beta Flux`
- Dashboard is miner-aware and can switch by `?miner=slug`.
- Dashboard now shows:
  - miner performance
  - free starter progress
  - package path / current level
  - investment summary
  - referral progress
  - links into wallet, network, investments, and operations

## User Lifecycle
- Registration uses email verification.
- If the email was previously invited, verification messaging is invitation-aware.
- New users automatically start in `Starter Free` / `Free Starter`.
- Starter upgrade logic is active:
  - user needs 20 verified invites
  - plus 1 direct referred user subscribed to `Basic 100`
  - then the account is upgraded automatically to `Basic 100`
- Sponsor/downline linking is active after invited users verify/register.

## Referral And Network System
- Friend invitations are stored in `friend_invitations`.
- Invitation statuses now support progression such as:
  - Pending
  - Verified
  - Registered friend
- Resend invitation email is available for pending invites.
- Referral tree / sponsor structure is active through user relationships.
- User-facing network page shows:
  - direct team
  - second level
  - branch summaries
  - referral pipeline
  - team events
  - referral rewards
- Admin-facing network page shows broader network reporting.
- MLM-style team reward levels are active through configurable depth-based logic.
- Reward display now distinguishes level-based team bonuses in UI and analytics.

## Packages, Miners, And Investments
- Packages are database-driven and admin-managed.
- Admin can:
  - create packages
  - update packages
  - archive packages
  - delete unused packages
- Miner catalog is admin-managed.
- Admin can:
  - create miners from UI
  - edit miner settings
  - manage miner performance logs
- Shareholder/investment logic is active.
- Users are upgraded to shareholder state through approved investment flow.
- `My Investments` page is active and shows owned packages, shares, returns, and history.

## Wallet, Earnings, And Payouts
- Earnings generation is active.
- Wallet page shows:
  - available balance
  - pending balance
  - paid out
  - earnings history
- Payout requests are active.
- Admin can:
  - approve payouts
  - mark payouts as paid
  - store transaction reference and notes
- Payout methods are admin-managed through platform settings.
- Payout method rules are active:
  - minimum amount
  - fixed fee
  - percentage fee
  - instructions
  - processing time
- Wallet reflects gross / fee / net payout values.

## Notifications System
- In-app notifications are active using database notifications.
- Header bell shows live unread count and latest notifications.
- Notifications cover:
  - payouts
  - rewards
  - investments
  - network events
  - milestones
  - digests
- User notification preferences are active.
- Admin notification defaults are active.
- Admin notification templates are active.
- Notification preview/testing is active from admin.
- Notification cleanup tools are active.

## Digest System
- User digest preferences support:
  - in-app
  - email
  - daily / weekly frequency
- Manual digest generation is available.
- Scheduled digest command is built.
- Digest tracking fields exist on users.
- Admin digest monitoring, manual send, bulk send, history, filtering, export, and segment analytics are active.
- Production scheduler setup is still intentionally deferred until deployment stage.

## Admin Area
- Admin-only access is enforced for operational/admin pages.
- Admin pages available include:
  - Analytics
  - Digests
  - Network Admin
  - Shareholders
  - Users
  - Operations
  - Rewards
  - Settings
  - Notification Rules
  - Notification Templates
  - Packages
  - Miners
  - Miner
- Admin analytics includes:
  - business summary metrics
  - miner-by-miner breakdown
  - MLM payout distribution
  - CSV export

## Investment Payment Review Flow
- Package purchase no longer activates instantly.
- Current flow is:
  1. user submits package payment details
  2. system creates pending `investment_orders` record
  3. user uploads proof after payment in a separate follow-up step
  4. admin reviews in Operations
  5. admin approves or rejects
  6. only approved orders create the real shareholder investment
- Payment proof preview works through a secure authenticated route.
- Users receive in-app and email notifications for investment review events.
- Admin safeguards are active:
  - standard approval requires proof
  - approve-without-proof requires override note
  - rejection requires admin note
- Operations page now supports:
  - investment order search
  - investment order status filters
  - CSV export for investment orders
  - proof preview modal
  - proof status badges
  - bulk approve for pending orders with proof
  - bulk reject with shared admin note
- Users now have an `Investment Orders` page to:
  - track submitted orders
  - filter by status
  - open proof
  - cancel pending orders before review

## Important Current Routes
- `dashboard`
- `dashboard.profile`
- `dashboard.notifications`
- `dashboard.notification-preferences`
- `dashboard.investment-orders`
- `dashboard.investments`
- `dashboard.network`
- `dashboard.wallet`
- `dashboard.friends`
- `general.sell-products`
- `general.sell-products.subscribe`
- `general.sell-products.proof`
- `investment-orders.proof-file`
- `dashboard.operations`
- `dashboard.operations.investment-orders.export`
- `dashboard.operations.investment-orders.bulk`

## Important Models / Tables
- `users`
- `friend_invitations`
- `miners`
- `miner_performance_logs`
- `investment_packages`
- `shareholders`
- `user_investments`
- `earnings`
- `payout_requests`
- `investment_orders`
- `referral_events`
- `platform_settings`
- Laravel `notifications`

## Recently Added / Updated For Investment Operations
- `app/Models/InvestmentOrder.php`
- `routes/web.php`
- `resources/views/pages/general/sell-products.blade.php`
- `resources/views/pages/general/operations.blade.php`
- `resources/views/pages/general/investment-orders.blade.php`
- `resources/views/layout/partials/sidebar.blade.php`
- `resources/views/pages/general/profile.blade.php`
- `database/migrations/2026_03_13_093000_add_cancelled_at_to_investment_orders_table.php`
- `tests/Feature/InvestmentOrderManagementTest.php`
- `tests/Feature/ShareholderSubscriptionTest.php`

## Recent Verification
The following targeted checks were run successfully after the latest investment-order work:
- `php artisan migrate`
- `php artisan test --filter=InvestmentOrderManagementTest`
- `php artisan test --filter=ShareholderSubscriptionTest`

## Deferred / Remember For Later
- Production scheduler / cron / Task Scheduler setup for automatic digests.
- Final deployment-time server scheduling configuration.
- Further payment-method-specific review rules if needed.
- Possible next operations improvements:
  - method-specific payment instructions on sell page
  - stricter payment review rules by method
  - admin bulk review refinements
  - payout or investment reconciliation tools
