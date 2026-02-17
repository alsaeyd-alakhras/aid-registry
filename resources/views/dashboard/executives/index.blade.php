<x-front-layout>
    @push('styles')
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="{{ asset('css/datatable/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.bootstrap4.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.dataTables.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.css') }}">

        {{-- sticky table --}}
        <link id="stickyTableLight" rel="stylesheet" href="{{ asset('css/custom2/stickyTable.css') }}">

        {{-- custom css --}}
        <link rel="stylesheet" href="{{ asset('css/custom2/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom2/datatableIndex2.css') }}">
        <style>
            :root {
                --sticky-col1-width: 60px;
                --sticky-col2-width: 90px;
                --sticky-col3-width: 90px;
                --sticky-col4-width: 120px;
                --sticky-col5-width: 120px;
                --sticky-col2-right: var(--sticky-col1-width);
                --sticky-col3-right: calc(var(--sticky-col1-width) + var(--sticky-col2-width));
                --sticky-col4-right: calc(var(--sticky-col1-width) + var(--sticky-col2-width) + var(--sticky-col3-width));
                --sticky-col5-right: calc(var(--sticky-col1-width) + var(--sticky-col2-width) + var(--sticky-col3-width) + var(--sticky-col4-width));
            }

            th.enhanced-sticky:nth-child(1), td.enhanced-sticky:nth-child(1) {
                right: 0;
                width: var(--sticky-col1-width);
                min-width: var(--sticky-col1-width);
            }

            th.enhanced-sticky:nth-child(2), td.enhanced-sticky:nth-child(2) {
                right: var(--sticky-col2-right);
                width: var(--sticky-col2-width);
                min-width: var(--sticky-col2-width);
            }

            th.enhanced-sticky:nth-child(3), td.enhanced-sticky:nth-child(3) {
                right: var(--sticky-col3-right);
                width: var(--sticky-col3-width);
                min-width: var(--sticky-col3-width);
            }

            th.enhanced-sticky:nth-child(4), td.enhanced-sticky:nth-child(4) {
                right: var(--sticky-col4-right);
                width: var(--sticky-col4-width);
                min-width: var(--sticky-col4-width);
            }

            th.enhanced-sticky:nth-child(5), td.enhanced-sticky:nth-child(5) {
                right: var(--sticky-col5-right);
                width: var(--sticky-col5-width);
                min-width: var(--sticky-col5-width);
            }
        </style>
    @endpush
    <x-slot:extra_nav>
        <div class="mx-2 my-0 nav-item form-group">
            <select name="year" id="year" class="form-control">
                @for ($year = date('Y'); $year >= 2023; $year--)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endfor
            </select>
        </div>
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="250">250</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="5000">5000</option>
                <option value="-1">all</option>
            </select>
        </div>
        @can('copy', 'App\\Models\Executive')
        <div class="nav-item">
            <button type="button" class="mx-1 text-white btn btn-icon btn-info" id="copy-export" title="نسخ">
                <i class="fa-solid fa-copy fe-16"></i>
            </button>
        </div>
        @endcan
        @can('export-excel', 'App\\Models\Executive')
        {{-- excel export --}}
        <div class="mx-2 nav-item">
            <button type="button" class="text-white btn btn-icon btn-success" id="excel-export" title="تصدير excel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="16" height="16">
                    <path
                        d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM155.7 250.2L192 302.1l36.3-51.9c7.6-10.9 22.6-13.5 33.4-5.9s13.5 22.6 5.9 33.4L221.3 344l46.4 66.2c7.6 10.9 5 25.8-5.9 33.4s-25.8 5-33.4-5.9L192 385.8l-36.3 51.9c-7.6 10.9-22.6 13.5-33.4 5.9s-13.5-22.6-5.9-33.4L162.7 344l-46.4-66.2c-7.6-10.9-5-25.8 5.9-33.4s25.8-5 33.4 5.9z" />
                </svg>
            </button>
        </div>
        @endcan
        @can('create', 'App\\Models\Executive')
            <div class="mx-2 nav-item">
                <button type="button" class="m-0 text-white btn btn-primary" id="createNew">
                    <i class="fa-solid fa-plus fe-16"></i> اضافة
                </button>
            </div>
        @endcan
        <div class="mx-2 nav-item">
            <button class="p-2 border-0 btn btn-outline-danger rounded-pill me-n1 waves-effect waves-light d-none"
                type="button" id="filterBtnClear" title="إزالة التصفية">
                <i class="fa-solid fa-eraser fe-16"></i>
            </button>
        </div>
        <div class="mx-2 nav-item d-flex align-items-center justify-content-center">
            <button type="button" class="btn" id="refreshData">
                <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </div>
    </x-slot:extra_nav>
    @php
        $fields = [
            'edit' => 'تعديل',
            'budget_number' => 'رقم الموازنة',
            'implementation_date' => 'تاريخ',
            'broker_name' => 'المؤسسة',
            'account' => 'الحساب',
            'affiliate_name' => 'الاسم',
            'field' => 'المجال',
            'project_name' => 'المشروع',
            'detail' => 'التفصيل',
            'item_name' => 'الصنف',
            'quantity' => 'الكمية',
            'price' => 'السعر',
            'total_ils' => 'الإجمالي',
            'received' => 'المستلم',
            'notes' => 'الملاحظات',
            'executive_status' => 'حالة التنفيذ',
            'amount_payments' => 'ملبغ',
            'payment_mechanism' => 'آليةالدفع',
            'user_name' => 'اسم المستخدم',
            'manager_name' => 'المدير المعتمد',
        ];
    @endphp
    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="executives-table"
                        class="table enhanced-sticky table-striped table-hover"style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th class="{{ $loop->index < 4 ? 'enhanced-sticky' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            @if ($index == 'budget_number')
                                                <span style="text-wrap-mode: wrap;text-align: right;font-size: 13px;">{{ $label }}</span>
                                            @else
                                                <span>{{ $label }}</span>
                                            @endif
                                            <div class="enhanced-filter-dropdown">
                                                <div class="dropdown">
                                                    <button class="enhanced-btn-filter btn-filter" type="button"
                                                        data-bs-toggle="dropdown"
                                                        id="btn-filter-{{ $loop->index + 1 }}">
                                                        <i class="fas fa-filter"></i>
                                                    </button>
                                                    <div class="dropdown-menu enhanced-filter-menu filterDropdownMenu"
                                                        aria-labelledby="{{ $index }}_filter">

                                                        @if ($index == 'implementation_date')
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">من
                                                                    تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="from_date"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted small">إلى
                                                                    تاريخ:</label>
                                                                <input type="date"
                                                                    class="form-control form-control-sm" id="to_date"
                                                                    data-column="{{ $loop->index + 1 }}">
                                                            </div>
                                                            <div class="gap-2 d-flex">
                                                                <button class="enhanced-apply-btn flex-fill"
                                                                    id="filter-date-btn">
                                                                    <i class="fas fa-check me-1"></i>
                                                                    تطبيق
                                                                </button>
                                                                <button
                                                                    class="btn btn-outline-secondary btn-sm flex-fill"
                                                                    id="filter-date-btn">
                                                                    <i class="fas fa-times me-1"></i>
                                                                    مسح
                                                                </button>
                                                            </div>
                                                        @else
                                                            <!-- فلتر الـ checkboxes العادي -->
                                                            <div
                                                                class="mb-3 d-flex justify-content-between align-items-center">
                                                                <input type="search"
                                                                    class="form-control search-checkbox"
                                                                    placeholder="ابحث..."
                                                                    data-index="{{ $loop->index + 1 }}">
                                                                <button
                                                                    class="enhanced-apply-btn ms-2 filter-apply-btn-checkbox"
                                                                    data-target="{{ $loop->index + 1 }}"
                                                                    data-field="{{ $index }}">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </div>
                                                            <div class="enhanced-checkbox-list checkbox-list-box">
                                                                <label style="display: block;">
                                                                    <input type="checkbox" value="all"
                                                                        class="all-checkbox"
                                                                        data-index="{{ $loop->index + 1 }}"> الكل
                                                                </label>
                                                                <div
                                                                    class="checkbox-list checkbox-list-{{ $loop->index + 1 }}">
                                                                </div>
                                                            </div>
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="enhanced-sticky">العمليات</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td class="text-right enhanced-sticky">الإجمالي</td>
                                @foreach ($fields as $key => $label)
                                    <td class="text-center {{ $loop->index < 4 ? 'enhanced-sticky' : '' }}" id="tfoot-{{ $key }}">
                                        @if(in_array($key, ['quantity', 'price', 'total_ils', 'amount_payments']))
                                            0
                                        @endif
                                    </td>
                                @endforeach
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade delete-modal" id="deleteConfirmModal" tabindex="-1"
        aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        تأكيد الحذف
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="delete-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="delete-warning-text">هل أنت متأكد؟</div>
                    <p class="delete-sub-text">
                        لن تتمكن من التراجع عن هذا الإجراء بعد الحذف!
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        إلغاء
                    </button>
                    <button type="button" class="text-white btn btn-confirm-delete" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>
                        حذف نهائي
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" role="document">
            <div class="modal-content">
                <form id="editForm">
                    @include('dashboard.executives.editModal')
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <!-- DataTables JS -->
        <script src="{{ asset('js/plugins/jquery.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/dataTables.buttons.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/buttons.dataTables.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/jszip.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/pdfmake.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/vfs_fonts.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('js/plugins/datatable/buttons.print.min.js') }}"></script>
        <script src="{{ asset('js/plugins/jquery.validate.min.js') }}"></script>

        {{-- script --}}
        <script>
            const tableId = 'executives-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";

            const pageLength = $('#advanced-pagination').val();

            // urls
            const _token = "{{ csrf_token() }}";
            const urlIndex = `{{ route('dashboard.executives.index') }}`;
            const urlFilters = `{{ route('dashboard.executives.filters', ':column') }}`;
            const urlCreate = `{{ route('dashboard.executives.create') }}`;
            const urlStore = `{{ route('dashboard.executives.store') }}`;
            const urlEdit = `{{ route('dashboard.executives.edit', ':id') }}`;
            const urlUpdate = `{{ route('dashboard.executives.update', ':id') }}`;
            const urlDelete = `{{ route('dashboard.executives.destroy', ':id') }}`;

            // ability
            const abilityCreate = "{{ Auth::user()->can('create', 'App\\Models\\Executive') }}";
            const abilityEdit = "{{ Auth::user()->can('update', 'App\\Models\\Executive') }}";
            const abilityDelete = "{{ Auth::user()->can('delete', 'App\\Models\\Executive') }}";

            const fields = [
                '#',
                'edit',
                'budget_number',
                'implementation_date',
                'broker_name',
                'account',
                'affiliate_name',
                'field',
                'project_name',
                'detail',
                'item_name',
                'quantity',
                'price',
                'total_ils',
                'received',
                'notes',
                'executive_status',
                'amount_payments',
                'payment_mechanism',
                'user_name',
                'manager_name',
                'delete'
            ];
            const SUMMABLE_COLUMNS = {
                // تفعيل/إلغاء مجاميع tfoot
                enabled: true,

                // تحديد الأعمدة التي نريد حساب مجاميعها
                columns: {
                    'quantity': {
                        type: 'sum',
                        format: 'number'
                    },
                    'price': {
                        type: 'sum',
                        format: 'number'
                    },
                    'total_ils': {
                        type: 'sum',
                        format: 'currency'
                    },
                    'amount_payments': {
                        type: 'sum',
                        format: 'currency'
                    }
                }
            };
            let formatNumber = (number, min = 0) => {
                // التحقق إذا كانت القيمة فارغة أو غير صالحة كرقم
                if (number === null || number === undefined || isNaN(number)) {
                    return ""; // إرجاع قيمة فارغة إذا كان الرقم غير صالح
                }
                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: min,
                    maximumFractionDigits: 2,
                }).format(number);
            };
            const columnsTable = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    class: 'enhanced-sticky text-center'
                }, // عمود الترقيم التلقائي
                {
                    data: 'edit',
                    name: 'edit',
                    orderable: false,
                    class: 'enhanced-sticky',
                    searchable: false,
                    render: function(data, type, row) {
                        let linkedit = ``;
                        if (abilityEdit) {
                            linkedit = `
                            <button data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="${data}"
                                class="action-btn btn-edit edit_row"
                                title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                        `;
                        }
                        let checkbox = `<input type="checkbox" class="select_row form-check-input" name="id[]" value="${data}">`;
                        return `
                        <div class="d-flex align-items-center justify-content-evenly">
                            ${linkedit}
                            ${checkbox}
                        </div>
                    `;
                    }
                },
                {
                    data: 'budget_number',
                    name: 'budget_number',
                    orderable: false,
                    class: 'enhanced-sticky text-center'
                },
                {
                    data: 'implementation_date',
                    name: 'implementation_date',
                    orderable: false,
                    class: 'enhanced-sticky text-center'
                },
                {
                    data: 'broker_name',
                    name: 'broker_name',
                    orderable: false,
                    class: 'enhanced-sticky'
                },
                {
                    data: 'account',
                    name: 'account',
                    orderable: false,
                },
                {
                    data: 'affiliate_name',
                    name: 'affiliate_name',
                    orderable: false
                },
                {
                    data: 'field',
                    name: 'field',
                    orderable: false
                },
                {
                    data: 'project_name',
                    name: 'project_name',
                    orderable: false
                },
                {
                    data: 'detail',
                    name: 'detail',
                    orderable: false
                },
                {
                    data: 'item_name',
                    name: 'item_name',
                    orderable: false
                },
                {
                    data: 'quantity',
                    name: 'quantity',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'price',
                    name: 'price',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'total_ils',
                    name: 'total_ils',
                    orderable: false,
                    class: 'text-center',
                    render: function(data, type, row) {
                        return formatNumber(data, 2);
                    }
                },
                {
                    data: 'received',
                    name: 'received',
                    orderable: false
                },
                {
                    data: 'notes',
                    name: 'notes',
                    orderable: false,
                },
                {
                    data: 'executive_status',
                    name: 'executive_status',
                    orderable: false,
                    class: 'text-center',
                    render: function(data, type, row) {
                        // 'implementation','receipt','exchange'
                        return data == 'implementation' ? 'تنفيذ' : data == 'receipt' ? 'قبض' : 'صرف';
                    }
                },
                {
                    data: 'amount_payments',
                    name: 'amount_payments',
                    orderable: false,
                    class: 'text-center',
                    render: function(data, type, row) {
                        return formatNumber(data, 2);
                    }
                },
                {
                    data: 'payment_mechanism',
                    name: 'payment_mechanism',
                    orderable: false
                },
                {
                    data: 'user_name',
                    name: 'user_name',
                    orderable: false,
                },
                {
                    data: 'manager_name',
                    name: 'manager_name',
                    orderable: false,
                },
                {
                    data: 'delete',
                    name: 'delete',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let linkdelete = '';
                        if (abilityDelete) {
                            linkdelete = `
                            <button class="action-btn btn-delete delete_row"
                                    data-id="${data}"
                                    title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        }
                        return `
                        <div class="d-flex align-items-center justify-content-evenly">
                            ${linkdelete}
                        </div>
                    `;
                    }
                }
            ];
            const dataForm = {
                id: '',
                implementation_date: '',
                month: '',
                budget_number: '',
                broker_name: '',
                account: '',
                affiliate_name: '',
                field: '',
                project_name: '',
                detail: '',
                item_name: '',
                quantity: '',
                price: '',
                total_ils: '',
                received: '',
                notes: '',
                amount_payments: '',
                payment_mechanism: '',
                payment_status: '',
                executive_status: '',
                user_id: '',
                user_name: '',
                manager_name: '',
                // files: '',
                user : '',
            }
            const columnsCopy = [1, 2, 3, 4, 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21];
            const columnNamesCopy = [ 'implementation_date', 'month', 'budget_number', 'broker_name', 'account', 'affiliate_name', 'field', 'project_name', 'item_name', 'executive_status', 'quantity', 'price', 'total_ils', 'received', 'notes', 'amount_payments', 'payment_mechanism' ];
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
        <script>
            // تعيين متغيرات CSS ديناميكياً
            function setStickyColumnWidths() {
                const columnWidths = {
                    col1: Math.max(30, Math.min(40, window.innerWidth * 0.05)),
                    col2: Math.max(80, Math.min(90, window.innerWidth * 0.12)),
                    col3: Math.max(80, Math.min(80, window.innerWidth * 0.08)),
                    col4: Math.max(80, Math.min(90, window.innerWidth * 0.10)),
                    col5: Math.max(130, Math.min(170, window.innerWidth * 0.10)),
                };

                document.documentElement.style.setProperty('--sticky-col1-width', columnWidths.col1 + 'px');
                document.documentElement.style.setProperty('--sticky-col2-width', columnWidths.col2 + 'px');
                document.documentElement.style.setProperty('--sticky-col3-width', columnWidths.col3 + 'px');
                document.documentElement.style.setProperty('--sticky-col4-width', columnWidths.col4 + 'px');
                document.documentElement.style.setProperty('--sticky-col5-width', columnWidths.col5 + 'px');

                document.documentElement.style.setProperty('--sticky-col2-right', columnWidths.col1 + 'px');
                document.documentElement.style.setProperty('--sticky-col3-right', (columnWidths.col1 + columnWidths.col2) + 'px');
                document.documentElement.style.setProperty('--sticky-col4-right', (columnWidths.col1 + columnWidths.col2 + columnWidths.col3) + 'px');
                document.documentElement.style.setProperty('--sticky-col5-right', (columnWidths.col1 + columnWidths.col2 + columnWidths.col3 + columnWidths.col4) + 'px');
            }

            // استدعاء عند تحميل الصفحة وتغيير حجم الشاشة
            $(document).ready(setStickyColumnWidths);
            $(window).resize(setStickyColumnWidths);
        </script>
    @endpush
</x-front-layout>
