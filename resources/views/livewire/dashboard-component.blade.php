<section class="content">
    <!-- SELECT constituency -->
    <div class="card card-primary m-auto w-75">
     <div class="card-header">
       <h3 class="card-title">{{$county->county_name}} - Assigned Constituencies</h3>

       <div class="card-tools">
         <button type="button" class="btn btn-tool" data-card-widget="collapse">
           <i class="fas fa-minus"></i>
         </button>
       </div>
     </div>
     <!-- /.card-header -->
     <div class="card-body">
      <div class="row">
        @if($constituencies->isEmpty())
            {{ auth()->user()->email }}
            <p class="text-bold col text-center text-danger">You have not been assigned to any constituency. Please contact your admininstrator!!</p>
        @else
          @foreach($constituencies as $const)
            <div class="col-12 col-sm-6 col-md-6">
              <div wire:click="selected('{{$const['constituency_code']}}')" class="info-box const">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-house-user"></i></span>

                <div class="info-box-content">
                  <span class="info-box-text">{{ ucwords(strtolower($const['constituency_name']))}} Constituency</span>
                  <button class="btn btn-primary">view</button>
                  <div wire:loading.delay wire:target="selected('{{$const['constituency_code']}}')">
                    Processing View...
                </div>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
          @endforeach
        @endif

        <div class="clearfix hidden-md-up"></div>
      </div>
     </div>
     <!-- /.card-body -->
     @if($constituencies != null)
        <div class="card-footer">
            <p class="text-sm text-gray">The above constituencies are the ones you are assigned to.</p>
        </div>
     @endif
     <!-- resources/views/livewire/your-livewire-component.blade.php -->
   </div>
   <!-- /.card -->
</section>
