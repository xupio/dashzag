@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Friends</h4>
        <p class="text-secondary mb-0">Review invited friends and track whether each one is verified or still pending.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#inviteFriendModal">
          <i data-lucide="user-plus" class="btn-icon-prepend"></i> Invite friend
        </button>
        <a href="{{ route('dashboard.profile') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="arrow-left" class="btn-icon-prepend"></i> Back to profile
        </a>
      </div>
    </div>
  </div>
</div>

@if (session('invite_success'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
        <span>{{ session('invite_success') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  </div>
@endif

@if (session('invite_limit'))
  <div class="row">
    <div class="col-12">
      <div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert">
        <span>{{ session('invite_limit') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  </div>
@endif

<div class="row">
  <div class="col-12 grid-margin">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Referral performance</h5>
            <p class="text-secondary mb-0">See how many invitations are turning into verified and active investors.</p>
          </div>
          <span class="badge bg-primary">Conversion view</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Pending invitations</div>
              <div class="h4 mb-1">{{ $friendReferralMetrics['pending'] }}</div>
              <div class="small text-secondary">Still waiting for email verification or registration.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Verified friends</div>
              <div class="h4 mb-1">{{ $friendReferralMetrics['verified'] }}</div>
              <div class="small text-secondary">{{ $friendReferralMetrics['verification_rate'] }}% of all invitations verified.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Registered users</div>
              <div class="h4 mb-1">{{ $friendReferralMetrics['registered'] }}</div>
              <div class="small text-secondary">{{ $friendReferralMetrics['registration_rate'] }}% reached account creation.</div>
            </div>
          </div>
          <div class="col-md-6 col-xl-3">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Active investors</div>
              <div class="h4 mb-1">{{ $friendReferralMetrics['active_investors'] }}</div>
              <div class="small text-secondary">{{ $friendReferralMetrics['investor_rate'] }}% became active investors.</div>
            </div>
          </div>
        </div>

        <div class="alert alert-info mt-3 mb-0">
          <strong>Total invitations:</strong> {{ $friendReferralMetrics['total'] }}.
          The stronger this conversion chain gets, the easier word-of-mouth growth becomes.
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Next referral target</h5>
            <p class="text-secondary mb-0">A visible milestone helps users keep sharing with purpose.</p>
          </div>
          <span class="badge bg-dark">Momentum</span>
        </div>

        <div class="row g-3 align-items-center">
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Active investors right now</div>
              <div class="h3 mb-1">{{ $friendReferralMilestone['current'] }}</div>
              <div class="small text-secondary">Current invited friends who became active investors.</div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Next target</div>
              <div class="h3 mb-1">{{ $friendReferralMilestone['next_target'] }}</div>
              <div class="small text-secondary">{{ $friendReferralMilestone['remaining'] }} more active investor{{ $friendReferralMilestone['remaining'] === 1 ? '' : 's' }} to reach the next milestone.</div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="small text-secondary mb-2">Progress</div>
              <div class="progress mb-2" style="height: 10px;">
                <div
                  class="progress-bar bg-success"
                  role="progressbar"
                  style="width: {{ $friendReferralMilestone['progress'] }}%;"
                  aria-valuenow="{{ $friendReferralMilestone['progress'] }}"
                  aria-valuemin="0"
                  aria-valuemax="100"
                ></div>
              </div>
              <div class="small text-secondary">{{ $friendReferralMilestone['progress'] }}% of the current milestone reached.</div>
            </div>
          </div>
        </div>

        <div class="alert alert-success mt-3 mb-0">
          <strong>Focus:</strong> help one more verified or registered friend become an active investor to keep this moving.
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Best message tips</h5>
            <p class="text-secondary mb-0">A simple sharing flow usually converts better than sending the registration link too early.</p>
          </div>
          <span class="badge bg-success">Guide users clearly</span>
        </div>

        <div class="row g-3">
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">1. Invite warm contacts first</div>
              <div class="small text-secondary">Start with friends, relatives, or trusted contacts who already know you. Word-of-mouth works best when trust is already there.</div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">2. Send “How it works” before register</div>
              <div class="small text-secondary">Let them understand the concept first, then send the registration link only after they show real interest.</div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">3. Follow up after verification</div>
              <div class="small text-secondary">When a friend verifies or registers, send a short follow-up message and help them choose their next step inside ZagChain.</div>
            </div>
          </div>
        </div>

        <div class="alert alert-warning mt-3 mb-0">
          <strong>Simple rule:</strong> explain first, register second, follow up third.
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 grid-margin">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <div>
            <h5 class="mb-1">Share & invite</h5>
            <p class="text-secondary mb-0">Help friends understand ZagChain quickly with ready-made sharing tools.</p>
          </div>
          <span class="badge bg-warning text-dark">Word-of-mouth kit</span>
        </div>

        <div class="row g-3">
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">Best page to share</div>
              <div class="small text-secondary mb-2">Send new users to the public explanation page first.</div>
              <div class="small fw-semibold text-break mb-3">{{ $displayHowItWorksUrl }}</div>
              <button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $publicHowItWorksUrl }}">
                Copy how it works link
              </button>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">Registration link</div>
              <div class="small text-secondary mb-2">Share this when someone is ready to join.</div>
              <div class="small fw-semibold text-break mb-3">{{ $displayRegisterUrl }}</div>
              <button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $publicRegisterUrl }}">
                Copy register link
              </button>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="fw-semibold mb-2">Short invite message</div>
              <div class="small text-secondary mb-3">{{ $shareShortMessage }}</div>
              <button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $shareShortMessage }}">
                Copy short message
              </button>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-lg-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="fw-semibold">WhatsApp message</div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-success btn-sm" data-copy-text="{{ $shareWhatsappText }}">
                    Copy
                  </button>
                  <a href="https://wa.me/?text={{ urlencode($shareWhatsappText) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    Open WhatsApp
                  </a>
                </div>
              </div>
              <div class="small text-secondary" style="white-space: pre-line;">{{ $shareWhatsappText }}</div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="fw-semibold">Telegram message</div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-info btn-sm" data-copy-text="{{ $shareTelegramText }}">
                    Copy
                  </button>
                  <a href="https://t.me/share/url?url={{ urlencode($publicHowItWorksUrl) }}&text={{ urlencode($shareTelegramText) }}" target="_blank" rel="noopener" class="btn btn-info btn-sm text-white">
                    Open Telegram
                  </a>
                </div>
              </div>
              <div class="small text-secondary" style="white-space: pre-line;">{{ $shareTelegramText }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Friend invitations</h5>
            <p class="text-secondary mb-0">Saved invitations for {{ $user->name }}.</p>
            <p class="text-secondary small mb-0">Email limit: {{ $friendInvitationEmailsRemaining }} of {{ $friendInvitationDailyEmailLimit }} invitation emails remaining today.</p>
          </div>
          <span class="badge bg-primary">{{ $friendInvitations->count() }} total</span>
        </div>

        @if ($friendInvitations->isEmpty())
          <div class="text-center py-5">
            <div class="wd-60 ht-60 rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3">
              <i data-lucide="users" class="text-secondary"></i>
            </div>
            <h5 class="mb-2">No invited friends yet</h5>
            <p class="text-secondary mb-0">Use the invite button above to add the first friend invitation.</p>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Telephone</th>
                  <th>Country</th>
                  <th>Status</th>
                  <th>Invited</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($friendInvitations as $friendInvitation)
                  @php
                    $followUpVerificationMessage = 'Hi '.$friendInvitation->name.', thanks for verifying your email with ZagChain. When you are ready, here is the registration link: '.$publicRegisterUrl;
                    $followUpRegistrationMessage = 'Hi '.$friendInvitation->name.', great to see you inside ZagChain. Start with the How It Works page: '.$publicHowItWorksUrl.' and then choose your next step from your dashboard.';
                  @endphp
                  <tr>
                    <td>{{ $friendInvitation->name }}</td>
                    <td>{{ $friendInvitation->email }}</td>
                    <td>{{ $friendInvitation->phone ?: '—' }}</td>
                    <td>{{ $friendInvitation->country ?: '—' }}</td>
                    <td>
                      @if ($friendInvitation->registered_at)
                        <span class="badge bg-success">Registered friend</span>
                      @elseif ($friendInvitation->verified_at)
                        <span class="badge bg-info">Verified</span>
                      @else
                        <span class="badge bg-warning text-dark">Pending</span>
                      @endif
                    </td>
                    <td>{{ $friendInvitation->created_at?->format('M d, Y h:i A') }}</td>
                    <td class="text-end">
                      @if ($friendInvitation->registered_at)
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            data-copy-text="{{ $followUpRegistrationMessage }}"
                          >
                            Copy follow-up
                          </button>
                          <span class="text-secondary small align-self-center">Registered</span>
                        </div>
                      @elseif ($friendInvitation->verified_at)
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-info"
                            data-copy-text="{{ $followUpVerificationMessage }}"
                          >
                            Copy follow-up
                          </button>
                          <span class="text-secondary small align-self-center">Verified</span>
                        </div>
                      @else
                        <form method="POST" action="{{ route('dashboard.friends.resend', $friendInvitation) }}" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-secondary">Resend email</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="inviteFriendModal" tabindex="-1" aria-labelledby="inviteFriendModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('dashboard.friends.invite') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="inviteFriendModalLabel">Invite friend</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="friend_name" class="form-label">Friend name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="friend_name" name="name" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="friend_email" class="form-label">Friend email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="friend_email" name="email" value="{{ old('email') }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="friend_phone" class="form-label">Telephone number</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="friend_phone" name="phone" value="{{ old('phone') }}" placeholder="Not required">
            <div class="form-text">Optional field.</div>
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-0">
            <label for="friend_country" class="form-label">Country</label>
            <select class="form-select @error('country') is-invalid @enderror" id="friend_country" name="country" required>
              <option value="" disabled {{ old('country') ? '' : 'selected' }}>Select country</option>
              @foreach ($friendInvitationCountries as $friendInvitationCountry)
                <option value="{{ $friendInvitationCountry }}" @selected(old('country') === $friendInvitationCountry)>{{ $friendInvitationCountry }}</option>
              @endforeach
            </select>
            @error('country')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Send invite</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-copy-text]').forEach(function (button) {
        button.addEventListener('click', async function () {
          var text = button.getAttribute('data-copy-text') || '';
          var originalText = button.textContent.trim();

          try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              await navigator.clipboard.writeText(text);
            } else {
              var helper = document.createElement('textarea');
              helper.value = text;
              helper.setAttribute('readonly', 'readonly');
              helper.style.position = 'absolute';
              helper.style.left = '-9999px';
              document.body.appendChild(helper);
              helper.select();
              document.execCommand('copy');
              document.body.removeChild(helper);
            }

            button.textContent = 'Copied';

            setTimeout(function () {
              button.textContent = originalText;
            }, 1600);
          } catch (error) {
            button.textContent = 'Copy failed';

            setTimeout(function () {
              button.textContent = originalText;
            }, 1600);
          }
        });
      });
    });
  </script>

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var inviteModalElement = document.getElementById('inviteFriendModal');
        if (!inviteModalElement || !window.bootstrap) {
          return;
        }

        var inviteModal = new bootstrap.Modal(inviteModalElement);
        inviteModal.show();
      });
    </script>
  @endif
@endpush



