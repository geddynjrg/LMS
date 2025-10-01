<section class="content">
  <div class="container-fluid">
      <h5 class="mt-4 mb-2">Code List </h5>
  </div>
  <div class="card">
      <div class="card-header">
        <h3 class="card-title">Loan Submission Form</h3>
        <div class="btn-group float-right m-2" role="group">
          <button type="button" class="btn btn-primary mr-2" wire:click="export" wire:loading.attr="disabled">
              <span wire:loading >loading...</span>
              <span wire:loading.remove ></i><i class="fas fa-file-excel"></i> Export Excel </span>
          </button>
        </div>
      </div>
      <!-- /.card-header -->

      <!-- card body -->
      <div wire:ignore class="card-body table-responsive p-0" >
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
              <th class="bg-gradient-cyan">AMOUNT ISSUED</th>
              <th class="bg-gradient-cyan">AMOUNT REPAID</th>
            </tr>
          </thead>
          <tbody class="text-bold">
            @php
                $i = 1;
            @endphp
            @foreach ($phases as $phase)
              @php
                $newLoans = $phase->grouploans->where('level', 'New');
                $resubmissionLoans = $phase->grouploans->where('level', 'Resubmission');
                $refinancingLoans = $phase->grouploans->where('level', 'Refinancing');
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
                    <td>{{$grouploan->group->name}} </td>
                    <td>{{$grouploan->group->gfs_code}} </td>
                    <td>{{$grouploan->group->activity}} </td>
                    <td>{{$grouploan->group->category}} </td>
                    <td>{{number_format($grouploan->amount_approved)}} </td>
                    <td>{{number_format($grouploan->amount_issued)}} </td>
                    <td>{{number_format($grouploan->amount_repaid)}} </td>
                  </tr>
                @endforeach
                <tr class="bg-gray ">
                  <td></td>
                  <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - New</td>
                  <td>{{number_format($newLoans->sum('amount_approved'))}}</td>
                  <td>{{number_format($newLoans->sum('amount_issued'))}}</td>
                  <td>{{number_format($newLoans->sum('amount_repaid'))}}</td>
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
                    <td>{{$grouploan->group->name}} </td>
                    <td>{{$grouploan->group->gfs_code}} </td>
                    <td>{{$grouploan->group->activity}} </td>
                    <td>{{$grouploan->group->category}} </td>
                    <td>{{number_format($grouploan->amount_approved)}} </td>
                    <td>{{number_format($grouploan->amount_issued)}} </td>
                    <td>{{number_format($grouploan->amount_repaid)}} </td>
                  </tr>
                @endforeach
                <tr class="bg-gray ">
                  <td></td>
                  <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - Resubmission</td>
                  <td>{{number_format($resubmissionLoans->sum('amount_approved'))}}</td>
                  <td>{{number_format($resubmissionLoans->sum('amount_issued'))}}</td>
                  <td>{{number_format($resubmissionLoans->sum('amount_repaid'))}}</td>
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
                    <td>{{$grouploan->group->name}} </td>
                    <td>{{$grouploan->group->gfs_code}} </td>
                    <td>{{$grouploan->group->activity}} </td>
                    <td>{{$grouploan->group->category}} </td>
                    <td>{{number_format($grouploan->amount_approved)}} </td>
                    <td>{{number_format($grouploan->amount_issued)}} </td>
                    <td>{{number_format($grouploan->amount_repaid)}} </td>
                  </tr>
                @endforeach
                <tr class="bg-gray ">
                  <td></td>
                  <td class="text-center" colspan="5"> SubTotal {{$phase->phase}} - Refinancing</td>
                  <td>{{number_format($refinancingLoans->sum('amount_approved'))}}</td>
                  <td>{{number_format($refinancingLoans->sum('amount_issued'))}}</td>
                  <td>{{number_format($refinancingLoans->sum('amount_repaid'))}}</td>
                </tr>
              @endif
            @endforeach
            <tr class="bg-gray text-lg text-bold">
              <td></td>
              <td class="text-center " colspan="5"> Totals </td>
              <td>{{number_format($totalAmountApproved)}}</td>
              <td>{{number_format($totalAmountIssued)}}</td>
              <td>{{number_format($totalAmountRepaid)}}</td>
            </tr>
          </tbody>
        </table>
      </div>
  </div>
</section>
@push('scripts')
<script src="{{asset('plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('plugins/datatables-buttons/js/buttons.colvis.min.js')}}"></script>
<script>
    $(function () {

      $('#example2').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
      });

      $('#example').DataTable({
            "paging": true,
            "searching": true,
            "info": false
        });
    });
  </script>
  <script>
    $(document).ready(function() {

    });
</script>
@endpush
