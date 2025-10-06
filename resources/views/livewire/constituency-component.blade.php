<section class="content">
    <div class="container-fluid">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3>{{ $totalgroups }}</h3>
              <p>Total Groups</p>
            </div>
            <div class="icon">
              <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('const.groups.index', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3>{{ $totalmembers }}</h3>
              <p>Total Members</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="{{ route('const.groups.members', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- NEW: Total Amount Approved Card -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-secondary">
            <div class="inner">
              <h3>{{ number_format($totalAmountApproved) }}/=</h3>
              <p>Total Amount Approved</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('const.loans.grouploans', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>{{ number_format($totalAmountIssued) }}/=</h3>
              <p>Actual Amount Issued</p>
            </div>
            <div class="icon">
              <i class="fas fa-hand-holding-usd"></i>
            </div>
            <a href="{{ route('const.loans.grouploans', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>
      <div class="row">
         <div class="col-lg-3 col-6">
          <div class="small-box bg-orange">
            <div class="inner">
              <h3>{{ number_format($totalAmountDue) }}</h3>
              <p>Total Amount Due</p>
              <small>Scheduled payments to date</small>
            </div>
            <div class="icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3>{{ number_format($totalAmountRepaid) }}/=</h3>
              <p>Cumulative Amount Repaid</p>
            </div>
            <div class="icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <a href="{{ route('const.loans.loanreceipts', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-purple">
            <div class="inner">
              <h3>{{ number_format($totalAmountDue - $totalAmountRepaid) }}/=</h3>
              <p>Cumulative Arrears</p>
              <small>Total Due - Total Repaid</small>
            </div>
            <div class="icon">
              <i class="fas fa-money-bill-alt"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-dark">
            <div class="inner">
              <h3>{{ number_format($outstandingBalance) }}/=</h3>
              <p>Outstanding Balance</p>
            </div>
            <div class="icon">
              <i class="fas fa-scale-balanced"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>



      </div>

      <!-- Default Analysis Row -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-primary">
            <div class="inner">
              <h3>{{ $repaymentProgressRate }}<sup style="font-size: 20px">%</sup></h3>
              <p>Repayment Progress Rate</p>
              <small>{{ $repaymentProgressRate }}% of total disbursed</small>
            </div>
            <div class="icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $repaymentPerformanceRate >= 100 ? 'bg-success' : ($repaymentPerformanceRate >= 80 ? 'bg-warning' : 'bg-danger') }}">
                <div class="inner">
                    <h3>{{ $repaymentPerformanceRate }}<sup style="font-size: 20px">%</sup></h3>
                    <p>Repayment Performance</p>
                    <small>
                        @if($repaymentPerformanceRate >= 100)
                            All scheduled payments made
                        @else
                            {{ $repaymentPerformanceRate * -1}}% behind schedule
                        @endif
                    </small>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-maroon">
            <div class="inner">
              <h3>{{ $defaultedLoans }}</h3>
              <p>Defaulted Loans</p>
              <small>Default Rate: {{ $defaultRate }}%</small>
            </div>
            <div class="icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            {{-- You might want to create a specific route for defaulted loans --}}
            <a href="{{ route('const.loans.grouploans', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}?filter=defaulted" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-dark">
            <div class="inner">
              <h3>{{ $averageDefaultMonths }}</h3>
              <p>Avg. Months Defaulted</p>
            </div>
            <div class="icon">
              <i class="fas fa-clock"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-pink">
            <div class="inner">
              <h3>{{ $womenGroups }}</h3>
              <p>Women Groups</p>
            </div>
            <div class="icon">
              <i class="fas fa-female"></i>
            </div>
            {{-- Fix: Use correct route name with filter --}}
            <a href="{{ route('const.groups.index', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}?category=Women" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-orange">
            <div class="inner">
              <h3>{{ $youthGroups }}</h3>
              <p>Youth Groups</p>
            </div>
            <div class="icon">
              <i class="fas fa-child"></i>
            </div>
            {{-- Fix: Use correct route name with filter --}}
            <a href="{{ route('const.groups.index', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}?category=Youth" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box bg-purple">
            <div class="inner">
              <h3>{{ $pwdGroups }}</h3>
              <p>PWD Groups</p>
            </div>
            <div class="icon">
              <i class="fas fa-wheelchair"></i>
            </div>
            {{-- Fix: Use correct route name with filter --}}
            <a href="{{ route('const.groups.index', [
                'county' => $constituency->county->slug,
                'constituency' => $constituency->slug
            ]) }}?category=PWD" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <!-- Refresh button -->
      <div class="row mt-3">
        <div class="col-12 text-center">
          <button wire:click="refreshStats" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-sync-alt"></i> Refresh Stats
          </button>
        </div>
      </div>
    </div>
</section>
