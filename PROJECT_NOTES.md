# Project Notes

## Current State
- Laravel app with NobleUI integrated.
- Dashboard includes live customer, BTC price, and BTC difficulty cards.
- Email verification is active for new user registration.
- Mailtrap SMTP is configured through `.env`.

## Authentication And Invitations
- New users register and are redirected to the email verification notice.
- If the registering email was previously invited, the verification email mentions that invitation and names the inviter when available.
- After the invited user confirms their account email, all matching friend invitations are marked as `Registered friend`.

## Friends Feature
- Dashboard left menu now includes:
  - Overview
  - Profile
  - Friends
- Friends page supports inviting a friend through a modal form.
- Friend invitation fields:
  - name (required)
  - email (required)
  - phone (optional)
  - country (optional)
- Invitations are saved in the `friend_invitations` table.
- Invitation email is sent with a signed confirmation link.
- Friends table statuses:
  - Pending
  - Verified
  - Registered friend

## Main Files Added Or Updated
- `routes/web.php`
- `app/Models/User.php`
- `app/Models/FriendInvitation.php`
- `app/Mail/FriendInvitationMail.php`
- `app/Notifications/InvitationAwareVerifyEmail.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `resources/views/pages/general/profile.blade.php`
- `resources/views/pages/general/friends.blade.php`
- `resources/views/emails/friend-invitation.blade.php`
- `resources/views/friend-invitations/verified.blade.php`
- `database/migrations/2026_03_10_130000_create_friend_invitations_table.php`
- `database/migrations/2026_03_10_140000_add_registered_at_to_friend_invitations_table.php`

## Database
- Migration for `friend_invitations` table has been run.
- Migration for `registered_at` on `friend_invitations` has been run.

## Next Good Steps
- Test full flow in browser and Mailtrap:
  - invite friend
  - receive invite email
  - confirm invitation
  - register with invited email
  - verify account email
  - confirm status changes to `Registered friend`
- Add a more complete friends management table and actions.
- Add automated feature tests for invitation email and confirmation flow.
