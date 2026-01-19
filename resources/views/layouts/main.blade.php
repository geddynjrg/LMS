<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/uwezo_fund_logo.png') }}">

        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.min.css')}}">

        <!-- overlayScrollbars -->
        <link rel="stylesheet" href="{{asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
        @stack('styles')
        <!-- Theme style -->
        <link rel="stylesheet" href="{{asset('dist/css/adminlte.min.css')}}">
        <link rel="stylesheet" href="{{asset('dist/css/custom.css')}}">

          <!-- SweetAlert2 -->
        <link rel="stylesheet" href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">

        {{-- toastr --}}
        <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">

        {{-- select 2 --}}
        <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.css') }}">


        <!-- DataTables style -->
        <link rel="stylesheet" href="{{asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
        <link rel="stylesheet" href="{{asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">

        {{-- livewire styles --}}
        @livewireStyles

        {{-- custom styles --}}
        <style>
            :root {
                --uwezo-blue: #003366;   /* Deep Navy */
                --uwezo-gold: #FFCC00;   /* Gold Accent */
                --uwezo-light: #f8f9fa;
            }

            .uwezo-card { border-top: 3px solid var(--uwezo-blue) !important; }
            .btn-uwezo { background-color: var(--uwezo-blue); color: white; border: none; }
            .btn-uwezo:hover { background-color: #002244; color: #FFCC00; }
            .text-uwezo { color: var(--uwezo-blue) !important; }
            .badge-uwezo-outline {
                border: 1px solid var(--uwezo-blue);
                color: var(--uwezo-blue);
                background: transparent;
            }
            .livewire-progress-bar {
                position: fixed;
                top: 47px;
                left: 0;
                right: 0;
                height: 3px;
                box-shadow: 0 1px 2px rgba(23, 162, 184, 0.3);
                width: 0;
                background-color: #27588a;
                z-index: 1039;
                transition: width 300ms ease-out, opacity 150ms;
                opacity: 0;
            }

            .navbar-search-block-open {
                display: flex !important;
                transform: translate3d(0, 0, 0);
                visibility: visible !important;
            }

            /* Adjust the progress bar if the sidebar is collapsed or on mobile if needed */
            @media (max-width: 767.98px) {
                .livewire-progress-bar {
                    top: 47px; /* AdminLTE mobile navbar height is usually the same */
                }
            }
        </style>
    </head>
    <body class="hold-transition sidebar-mini layout-fixed text-sm layout-footer-fixed layout-navbar-fixed ">

            <div class="wrapper">
                @auth
                    <!-- Navbar -->
                    @include('nav-bar')
                    <!-- /.navbar -->

                    <!-- Main Sidebar Container -->
                    <livewire:side-nav />

                    <!-- Content Wrapper. Contains page content -->
                    <div class="content-wrapper">
                        <!-- Main content -->
                        @yield('main-content')


                    </div>
                    <!-- /.content-wrapper -->

                    <!-- Control Sidebar -->
                    @include('rigt-side-bar')
                    <!-- /.control-sidebar -->

                    <!-- Main Footer -->
                    <footer class="main-footer">
                        <!-- To the right -->
                        <div class="float-right d-none d-sm-inline">
                            Uwezo Fund
                        </div>
                        <!-- Default to the left -->
                        <strong>Copyright &copy; 2023-2024 <a href="#">PSC</a>.</strong> All rights reserved.
                    </footer>
                @endauth

            </div>


        <!-- ./wrapper -->

        <!--SCRIPTS -->


        <!-- jQuery -->
        <script src="{{asset('plugins/jquery/jquery.min.js')}}"></script>
        <!-- Bootstrap 4 -->
        <script src="{{asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

        <!-- overlayScrollbars -->
        <script src="{{asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>

        <!-- SweetAlert2 -->
        <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

        {{-- toastr --}}
        <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>

        {{-- select 2 --}}
        <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
        <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true, // Adds a visual countdown
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "2000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
        </script>

        <script src="{{asset('plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
        <script src="{{asset('plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>



        <!-- AdminLTE App -->
        <script src="{{asset('dist/js/adminlte.min.js')}}"></script>
        @stack('scripts')

        @livewire('add-apps-to-phase-modal')

        @livewireScripts

        <script>
            window.progressInterval = window.progressInterval || null;

            document.addEventListener('livewire:navigate', () => {
                const bar = document.getElementById('livewire-main-progress');
                if (!bar) return;

                // Reset and show
                clearInterval(window.progressInterval);
                bar.style.transition = 'width 300ms ease-out, opacity 150ms';
                bar.style.opacity = '1';
                bar.style.width = '10%';

                let width = 10;
                window.progressInterval = setInterval(() => {
                    if (width < 30) {
                        width += 20; // Jump to 30
                    } else if (width < 60) {
                        width += 10; // Move to 60
                    } else if (width < 90) {
                        width += 2;  // Slowly crawl to 90
                    }
                    bar.style.width = width + '%';
                }, 200);
            });

            $(document).off('click', '[data-widget="navbar-search"]').on('click', '[data-widget="navbar-search"]', function (e) {
                e.preventDefault();

                const searchBlock = $('.navbar-search-block');
                const isCloseBtn = $(this).find('.fas.fa-times').length > 0;

                if (isCloseBtn) {
                    searchBlock.removeClass('navbar-search-block-open');
                } else {
                    searchBlock.addClass('navbar-search-block-open');
                    setTimeout(() => {
                        $('#nav-search-input').focus();
                    }, 150);
                }
            });

            function handleGlobalSearch(e) {
                const activeElem = document.activeElement.tagName;
                if (activeElem === 'INPUT' || activeElem === 'TEXTAREA' || activeElem === 'SELECT') return;

                if (e.key === '/') {
                    e.preventDefault();
                    const searchToggle = document.querySelector('[data-widget="navbar-search"]');
                    if (searchToggle) {
                        const searchBlock = document.querySelector('.navbar-search-block');
                        if (searchBlock && !searchBlock.classList.contains('navbar-search-block-open')) {
                            searchToggle.click();
                        }
                        setTimeout(() => {
                            const input = document.getElementById('nav-search-input');
                            if (input) input.focus();
                        }, 100);
                    }
                }

                if (e.key === 'Escape') {
                    const searchBlock = $('.navbar-search-block');
                    if (searchBlock.hasClass('navbar-search-block-open')) {
                        searchBlock.removeClass('navbar-search-block-open');
                    }
                }
            }

            if (window.handleGlobalSearch) {
                document.removeEventListener('keydown', window.handleGlobalSearch);
            }

            document.addEventListener('livewire:navigated', () => {

                const bar = document.getElementById('livewire-main-progress');
                if (!bar) return;

                clearInterval(window.progressInterval);
                bar.style.width = '100%';

                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => {
                        bar.style.transition = 'none';
                        bar.style.width = '0';
                    }, 300);
                }, 250);


                // Re-initialize select2
                if (typeof window.initSelect2 === 'function') {
                    window.initSelect2();
                }

                // Re-initialize dropdown
                if (typeof $ !== 'undefined' && typeof $.fn.dropdown !== 'undefined') {
                    $('[data-toggle="dropdown"]').dropdown();
                }

                // Re-initialize Tooltips
                if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
                    $('[data-toggle="tooltip"]').tooltip();
                }

                $(document).off('click', '[data-card-widget="collapse"]');

                $(document).on('click', '[data-card-widget="collapse"]', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const button = $(this);
                    const card = button.closest('.card');
                    const cardBody = card.find('.card-body');
                    const icon = button.find('i');

                    if (card.hasClass('collapsed-card')) {
                        cardBody.slideDown(300, function() {
                            card.removeClass('collapsed-card');
                            icon.removeClass('fa-plus').addClass('fa-minus');
                        });
                    } else {
                        cardBody.slideUp(300, function() {
                            card.addClass('collapsed-card');
                            icon.removeClass('fa-minus').addClass('fa-plus');
                        });
                    }
                });
            });

            $(document).on('mousedown', function (e) {
                const searchBlock = $('.navbar-search-block');
                const searchToggle = $('[data-widget="navbar-search"]');

                // If the click is NOT on the search block and NOT on the toggle button
                if (!searchBlock.is(e.target) && searchBlock.has(e.target).length === 0 &&
                    !searchToggle.is(e.target) && searchToggle.has(e.target).length === 0) {
                    if (searchBlock.hasClass('navbar-search-block-open')) {
                        searchBlock.removeClass('navbar-search-block-open');
                    }
                }
            });

            document.addEventListener('keydown', window.handleGlobalSearch);

            window.addEventListener('swal:modal', event => {
                const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;

                // Define Toast configuration
                if (data.toast) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: data.type,
                        title: data.title
                    });
                } else {

                    Swal.fire({
                        title: data.title,
                        text: data.text,
                        icon: data.type,
                        theme: 'bootstrap-4',
                        timer: data.timer || null,
                        showConfirmButton: data.showConfirmButton !== undefined ? data.showConfirmButton : true,
                        customClass: {
                            confirmButton: 'btn btn-primary px-4',
                            cancelButton: 'btn btn-danger px-4'
                        },
                        buttonsStyling: false
                    });
                }
            });


        </script>

        <script>
            // Define the function globally
            window.initSelect2 = function() {
                if (!window.jQuery || !window.jQuery.fn.select2) return;

                $('select.select2').each(function () {
                    const el = $(this);
                    if (el.hasClass('select2-hidden-accessible')) return;

                    // Find the property name: check wire:model first, then the ID
                    let propertyName = el.attr('wire:model') || el.attr('wire:model.live');

                    if (!propertyName && el.attr('id')) {
                        propertyName = el.attr('id').replace('select2-', '');
                    }

                    el.select2({
                        // theme: 'bootstrap4',
                        width: '100%',
                        // placeholder: el.data('placeholder') || 'Select an option',
                        allowClear: false,
                    });

                    // Sync visual state for Back Button / URL hydration
                    const currentVal = el.val();
                    if (currentVal) {
                        el.val(currentVal).trigger('change.select2');
                    }

                    el.on('change', function (e) {
                        let component = Livewire.find(el.closest('[wire\\:id]').attr('wire:id'));
                        if (component && propertyName) {
                            // Tells Livewire to update the public property
                            component.set(propertyName, el.val());
                        }
                    });
                });
            };

            // Initialize on first load and after every Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                initSelect2();
            });

            // Re-init after Livewire updates the DOM (for dynamic selects)
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', ({ el }) => {
                    initSelect2();
                });

                Livewire.on('confirm-action', (eventData) => {
                    const data = Array.isArray(eventData) ? eventData[0] : eventData;
                    Swal.fire({
                        title: data.title || "Are you sure?",
                        text: data.text || "This action cannot be undone!",
                        icon: data.icon || "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: data.confirmText || "Yes, proceed!",
                        reverseButtons: true // Places "Cancel" on the left
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // If confirmed, tell Livewire to call the actual function
                            // 'componentId' and 'action' must be passed from the backend
                            Livewire.find(data.componentId).call(data.action, data.params);
                        }
                    });
                });
            });
        </script>

    </body>
</html>
