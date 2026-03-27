# Referral Registration Reward System

This document explains the current first-pass rules for the `referral registration reward`.

## Core Rule

The registration reward can appear in the wallet, but it does not become fully collectable immediately.

The reward only becomes available when all of these conditions are respected:

1. The reward owner must have an active `Basic 100` investment.
2. The reward owner's referral tree is checked only through the first `3 levels`.
3. The maximum collectable registration reward is capped at `50%` of the active investment volume inside that 3-level tree.

## Important Meaning

- `Visible reward`:
  The reward exists in the wallet record and can be seen by the user.

- `Available reward`:
  The reward is unlocked and can be used in payout calculations.

- `Pending reward`:
  The reward is visible but still locked.

## Formula

`Maximum unlocked registration reward = 50% x active investment volume in levels 1 to 3`

## Example 1

- User has `1` registration reward
- Reward amount = `$25`
- User does **not** have `Basic 100`
- Tree investment volume = `$500`

Result:

- Visible reward = `$25`
- Available reward = `$0`
- Pending reward = `$25`

Reason:

The user is not yet under `Basic 100`.

## Example 2

- User has `Basic 100`
- User has `1` registration reward
- Reward amount = `$25`
- Tree investment volume = `$100`
- Unlock cap = `50% x 100 = $50`

Result:

- Visible reward = `$25`
- Available reward = `$25`
- Pending reward = `$0`

Reason:

The reward is below the `$50` unlock cap.

## Example 3

- User has `Basic 100`
- User has `3` registration rewards
- Reward amount per registration = `$25`
- Total visible registration reward = `$75`
- Tree investment volume = `$100`
- Unlock cap = `50% x 100 = $50`

Result:

- Visible reward = `$75`
- Available reward = `$50`
- Pending reward = `$25`

Reason:

Only the first `$50` is unlocked. The remaining `$25` stays visible but locked.

## Example 4

- User has `Basic 100`
- User has `100` registration rewards
- Reward amount per registration = `$1`
- Total visible registration reward = `$100`
- Tree investment volume = `$120`
- Unlock cap = `50% x 120 = $60`

Result:

- Visible reward = `$100`
- Available reward = `$60`
- Pending reward = `$40`

Reason:

The user can see the full `$100`, but only `$60` is collectable until the tree grows more.

## Example 5

- User has `Basic 100`
- Total visible registration reward = `$100`
- Tree investment volume later grows to `$200`
- Unlock cap = `50% x 200 = $100`

Result:

- Visible reward = `$100`
- Available reward = `$100`
- Pending reward = `$0`

Reason:

The tree now supports unlocking the full visible reward.

## Scope of Tree Investment

Only active investment amounts from the first `3 referral levels` are counted.

That means:

- direct referrals count
- level 2 referrals count
- level 3 referrals count
- deeper than level 3 does not count in this first pass

## What Does Not Count

These are not withdrawable through the registration reward unlock:

- package capital itself
- asset value
- share value
- inactive investments
- deeper-than-level-3 investment volume

## Current Implementation Summary

- registration rewards are created as `pending`
- they become `available` only when the rules allow it
- each new investment triggers a fresh sync for the investor and the first 3 sponsor levels above them

