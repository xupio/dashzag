<div class="modal fade" id="kycUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('dashboard.kyc.submit') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Upload legal verification proof</h5>
            <p class="text-secondary mb-0">This is required before the first withdrawal. Registration itself stays open and simple.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-light border">
            Accepted formats: JPG, JPEG, PNG, or PDF up to 5 MB. Use a clear legal identity or responsibility proof that the admin team can review quickly.
          </div>
          <div class="mb-3">
            <label class="form-label">Proof file</label>
            <input type="file" name="kyc_proof" class="form-control @error('kyc_proof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
            @error('kyc_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="mb-0">
            <label class="form-label">Notes for admin</label>
            <textarea name="kyc_notes" rows="3" class="form-control @error('kyc_notes') is-invalid @enderror" placeholder="Optional context for the review team">{{ old('kyc_notes') }}</textarea>
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
