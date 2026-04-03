<div class="card {{ ($kycSummary['status'] ?? 'not_submitted') === 'approved' ? 'border-success' : 'border-warning' }}">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <h6 class="mb-0">Legal verification</h6>
          <span class="badge {{ $kycSummary['badge_class'] ?? 'bg-secondary' }}">{{ $kycSummary['label'] ?? 'Not submitted' }}</span>
        </div>
        <p class="text-secondary mb-2">
          Registration stays simple. Upload your proof after sign-up, and withdrawals will stay blocked until the admin team reviews and approves it.
        </p>
        <div class="small text-secondary">
          @if (($kycSummary['status'] ?? 'not_submitted') === 'approved')
            Approved {{ optional($kycSummary['reviewed_at'] ?? null)?->format('M d, Y h:i A') ?? '' }}{{ !empty($kycSummary['reviewer_label']) ? ' by '.$kycSummary['reviewer_label'] : '' }}.
          @elseif (($kycSummary['status'] ?? 'not_submitted') === 'pending')
            Uploaded {{ optional($kycSummary['submitted_at'] ?? null)?->format('M d, Y h:i A') ?? '' }}. Your account stays limited for withdrawals until review is completed.
          @elseif (($kycSummary['status'] ?? 'not_submitted') === 'rejected')
            Review update: {{ $kycSummary['admin_notes'] ?: 'Please upload a clearer proof and submit again.' }}
          @else
            You can keep using the account, but your first withdrawal will be blocked until this verification is approved.
          @endif
        </div>
      </div>
      <div class="d-flex flex-column align-items-md-end gap-2">
        @if (!empty($kycSummary['proof_name']))
          <div class="small text-secondary">Current proof: {{ $kycSummary['proof_name'] }}</div>
        @endif
        @if (($kycSummary['status'] ?? 'not_submitted') !== 'approved')
          <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#kycUploadModal">
            {{ ($kycSummary['status'] ?? 'not_submitted') === 'pending' ? 'Update proof' : 'Upload KYC proof' }}
          </button>
        @endif
      </div>
    </div>
  </div>
</div>
