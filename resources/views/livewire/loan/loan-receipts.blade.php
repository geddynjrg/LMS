<section class="content">
    <div class="container-fluid">
      <h5 class="mt-4 mb-2">Loan Repayment <code>(Receipts)</code></h5>

      <!-- card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Loan Receipts Information</h3>
          @can('create receipt')
          <button type="button" class="btn btn-primary mr-2 float-right" wire:loading.attr="disabled" data-toggle="modal" data-target="#add-receipt-modal">
            <span wire:loading wire:target="addNewReciept"><i class="fas fa-spinner fa-spin"></i> Loading...</span>
            <span wire:loading.remove wire:target="addNewReciept"></i> <i class="fas fa-plus"></i> Add New Receipt </span>
          </button>
          @endcan

        </div>
        <!-- /.card-header -->

        <div wire:ignore class="card-body">
          <table id="example1" class="table table-bordered table-striped" >
              <thead class="bg-gradient-cyan">
                    <tr>
                      <th>#</th>
                      <th>id</th>
                      <th>Receipt No.</th>
                      <th>Transaction Date</th>
                      <th>Group</th>
                      <th>Amount</th>
                      <th>Payment Mode</th>
                      <th>Reference No.</th>
                      <th>Group Id</th>
                      <th>Action</th>
                    </tr>
              </thead>
              <tbody>
                  @php
                      $i = 1;
                  @endphp
                  @foreach ($receipts as $receipt)
                  <tr wire:key="{{ $receipt->id }}">
                      <td>{{ $i++ }}</td>
                      <td>{{ $receipt->id }}</td>
                      <td>{{ $receipt->receipt_number}}</td>
                      <td>{{ $receipt->transaction_date }}</td>
                      <td class="col-3 text-capitalize">{{ strtolower($receipt->grouploan->group->name) }}</td>
                      <td>{{ number_format($receipt->amount) }}</td>
                      <td>{{ $receipt->payment  }}</td>
                      <td>{{ $receipt->reference_number }}</td>
                      <td>{{ $receipt->group_loans_id }} </td>
                      <td >
                        @can('update receipt')
                            <button type="button" id="editReceipt"  class="btn btn-sm p-0 m-0"><i class="fas fa-edit text-primary"></i></button>
                        @endcan

                        |
                        @can('delete receipt')
                            <button type="button" id="deleteReceipt" class="btn btn-sm p-0 m-0"><i class="fas fa-trash-alt text-danger"></i></button>
                        @endcan
                  </tr>
                  @endforeach
              </tbody>
              <tfoot>
                <th>#</th>
                <th>id</th>
                <th>Receipt No.</th>
                <th>Transaction Date</th>
                <th>Group</th>
                <th>Amount</th>
                <th>Payment Mode</th>
                <th>Reference No.</th>
                <th>Group Id</th>
                <th>Action</th>
              </tfoot>
          </table>
        </div>
      </div>
      <!-- /card -->
    </div>
    <!-- /container -->

    <!--add new receipt Modal -->
    <div  class="modal fade" id="add-receipt-modal">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title" id="addreceipt">Add New Receipt</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <livewire:add-receipt :constituency="$constituency">
              </div>
          </div>
          <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>

    <!--Edit receipt Modal -->
    <div  class="modal fade" id="edit-receipt-modal">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title" id="addreceipt">Edit Receipt</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <livewire:edit-receipt :constituency="$constituency">
              </div>
          </div>
          <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.Edit receipt modal -->

    <!--delete receipt Modal -->
    <div  class="modal fade" id="delete-receipt-modal">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title" id="addreceipt">Delete Receipt</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <livewire:delete-receipt>
              </div>
          </div>
          <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.Delete receipt modal -->
</section>

@push('styles')
   <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush

@push('scripts')
  <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
  <script src="{{asset('plugins/jszip/jszip.min.js')}}"></script>
  <script src="{{asset('plugins/pdfmake/pdfmake.min.js')}}"></script>
  <script src="{{asset('plugins/pdfmake/vfs_fonts.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.colvis.min.js')}}"></script>

  <script>
    $(function () {
      initDataTable();
    });

    function initDataTable() {
      var text = @this.constituency +' Constituency Receipts Information Summary';
      const table = new DataTable('#example1',
      {
          scrollX: true,
          lengthChange: true,
          autoWidth: true,
          buttons: [
                  {
                      extend: 'print',
                      title: text,
                      text: 'Print',
                      exportOptions: {
                          columns: [':not(:last-child)']
                      },
                      footer: true
                  },
                  {
                      extend: 'csv',
                      title: text,
                      text: 'CSV',
                      exportOptions: {
                          columns: [':not(:last-child)']
                      },
                      footer: true
                  },
                  {
                      extend: 'pdf',
                      title: text,
                      text: 'Pdf',
                      exportOptions: {
                          columns: [':not(:last-child)']
                      },
                      footer: true
                  },
                  {
                      extend: 'excelHtml5',
                      title: text,
                      text: 'Excel',
                      exportOptions: {
                          columns: [':not(:last-child)']
                      },
                      footer: true
                  },

              ],


          processing: true,
          columnDefs: [
            {
              targets: 1,
              visible: false,
              searchable: false
            },
            {
              targets: 8,
              visible: false,
              searchable: false
            },
            {
              searchable: false,
              targets: -1
            }
          ],
      });

      table.draw();
      table.on('click', '#editReceipt', function (e) {
        let data = table.row($(this).closest('tr')).data();
        Livewire.dispatch('edit-receipt-modal', { data: data});
        $("#edit-receipt-modal").modal('show');
      });

      table.on('click', '#deleteReceipt', function (e) {
        let data = table.row($(this).closest('tr')).data();
        console.log(data)
        Livewire.dispatch('delete-receipt-modal', { data: data});
        $("#delete-receipt-modal").modal('show');
      });

      var buttonContainer = table.buttons().container();
      $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
    }




    document.addEventListener('show-edit-receipt-modal', function (event) {
      $("#edit-receipt-modal").modal('show');
    });

    document.addEventListener('receipt-edited', function (event) {
      $("#edit-receipt-modal").addClass('fade').modal('hide');
      toastr.success('Receipt Edited Successfully.');
      location.reload();
    });

    document.addEventListener('receipt-added', function (event) {
      $("#add-receipt-modal").addClass('fade').modal('hide');
      toastr.success('Receipt Recorded Successfully.');
      location.reload();
    });

    document.addEventListener('receipt-deleted', function (event) {
      $("#delete-receipt-modal").addClass('fade').modal('hide');
      toastr.success('Receipt Deleted Successfully.');
      location.reload();
    });




  </script>


@endpush
