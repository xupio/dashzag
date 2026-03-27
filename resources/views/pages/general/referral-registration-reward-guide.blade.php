@extends('layout.master')

@section('content')
<div class="row">
  <div class="col-12 grid-margin">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Referral Registration Reward Guide</h4>
        <p class="text-secondary mb-0">Review the active rule logic, explanation, and number examples for the referral registration reward.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.rewards.referral-registration-guide.examples') }}" class="btn btn-outline-success btn-icon-text">
          <i data-lucide="download" class="btn-icon-prepend"></i> Download Excel sample
        </a>
        <a href="{{ route('dashboard.rewards') }}" class="btn btn-outline-primary btn-icon-text">
          <i data-lucide="badge-dollar-sign" class="btn-icon-prepend"></i> Reward settings
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Rule 1</div>
        <div class="fw-semibold fs-5 mb-2">Basic 100 required</div>
        <div class="small text-secondary">The reward owner must already be under an active Basic 100 investment before any registration reward can unlock.</div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Rule 2</div>
        <div class="fw-semibold fs-5 mb-2">3-level tree only</div>
        <div class="small text-secondary">Only active investment volume inside the first 3 sponsor levels is counted for registration reward unlocking.</div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="text-secondary small mb-1">Rule 3</div>
        <div class="fw-semibold fs-5 mb-2">50% unlock cap</div>
        <div class="small text-secondary">The visible reward may be higher, but the available reward cannot exceed 50% of that 3-level active tree investment volume.</div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Detailed explanation</h5>
            <p class="text-secondary mb-0">This is the same explanation stored in the project documentation.</p>
          </div>
          <span class="badge bg-light text-dark border">Live guide</span>
        </div>

        <div class="d-flex flex-column gap-3">
          @foreach ($rewardGuideLines as $line)
            @if (str_starts_with($line, '# '))
              <div class="border rounded p-3 bg-light">
                <div class="fw-semibold">{{ trim(substr($line, 2)) }}</div>
              </div>
            @elseif (str_starts_with($line, '## '))
              <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-2">{{ trim(substr($line, 3)) }}</div>
              </div>
            @else
              <div class="border rounded p-3 bg-light small text-secondary">{{ $line }}</div>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Number examples</h5>
            <p class="text-secondary mb-0">These sample rows are the same ones exported in the Excel-friendly CSV file.</p>
          </div>
          <a href="{{ route('dashboard.rewards.referral-registration-guide.examples') }}" class="btn btn-sm btn-outline-success">Download CSV</a>
        </div>

        @if ($rewardGuideExamples->isEmpty())
          <p class="text-secondary mb-0">No sample rows are available right now.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Scenario</th>
                  <th>Visible</th>
                  <th>Cap</th>
                  <th>Available</th>
                  <th>Pending</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($rewardGuideExamples as $example)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $example['Scenario'] }}</div>
                      <div class="text-secondary small">{{ $example['Explanation'] }}</div>
                    </td>
                    <td>${{ number_format((float) ($example['Visible Registration Reward'] ?? 0), 2) }}</td>
                    <td>${{ number_format((float) ($example['50 Percent Unlock Cap'] ?? 0), 2) }}</td>
                    <td class="text-success fw-semibold">${{ number_format((float) ($example['Available Reward'] ?? 0), 2) }}</td>
                    <td class="text-warning fw-semibold">${{ number_format((float) ($example['Pending Reward'] ?? 0), 2) }}</td>
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
@endsection
