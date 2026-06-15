<table id="example1" class="table table-sm  table-head-fixed table-bordered table-valign-middle" >
  <thead >
    <tr>
      <th class="bg-gradient-cyan">S/N</th>
      <th class="bg-gradient-cyan">DATE OF DISBURSEMNT</th>
      <th class="bg-gradient-cyan">GROUP/ INSTITUITION</th>
      <th class="bg-gradient-cyan">GFS CODE</th>
      <th class="bg-gradient-cyan">SECTOR</th>
      <th class="bg-gradient-cyan">CATEGORY</th>
      <th class="bg-gradient-cyan">AMOUNT APPROVED</th>
      <th class="bg-gradient-cyan">CHEQUE NO.</th>
      <th class="bg-gradient-cyan">AMOUNT ISSUED</th>
      <th class="bg-gradient-cyan">REMARKS</th>
    </tr>
  </thead>
  <tbody class="text-bold">
    @php
        $i = 1;
    @endphp
    @foreach ($phases as $phase)
      @php
        $newLoans = $phase->grouploans->where('loan_type', 'New');
        $resubmissionLoans = $phase->grouploans->where('loan_type', 'Resubmission');
        $refinancingLoans = $phase->grouploans->where('loan_type', 'Refinancing');
      @endphp
      <!-- new groups -->
      @if($newLoans->isNotEmpty())
        <tr>
          <td class="bg-gray"></td>
          <td colspan="9" class="text-center text-bold bg-gray">{{$phase->phase}} - New</td>
        </tr>
        @foreach ($newLoans as $grouploan)
          <tr>
            <td class="bg-gray">{{ $i++ }}</td>
            <td>{{$grouploan->disbursement_date}} </td>
            <td>{{$grouploan->group_name}} </td>
            <td>{{$grouploan->gfs_code}} </td>
            <td>{{$grouploan->sector}} </td>
            <td>{{$grouploan->category}} </td>
            <td>{{number_format($grouploan->amount_approved)}} </td>
            <td>{{$grouploan->cheque_no}} </td>
            <td>{{number_format($grouploan->amount_issued)}} </td>
            <td>{{$grouploan->comments}} </td>
          </tr>
        @endforeach
        <tr class="bg-gray ">
          <td></td>
          <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - New</td>
          <td>{{number_format($newLoans->sum('amount_approved'))}}</td>
          <td></td>
          <td>{{number_format($newLoans->sum('amount_issued'))}}</td>
          <td></td>
        </tr>
      @endif

      <!-- Resubmission -->
      @if($resubmissionLoans->isNotEmpty())
        <tr>
          <td class="bg-gray"></td>
          <td colspan="9" class="text-center text-bold bg-gray">{{$phase->phase}} - Resubmission</td>
        </tr>
        @foreach ($resubmissionLoans as $grouploan)
          <tr>
            <td class="bg-gray">{{ $i++ }}</td>
            <td>{{$grouploan->disbursement_date}} </td>
            <td>{{$grouploan->group_name}} </td>
            <td>{{$grouploan->gfs_code}} </td>
            <td>{{$grouploan->sector}} </td>
            <td>{{$grouploan->category}} </td>
            <td>{{number_format($grouploan->amount_approved)}} </td>
            <td>{{$grouploan->cheque_no}} </td>
            <td>{{number_format($grouploan->amount_issued)}} </td>
            <td>{{$grouploan->comments}} </td>
          </tr>
        @endforeach
        <tr class="bg-gray ">
          <td></td>
          <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - Resubmission</td>
          <td>{{number_format($resubmissionLoans->sum('amount_approved'))}}</td>
          <td></td>
          <td>{{number_format($resubmissionLoans->sum('amount_issued'))}}</td>
          <td></td>
        </tr>
      @endif

      <!-- Refinancing -->
      @if($refinancingLoans->isNotEmpty())
        <tr>
          <td class="bg-gray"></td>
          <td colspan="9" class="text-center text-bold bg-gray">{{$phase->phase}} - Refinancing</td>
        </tr>
        @foreach ($refinancingLoans as $grouploan)
          <tr>
            <td class="bg-gray">{{ $i++ }}</td>
            <td>{{$grouploan->disbursement_date}} </td>
            <td>{{$grouploan->group_name}} </td>
            <td>{{$grouploan->gfs_code}} </td>
            <td>{{$grouploan->sector}} </td>
            <td>{{$grouploan->category}} </td>
            <td>{{number_format($grouploan->amount_approved)}} </td>
            <td>{{$grouploan->cheque_no}} </td>
            <td>{{number_format($grouploan->amount_issued)}} </td>
            <td>{{$grouploan->comments}} </td>
          </tr>
        @endforeach
        <tr class="bg-gray ">
          <td></td>
          <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - Refinancing</td>
          <td>{{number_format($refinancingLoans->sum('amount_approved'))}}</td>
          <td></td>
          <td>{{number_format($refinancingLoans->sum('amount_issued'))}}</td>
          <td></td>
        </tr>
      @endif
    @endforeach
    <tr class="bg-gray text-lg text-bold">
      <td></td>
      <td class="text-center " colspan="5"> Totals </td>
      <td>{{number_format($totalAmountApproved)}}</td>
      <td></td>
      <td>{{number_format($totalAmountIssued)}}</td>
      <td></td>
    </tr>
  </tbody>
</table>
