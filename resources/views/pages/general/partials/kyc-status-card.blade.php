<div class="card {{ ($kycSummary['status'] ?? 'not_submitted') === 'approved' ? 'border-success' : 'border-warning' }}">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <h6 class="mb-0">Legal verification</h6>
          <span class="badge {{ $kycSummary['badge_class'] ?? 'bg-secondary' }}">{{ $kycSummary['label'] ?? 'Not submitted' }}</span>
        </div>
        <p class="text-secondary mb-2">
          Registration stays simple. Upload your proof after sign-up. Your account can still be used normally, but withdrawals stay blocked until the admin team reviews and approves the document.
        </p>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="fw-semibold mb-2">What we need from you</div>
          <div class="small text-secondary">Upload one clear identity or responsibility document, such as a passport, national ID, driver license, or business responsibility proof that matches the account holder or authorized person.</div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <div class="small text-secondary">Step 1</div>
              <div class="fw-semibold small">Upload proof</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <div class="small text-secondary">Step 2</div>
              <div class="fw-semibold small">Admin review</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <div class="small text-secondary">Step 3</div>
              <div class="fw-semibold small">Withdrawals unlocked</div>
            </div>
          </div>
        </div>
        <div class="small text-secondary">
          @if (($kycSummary['status'] ?? 'not_submitted') === 'approved')
            Approved {{ optional($kycSummary['reviewed_at'] ?? null)?->format('M d, Y h:i A') ?? '' }}{{ !empty($kycSummary['reviewer_label']) ? ' by '.$kycSummary['reviewer_label'] : '' }}. Your wallet can now be used for withdrawal requests.
          @elseif (($kycSummary['status'] ?? 'not_submitted') === 'pending')
            Uploaded {{ optional($kycSummary['submitted_at'] ?? null)?->format('M d, Y h:i A') ?? '' }}. Your file is waiting for admin review, and withdrawals stay limited until the review is completed.
          @elseif (($kycSummary['status'] ?? 'not_submitted') === 'rejected')
            Review update: {{ $kycSummary['admin_notes'] ?: 'Please upload a clearer proof and submit again.' }} You can replace the file and send a corrected version any time.
          @else
            You can keep using the account, but your first withdrawal will be blocked until this verification is approved. Open the KYC upload form when you are ready to prepare your first payout.
          @endif
        </div>
      </div>
      <div class="d-flex flex-column align-items-md-end gap-2">
        @if (!empty($kycSummary['proof_name']))
          <div class="small text-secondary">Current proof: {{ $kycSummary['proof_name'] }}</div>
          <a href="{{ route('dashboard.kyc.proof') }}" class="btn btn-outline-secondary btn-sm">
            Open current proof
          </a>
        @endif
        @if (($kycSummary['status'] ?? 'not_submitted') !== 'approved')
          <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#kycUploadModal">
            {{ ($kycSummary['status'] ?? 'not_submitted') === 'pending' ? 'Update proof' : 'Upload KYC proof' }}
          </button>
        @endif
      </div>
    </div>
    @if (!empty($kycSummary['admin_notes']))
      <div class="alert {{ ($kycSummary['status'] ?? 'not_submitted') === 'approved' ? 'alert-success' : 'alert-warning' }} mt-3 mb-0">
        <div class="fw-semibold mb-1">Review note</div>
        <div>{{ $kycSummary['admin_notes'] }}</div>
      </div>
    @endif
  </div>
</div>
