<section class="content"> 
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Loan Repayment <code>(Receipts)</code></h5>
    </div>
    <div class="card card-primary ">
        <div class="card-header">
          <h3 class="card-title">New Receipt</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-plus"></i> Add new receipt
            </button>
          </div>
        </div>
        <div class="card-body">
     
          <form wire:submit.prevent="addReceipt">
            @csrf
            <div class="card-body">
              <div class="row">
                <div  class="col-md-6 col-sm-12 form-group">
                  <label for="selectgroup">Select Group</label>
                  <div wire:ignore>
                    <select required  wire:model.live="selectedOption" id="selectgroup" class="form-control select2bs4" style="width: 100%;">
                      <option value="" >Select group...</option>
                      @foreach ($groups as $group_loan )
                        <option value="{{ $group_loan->id }}" wire:click="option('{{$group_loan->id}}')">{{ $group_loan->group->code}} - {{ $group_loan->group->name}} - {{ $group_loan->group->identifier}} - {{ $group_loan->loanPhase->phase}}</option>
                      @endforeach
                    </select>
                  </div>
                  @error('selectedOption')
                    <span class="text-sm text-danger">{{ $message }}</span>
                  @enderror
                </div>
                <button type="submit" class="btn btn-sm btn-primary" name="submit">submit</button>
                
                
              </div>
          
            </div>
          </form>
         
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</section>

@push('styles')
  <link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
  <link rel="stylesheet" href="{{asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
@endpush

@push('scripts')
  <script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>


  <script>
    $(function () {
      //Initialize Select2 Elements
      $('.select2').select2()

      //Initialize Select2 Elements
      $('.select2bs4').select2({
        theme: 'bootstrap4'
      })

      

      $('.select2').on('change', function (e) {
        @this.set('selectedOption', e.target.value);
      })

      $('.select2bs4').on('change', function (e) {
        @this.set('selectedOption', e.target.value);
      })
    });

   

    var text = ' Constituency Loan Receipts Information Summary';
        $(function () {
            var text = @this.constituency +' Constituency Members Information Summary';
            const table = new DataTable('#example1', {
                responsive: true, 
                lengthChange: true, 
                autoWidth: false,
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
                columnDefs: [
                    {
                        searchable: false,
                        orderable: false,
                        targets: 0
                    }
                ],
                
                processing: true,
            });

            table.draw();

            var buttonContainer = table.buttons().container();
            $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
        });
  </script>

  
@endpush
