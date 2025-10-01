<table id="example1" class="table table-sm  table-head-fixed table-bordered table-valign-middle" >
    <thead>
      <tr>
        <th class="text-center" colspan="5">UWEZO FUND</th>
      </tr>
      <tr>
        <th class="text-center" colspan="5">GROUP / INSTITUTIONAL INFORMATION</th>
      </tr>
      <tr>
        <th class="text-center" colspan="5">Republic of Kenya</th>
      </tr>
      <tr>
        <th class="text-center" colspan="5">Uwezo Fund Oversight Board</th>
      </tr>
      <tr>
        <th class="text-center" colspan="5">Name of Constituency: {{ $constituency->constituency_name }},     IEBC Code: {{ $constituency->constituency_code }}, County: {{strtoupper(auth()->user()->county->county_name)}}</th>
      </tr>
      <tr>
        <th class="text-center" colspan="5">For the Month Ended: {{ \Carbon\Carbon::now()->isoFormat('Do MMMM YYYY') }}</th>
      </tr>
      <tr>
        <th colspan="5">Group / Institution No. : {{$group->reg_number}}</th>
      </tr>
      <tr>
        <th colspan="5">Group / Institution Name: {{strtoupper($group->name)}}</th>
      </tr>
      <tr>
        <th colspan="5">Sector: {{$group->activity}}	</th>
      </tr>
      <tr>
        <th colspan="5">Status of the Group (tick one) Start Up (New)……… EXPANSION……√…………..		</th>
      </tr>
      <tr>
        <th colspan="5">Brief statement on group/ institution status at time of submission: {{$group->activity_desc}}.	</th>
      </tr>
      <tr>
        <th colspan="5">Financial Year: {{$date}}</th>
      </tr>
      <tr>
        <th colspan="5">Initial or other loans applied,
            @if($group->groupLoans->count()>0)
                @foreach($group->groupLoans as $groupLoan)
                @if ($groupLoan->amount_issued < 1)
                Kshs 00. Dated……'NILL'..
                @else
                Kshs {{number_format($groupLoan->amount_issued)}}. Dated……{{$groupLoan->disbursement_date }}..,
                @endif
                @endforeach
            @endif
        </th>
      </tr>
      <tr>
        <th colspan="5">Loan allocated last financial year Kshs___________		</th>
      </tr>
      <tr>
        <th colspan="5">Loan allocated this Financial year ...........	</th>
      </tr>
      <tr>
        <th colspan="5"></th>
      </tr>
      <tr>
        <th>S/N</th>
        <th>Name</th>
        <th>Position</th>
        <th>ID Number</th>
        <th>Phone Number</th>
      </tr>
    </thead>
    <tbody>
        @php
            $i = 1;
        @endphp
        @foreach ($group->groupLoans->last()->groupMembers as $groupMember)
        <tr>
            <td class="col-1">{{$i++}}</td>
            <td class="col-4">{{$groupMember->member->name}}</td>
            <td>{{$groupMember->role}}</td>
            <td>{{$groupMember->member_Id}}</td>
            <td>{{$groupMember->member->number}}</td>
        </tr>
        @endforeach

    </tbody>
    <tfoot>
        <tr>
            <th colspan="5"></th>
        </tr>
        <tr>
            <th colspan="5"></th>
        </tr>
        <tr>
            <th colspan="5">PREPARED BY:  NAME: .................................................................. SIGN................ DATE ...........</th>
        </tr>
        <tr>
            <th colspan="5">CONFIRMED BY:  CHAIR (CUFMC): .......................................................... SIGN................ DATE ...........</th>
        </tr>
    </tfoot>
</table>
