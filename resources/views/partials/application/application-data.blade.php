<div class="row animate-on-load">
    {{-- LEFT COLUMN: Entity Profile & Detailed Financials --}}
    <div class="col-md-7">
        <div class="bg-white rounded-sm shadow-sm border overflow-hidden mb-4">
            {{-- 1. HEADER: Identity & Status --}}
            <div class="d-flex align-items-center justify-content-between px-4 py-3 bg-light border-bottom">
                <div>
                    <h5 class="mb-0 font-weight-bold text-dark tracking-tight">
                        {{ str($this->application->group_name)->title() }}
                    </h5>
                    <span class="text-muted d-block text-uppercase tracking-wider" style="font-size: 0.7rem;">
                        <i class="fas fa-fingerprint mr-1"></i> REG: {{ $this->application->group_registration_number }}
                    </span>
                    <span class="text-muted text-uppercase tracking-wider" style="font-size: 0.7rem;">
                        <i class="fas fa-fingerprint mr-1"></i> GFS: {{ $this->application?->phaseApplication->gfs_code ?? 'N/A' }}
                    </span>
                </div>
                <div class="text-right">
                    <span class="d-block font-weight-bold text-primary mb-0" style="font-size: 0.85rem;">
                        {{ ucfirst($this->application->nature_of_group) }} Group
                    </span>
                    <small class="text-muted">Registered {{ \Carbon\Carbon::parse($this->application->date_registered)->format('M d, Y') }}</small>
                </div>
            </div>

            <div class="p-4">
                {{-- 2. ROW: Location & Administration --}}
                <div class="mb-5">
                    <label class="text-muted small font-weight-bold text-uppercase tracking-widest mb-3 d-block border-bottom pb-2">
                        Location & Administration
                    </label>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 3rem;">
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">County / Constituency</span>
                            <span class="text-dark font-weight-medium">{{$this->application->constituency->county->county_name}} / {{$this->application->constituency->constituency_name}}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Administrative Ward</span>
                            <span class="text-dark font-weight-bold">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ $this->application->ward_name }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Meeting Point</span>
                            <span class="text-dark">{{ $this->application->meeting_point ?? 'Social Hall' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Landmark</span>
                            <span class="text-dark">
                                <i class="fas fa-search-location text-muted mr-1"></i> {{ $this->application->nearest_landmark }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 3. ROW: Financial Gateway (The Data Strip) --}}
                <div class="mb-5">
                    <label class="text-muted small font-weight-bold text-uppercase tracking-widest mb-3 d-block border-bottom pb-2">
                        Financial Gateway
                    </label>
                    <div class="d-flex align-items-center justify-content-between p-3 rounded bg-light border-left border-primary shadow-xs" style="border-left-width: 4px !important;">
                        <div class="d-flex align-items-center">
                            <div class="mr-5">
                                <span class="text-xs text-muted d-block text-uppercase mb-1">Bank Institution</span>
                                <span class="font-weight-bold text-dark">{{ $this->application->bank_name }}</span>
                            </div>
                            <div class="mr-4 border-left pl-5">
                                <span class="text-xs text-muted d-block text-uppercase mb-1">Branch</span>
                                <span class="text-dark">{{ $this->application->bank_branch }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Account Number</span>
                            <span class="font-mono font-weight-bold text-primary h5 mb-0" style="letter-spacing: 1px;">
                                {{ $this->application->bank_account_number }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 4. ROW: Activities & System Trace --}}
                <div class="pt-2">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label class="text-muted small font-weight-bold text-uppercase tracking-widest mb-3 d-block">
                                Primary Key Activities
                            </label>
                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.75rem;">
                                @forelse($this->application->key_activities ?? [] as $activity)
                                    <div class="d-flex align-items-center px-3 py-1 bg-white border rounded-pill shadow-xs transition-all hover-lift">
                                        <i class="fas fa-check-circle text-success mr-2" style="font-size: 0.75rem;"></i>
                                        <span class="text-dark small font-weight-medium">{{ $activity }}</span>
                                    </div>
                                @empty
                                    <span class="text-muted small italic">No activities reported</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-sm shadow-sm border overflow-hidden mb-4"
            x-data="{ showCard: false }"
            x-init="setTimeout(() => showCard = true, 100)"
            x-show="showCard"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0">

            @php
                $totalExpenses = collect($this->processedExpenses)->sum('amount');
                $netIncome = $this->application->average_income - $totalExpenses;
                $expenseRatio = $this->application->average_income > 0 ? ($totalExpenses / $this->application->average_income) * 100 : 0;
            @endphp

            {{-- 1. HEADER: Clean & Minimal --}}
            <div class="bg-light border-bottom px-4 py-3">
                <h6 class="mb-0 font-weight-bold text-dark text-uppercase tracking-wider">
                    <i class="fas fa-calculator mr-2 text-muted"></i>Financial & Operating Assessment
                </h6>
            </div>

            <div class="p-4">
                {{-- 2. OPERATING PROFILE: Horizontal Flow --}}
                <div class="mb-4">
                    <label class="text-muted small font-weight-bold text-uppercase tracking-widest mb-3 d-block border-bottom pb-2">
                        Operating Profile
                    </label>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 4rem;">
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Loan Purpose / Nature</span>
                            <span class="text-dark font-weight-bold">
                                <i class="fas fa-tag mr-1 text-muted"></i> {{ ucfirst($this->application->loan_nature ?? 'Expansion') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Business Experience</span>
                            <span class="text-dark font-weight-bold">
                                <i class="fas fa-history mr-1 text-muted"></i> {{ $this->application->experience_years ?? '2' }} Years
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 3. FINANCIAL SUMMARY: Unified Primary Color --}}
                <div class="mb-5">
                    <label class="text-muted small font-weight-bold text-uppercase tracking-widest mb-3 d-block border-bottom pb-2">
                        Monthly Performance (KES)
                    </label>
                    <div class="row no-gutters bg-light rounded border p-3">
                        <div class="col-md-4 text-center border-right">
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Monthly Income</span>
                            <span class="h5 font-weight-bold text-dark">{{ number_format($this->application->average_income, 2) }}</span>
                        </div>
                        <div class="col-md-4 text-center border-right">
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Operating Exp.</span>
                            <span class="h5 font-weight-bold text-dark">{{ number_format($totalExpenses, 2) }}</span>
                        </div>
                        <div class="col-md-4 text-center">
                            <span class="text-xs text-muted d-block text-uppercase mb-1">Net Surplus</span>
                            <span class="h5 font-weight-bold text-primary">{{ number_format($netIncome, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- 4. BURN RATE: Single Color Progress --}}
                <div class="p-3 rounded border" style="background-color: #fcfcfc;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold text-muted text-uppercase tracking-tighter" style="font-size: 0.75rem;">
                            Expense-to-Income Ratio
                        </span>
                        <span class="h6 font-weight-bold mb-0 text-primary">
                            {{ number_format($expenseRatio, 1) }}%
                        </span>
                    </div>
                    <div class="progress shadow-none" style="height: 8px; border-radius: 10px; background-color: #e9ecef;">
                        <div class="progress-bar bg-primary"
                            role="progressbar"
                            style="width: 0%; transition: width 1.5s ease;"
                            :style="'width: ' + {{ min($expenseRatio, 100) }} + '%'"
                            aria-valuenow="{{ $expenseRatio }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. EXPENSE TABLE: Subdued & Clean --}}
            <div class="table-responsive border-top">
                <table class="table table-hover mb-0">
                    <thead class="bg-light text-muted text-uppercase" style="font-size: 0.7rem;">
                        <tr>
                            <th class="pl-4 border-0 py-2 tracking-widest">Detailed Expense Item</th>
                            <th class="pr-4 border-0 py-2 text-right tracking-widest">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->processedExpenses ?? [] as $expense)
                        <tr class="transition-all">
                            <td class="pl-4 py-2.5 text-dark font-weight-medium">
                                <span class="text-muted mr-2">•</span>{{ $expense['name'] }}
                            </td>
                            <td class="pr-4 py-2.5 text-right font-weight-bold text-dark">
                                {{ number_format($expense['amount'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted small italic">No detailed expenses recorded</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        {{-- 1. Loan Request Summary Card --}}
        <div class="bg-white rounded-sm border shadow-sm mb-4 text-center overflow-hidden">
            <div class="card-body py-5">
                <span class="text-uppercase text-muted font-weight-bold tracking-widest d-block mb-2" style="font-size: 0.7rem;">
                    Requested Loan Amount
                </span>
                <h1 class="font-weight-bold text-dark mb-1" style="letter-spacing: -1px;">
                    KES {{ number_format($this->application->loan_amount, 2) }}
                </h1>

                <div class="d-flex justify-content-center mt-3 flex-wrap" style="gap: 12px;">
                    <span class="badge badge-light border text-dark px-3 py-2 font-weight-medium">
                        <i class="far fa-clock mr-1 text-primary"></i> {{ $this->application->proposed_repayment_period }} Months
                    </span>
                    <span class="badge badge-light border text-dark px-3 py-2 font-weight-medium">
                        <i class="fas fa-percent mr-1 text-primary"></i> 0% Interest
                    </span>
                </div>

                @include('partials.application.send-group-sms-button', ['class' => 'mt-3'])

                @php
                    $approvedAmount = $this->application->phaseApplication?->amount_approved
                        ?? $this->application->amount_approved;
                    $issuedAmount = $this->application->phaseApplication?->amount_issued;
                @endphp

                {{-- Approved State --}}
                @if($approvedAmount !== null && $approvedAmount !== '')
                    <div class="mt-4 pt-4 border-top">
                        <span class="text-uppercase text-muted font-weight-bold tracking-widest d-block mb-2" style="font-size: 0.7rem;">
                            @if (in_array($this->application->phaseApplication?->status, ['pending_hq_approval', 'pending_submission_to_hq']))
                                Proposed Amount
                            @else
                                Amount Approved
                            @endif
                        </span>
                        <h1 class="font-weight-bold text-dark mb-1" style="letter-spacing: -1px;">
                            KES {{ number_format($approvedAmount, 2) }}
                        </h1>
                    </div>
                @endif

                @if($issuedAmount !== null && $issuedAmount !== '')
                    <div class="mt-4 pt-4 border-top">
                        <span class="text-uppercase text-muted font-weight-bold tracking-widest d-block mb-2" style="font-size: 0.7rem;">
                            Amount Issued
                        </span>
                        <h1 class="font-weight-bold text-dark mb-1" style="letter-spacing: -1px;">
                            KES {{ number_format($issuedAmount, 2) }}
                        </h1>
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. Risk Assessment Card --}}
        <div class="bg-white rounded-sm border shadow-sm overflow-hidden"
            x-data="{ showFlags: false }"
            x-init="setTimeout(() => showFlags = true, 300)"
            x-show="showFlags"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 transform translate-x-4"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            style="border-left: 5px solid {{ in_array($this->riskCategory, ['High Risk', 'Critical Risk']) ? '#dc3545' : 'var(--color-primary)' }} !important;">

            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <span class="font-weight-bold small text-uppercase tracking-widest text-muted d-block mb-1">Risk Assessment</span>
                        <div class="h5 mb-0 font-weight-bold {{ in_array($this->riskCategory, ['High Risk', 'Critical Risk']) ? 'text-danger' : 'text-dark' }}">
                            {{ $this->riskCategory }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="px-3 py-1 bg-light border rounded text-dark font-weight-bold font-mono" style="font-size: 0.9rem;">
                            Score: {{ $this->riskAssessmentData['total_score'] }}
                        </div>
                    </div>
                </div>


                @php
                    $ml = $this->mlScoreData ?? null;
                    $mlIntake = is_array($ml) ? ($ml['intake'] ?? null) : null;
                    $mlEarly = is_array($ml) ? ($ml['earlywarn'] ?? null) : null;
                @endphp
                @if($mlIntake)
                    <div class="mb-4">
                        <span class="font-weight-bold small text-uppercase tracking-widest text-muted d-block mb-2 text-center">ML repayment risk</span>
                        @include('partials.application.ml-tu-gauge', [
                            'mlIntake' => $mlIntake,
                            'mlEarly' => $mlEarly,
                            'showEarlywarn' => true,
                        ])
                    </div>
                @endif

                <label class="text-xs text-muted text-uppercase font-weight-bold tracking-wider mb-3 d-block">Identified Flags</label>
                <ul class="list-unstyled mb-0">
                    @forelse($this->riskFlags as $flag)
                        <li class="small text-dark mb-3 d-flex align-items-start">
                            <div class="mr-3 mt-1">
                                <i class="fas fa-circle {{ in_array($this->riskCategory, ['High Risk', 'Critical Risk']) ? 'text-danger' : 'text-primary' }}" style="font-size: 0.5rem;"></i>
                            </div>
                            <span class="font-weight-medium" style="line-height: 1.5; font-size: 0.85rem;">{{ $flag }}</span>
                        </li>
                    @empty
                        <li class="small text-muted d-flex align-items-center py-2 bg-light rounded px-3">
                            <i class="fas fa-shield-alt text-primary mr-2"></i>
                            No risk flags identified.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

</div>



