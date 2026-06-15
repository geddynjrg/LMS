<table>
    @php
        $loans = $application->phaseApplication?->linkedGroup?->groupLoans ?? collect();

        $lastFYStart = ($currentFY->start - 1) . '-07-01';
        $lastFYEnd   = $currentFY->start . '-06-30';

        $lastFYLoans = $loans->filter(function($loan) use ($lastFYStart, $lastFYEnd) {
            return $loan->disbursement_date >= $lastFYStart && $loan->disbursement_date <= $lastFYEnd;
        });

    @endphp
    <thead>
        <tr><th colspan="7" style="text-align: center; font-weight: bold;">UWEZO FUND</th></tr>
        <tr><th colspan="7" style="text-align: center; font-weight: bold;">GROUP / INSTITUTIONAL INFORMATION</th></tr>
        <tr><th colspan="7" style="text-align: center;">Republic of Kenya</th></tr>
        <tr><th colspan="7" style="text-align: center;">Uwezo Fund Oversight Board</th></tr>
        <tr>
            <th colspan="7" style="text-align: center;">
                Name of Constituency: {{ $constituency->constituency_name }},
                IEBC Code: {{ $constituency->constituency_code }},
                County: {{ strtoupper($constituency->county->county_name) }}
            </th>
        </tr>
        <tr><th colspan="7" style="text-align: center;">For the Month Ended: {{ now()->format('d F Y') }}</th></tr>

        {{-- Group Info Section --}}
        <tr><th colspan="7"> <b>Group / Institution No. : </b> {{ $application->group_registration_number }}</th></tr>
        <tr><th colspan="7"><b>Group / Institution Name: </b> {{ strtoupper($application->group_name) }}</th></tr>
        <tr>
            <th colspan="7">
                <b>Sector:</b>
                {{
                    collect($application->business_type)->map(function($bs) {
                        return ucfirst($bs);
                    })->implode(', ')
                }}
            </th>
        </tr>

        <tr><th colspan="7"><b>Status: Start Up (New)</b>.......{{ $application->nature_of_business == 'startup' ? '√' : '' }}....... EXPANSION.......{{ $application->nature_of_business == 'expansion' ? '√' : '' }}.......</th></tr>
        <tr>
            <th colspan="7">
                <b>Brief statement on group/ institution status at time of submission:</b>

                @forelse($application->key_activities as $activity)
                    ...........{{ $activity }}{{ $loop->last ? '.' : ', ' }}
                @empty
                    ...........N/A.
                @endforelse
            </th>
        </tr>
        <tr><th colspan="7"><b>Financial Year:...........</b> {{ $currentFY->label }}</th></tr>
        <tr>
            <th colspan="7"><b>Initial or other loans applied:</b>
                @forelse($application->phaseApplication?->linkedGroup?->groupLoans ?? [] as $loan)
                    ...........Kshs {{ number_format($loan->amount_issued) }} ({{ \Carbon\Carbon::parse($loan->disbursement_date)->format('d/m/Y') }}){{ $loop->last ? '' : ', ' }}
                @empty
                    ...........Kshs 0.00
                @endforelse
            </th>
        </tr>
        <tr>
            <th colspan="7">
                <b>Loan allocated last financial year</b> (FY {{ ($currentFY->start - 1) . '/' . $currentFY->start }}):
                @forelse($lastFYLoans as $loan)
                    ...........Kshs {{ number_format($loan->amount_issued) }} ({{ \Carbon\Carbon::parse($loan->disbursement_date)->format('d/m/Y') }}){{ $loop->last ? '' : ', ' }}...........
                @empty
                    ...........Kshs 0.00
                @endforelse
            </th>
        </tr>
        <tr>
            <th colspan="7">
                <b>Loan allocated this Financial year</b> (FY {{ $currentFY->start . '/' . ($currentFY->end) }}): ...........Kshs {{ $application->phaseApplication?->amount_approved ?? 0.0 }}...........
            </th>
        </tr>
        <tr><th colspan="7"></th></tr> {{-- Empty spacer --}}

        {{-- Table Data Header --}}
        <tr>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">S/N</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">Name</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">Position</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">ID Number</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">Phone Number</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">Gender</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000;">PWD Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($application->groupMembers as $index =>$groupMember)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ ucwords($groupMember->name) }}</td>
            <td>{{ ucwords($groupMember->position) }}</td>
            <td>{{ $groupMember->id_number }}</td>
            <td>{{ $groupMember->phone_number }}</td>
            <td>{{ strtoupper($groupMember->gender) }}</td>
            <td>{{ $groupMember->person_with_disability?'PLWD' : 'N/A' }}</td>
        </tr>

        @empty
        <tr>
            <td colspan="7" style="text-align: center;">No group members found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
