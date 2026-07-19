<div class="modal fade" id="kycUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('dashboard.kyc.submit') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Upload legal verification proof</h5>
            <p class="text-secondary mb-0">This is required before the first withdrawal. Registration stays simple, but payouts stay blocked until this review is approved.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-light border">
            Accepted formats: JPG, JPEG, PNG, or PDF up to 5 MB. Upload a clear document that proves your identity or your legal responsibility for the account.
          </div>
          <div class="border rounded p-3 mb-3">
            <div class="fw-semibold mb-2">What document can you upload?</div>
            <div class="small text-secondary mb-2">Use one document that clearly matches the ZagChain account holder or responsible person.</div>
            <ul class="small text-secondary mb-0 ps-3">
              <li>Passport, national ID, or residency ID.</li>
              <li>Driver license if it clearly shows full name and photo.</li>
              <li>Company or responsibility proof if the account is managed on behalf of a business or another party.</li>
              <li>Any document the admin team specifically asked you to provide in a previous review note.</li>
            </ul>
          </div>
          <div class="border rounded p-3 bg-light mb-3">
            <div class="fw-semibold mb-2">Before you submit</div>
            <div class="small text-secondary mb-2">Make the review easy so your first withdrawal is not delayed.</div>
            <ul class="small text-secondary mb-0 ps-3">
              <li>Make sure the name, date, photo, and document details are readable.</li>
              <li>Upload the front side or full page that shows the main identity details.</li>
              <li>Use a file that matches your ZagChain account identity or the responsible person for that account.</li>
              <li>If the document belongs to a company manager, sponsor, or legal representative, explain that in the notes.</li>
              <li>Do not upload blurry screenshots, cropped corners, or unreadable photos.</li>
            </ul>
          </div>
          <div class="border rounded p-3 bg-light mb-3">
            <div class="fw-semibold mb-2">What happens next?</div>
            <ul class="small text-secondary mb-0 ps-3">
              <li>After you submit, your KYC status changes to pending review.</li>
              <li>The admin team checks the file and may approve it or ask for a clearer document.</li>
              <li>Once approved, the withdrawal button becomes available in your wallet.</li>
            </ul>
          </div>
          @if (!empty($kycSummary['proof_name']))
            <div class="border rounded p-3 mb-3">
              <div class="small text-secondary">Current uploaded proof</div>
              <div class="fw-semibold">{{ $kycSummary['proof_name'] }}</div>
              @if (($kycSummary['status'] ?? 'not_submitted') !== 'approved')
                <div class="small text-secondary mt-1">Uploading a new file will replace the current one and restart review.</div>
              @endif
            </div>
          @endif
          <div class="mb-3">
            <label class="form-label">Proof file</label>
            <input type="file" name="kyc_proof" class="form-control @error('kyc_proof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
            @error('kyc_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-0">
            <label class="form-label">Notes for admin</label>
            <textarea name="kyc_notes" rows="3" class="form-control @error('kyc_notes') is-invalid @enderror" placeholder="Example: this document belongs to the company manager responsible for the account, or this is a replacement for a previously rejected file">{{ old('kyc_notes') }}</textarea>
            @error('kyc_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Submit proof</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($errors->has('kyc_proof') || $errors->has('kyc_notes'))
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modalElement = document.getElementById('kycUploadModal');
      if (!modalElement || !window.bootstrap) {
        return;
      }

      new bootstrap.Modal(modalElement).show();
    });
  </script>
@endif
