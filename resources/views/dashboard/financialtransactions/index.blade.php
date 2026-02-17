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
            .btn-icon {
                padding: 5px !important;
            }

            .btn-success {
                color: #fff !important;
                background-color: #28c76f !important;
                border-color: #28c76f !important;
            }

            .container-xxl.flex-grow-1.container-p-y {
                padding: 0 !important;
                padding-top: 3.8 rem;
            }

            tbody td {
                padding: 2px !important;
                font-size: 14px !important;
            }
        </style>
        <style>
            :root {
                --sticky-col1-width: 60px;
                --sticky-col2-width: 90px;
                --sticky-col3-width: 100px;
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
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="100" selected>100</option>
                <option value="250">250</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="-1">all</option>
            </select>
        </div>
        @can('create', 'App\\Models\MerchantPayment')
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
            'transaction_date' => 'التاريخ',
            'project' => 'المشروع',
            'field' => 'المجال',
            'funder' => 'الممول',
            'budget_number' => 'رقم الموازنة',
            'account_name' => 'الحساب',
            'name' => 'الاسم',
            'description' => 'البيان',
            'association' => 'جمعية',
            'account_type' => 'نوع الحساب',
            'amount' => 'المبلغ',
            'currency' => 'العملة',
            'fund' => 'صندوق',
            'exchange_rate' => 'سعر تحويل',
            'debit_shekel' => 'مدين ش',
            'credit_shekel' => 'دائن ش',
            'debit_dollar' => 'مدين دولار',
            'credit_dollar' => 'دائن دولار',
            'merchant' => 'التاجر',
        ];
    @endphp
    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="financialtransactions-table"
                        class="table enhanced-sticky table-striped table-hover"style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th class="text-center {{ $loop->index < 4 ? 'enhanced-sticky' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $label }}</span>
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
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td class="text-right">الإجمالي</td>
                                @foreach ($fields as $key => $label)
                                    <td class="text-center" id="tfoot-{{ $key }}">
                                        @if(in_array($key, ['amount','debit_shekel','credit_shekel','debit_dollar','credit_dollar']))
                                            0
                                        @endif
                                    </td>
                                @endforeach
                                <td></td>
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
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        {{-- <div class="modal-dialog modal-fullscreen" role="document"> --}}
            <div class="modal-content">
                <form id="editForm">
                    @include('dashboard.financialtransactions.editModal')
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
            const tableId = 'financialtransactions-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";

            let pageLength = $('#advanced-pagination').val() || 100;

            // urls
            const _token = "{{ csrf_token() }}";
            const urlIndex = `{{ route('dashboard.financialtransactions.index') }}`;
            const urlFilters = `{{ route('dashboard.financialtransactions.filters', ':column') }}`;
            const urlCreate = `{{ route('dashboard.financialtransactions.create') }}`;
            const urlStore = `{{ route('dashboard.financialtransactions.store') }}`;
            const urlEdit = `{{ route('dashboard.financialtransactions.edit', ':id') }}`;
            const urlUpdate = `{{ route('dashboard.financialtransactions.update', ':id') }}`;
            const urlDelete = `{{ route('dashboard.financialtransactions.destroy', ':id') }}`;

            // ability
            const abilityCreate = "{{ Auth::user()->can('create', 'App\\Models\\FinancialTransaction') }}";
            const abilityEdit = "{{ Auth::user()->can('update', 'App\\Models\\FinancialTransaction') }}";
            const abilityDelete = "{{ Auth::user()->can('delete', 'App\\Models\\FinancialTransaction') }}";

            const fields = [
                '#',
                'edit',
                'transaction_date',
                'project',
                'field',
                'funder',
                'budget_number',
                'account_name',
                'name',
                'description',
                'association',
                'account_type',
                'amount',
                'currency',
                'fund',
                'exchange_rate',
                'debit_shekel',
                'credit_shekel',
                'debit_dollar',
                'credit_dollar',
                'merchant',
                'delete'
            ];
            let formatNumber = (number, min = 0, max = 2) => {
                if (number === null || number === undefined || isNaN(number)) {
                    return "";
                }

                // تأكد إن max أكبر من أو يساوي min
                let maxDigits = Math.max(max, min);

                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: min,
                    maximumFractionDigits: maxDigits,
                }).format(number);
            };
            const SUMMABLE_COLUMNS = {
                // تفعيل/إلغاء مجاميع tfoot
                enabled: true,

                // تحديد الأعمدة التي نريد حساب مجاميعها
                columns: {
                    'amount': {
                        type: 'sum',
                        format: 'number'
                    },
                    'debit_shekel': {
                        type: 'sum',
                        format: 'number'
                    },
                    'credit_shekel': {
                        type: 'sum',
                        format: 'currency'
                    },
                    'debit_dollar': {
                        type: 'sum',
                        format: 'currency'
                    },
                    'credit_dollar': {
                        type: 'sum',
                        format: 'currency'
                    }
                }
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
                    class: 'enhanced-sticky text-center',
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
                        return `
                        <div class="d-flex align-items-center justify-content-evenly">
                            ${linkedit}
                        </div>
                    `;
                    }
                },
                { data: 'transaction_date', name: 'transaction_date', orderable: false, class: 'text-center' },
                { data: 'project', name: 'project', orderable: false },
                { data: 'field', name: 'field', orderable: false, class: 'text-center' },
                { data: 'funder', name: 'funder', orderable: false },
                { data: 'budget_number', name: 'budget_number', orderable: false, class: 'text-center' },
                { data: 'account_name', name: 'account_name', orderable: false },
                { data: 'name', name: 'name', orderable: false },
                { data: 'description', name: 'description', orderable: false },
                { data: 'association', name: 'association', orderable: false, class: 'text-center' },
                { data: 'account_type', name: 'account_type', orderable: false, class: 'text-center', render: function(data, type, row) {
                    let badgeClass = '';
                    switch (row.account_type) {
                        case 'دائن':
                            badgeClass = 'success';
                            break;
                        case 'مدين':
                            badgeClass = 'warning';
                            break;
                        case 'صرف':
                            badgeClass = 'danger';
                            break;
                        case 'قبض':
                            badgeClass = 'info';
                            break;
                        default:
                            badgeClass = 'secondary';
                    }
                    return '<span class="badge bg-' + badgeClass + '">' + row.account_type + '</span>';
                }},
                { data: 'amount', name: 'amount', orderable: false, class: 'text-center', render: function(data, type, row) {
                    return formatNumber(data, 2);
                }},
                { data: 'currency', name: 'currency', orderable: false, class: 'text-center',
                    render: function(data, type, row) {
                        let icon = '';
                        switch (row.currency) {
                            case 'شيكل':
                                icon = '₪';
                                break;
                            case 'دولار':
                                icon = '$';
                                break;
                            case 'دينار':
                                icon = 'JD';
                                break;
                            default:
                                icon = '';
                        }
                        return icon + ' ' + row.currency;
                    }
                },
                { data: 'fund', name: 'fund', orderable: false, class: 'text-center' },
                { data: 'exchange_rate', name: 'exchange_rate', orderable: false, class: 'text-center' },
                { data: 'debit_shekel', name: 'debit_shekel', orderable: false, class: 'text-center', render: function(data, type, row) {
                    return formatNumber(data, 2);
                }},
                { data: 'credit_shekel', name: 'credit_shekel', orderable: false, class: 'text-center', render: function(data, type, row) {
                    return formatNumber(data, 2);
                }},
                { data: 'debit_dollar', name: 'debit_dollar', orderable: false, class: 'text-center', render: function(data, type, row) {
                    return formatNumber(data, 2);
                }},
                { data: 'credit_dollar', name: 'credit_dollar', orderable: false, class: 'text-center', render: function(data, type, row) {
                    return formatNumber(data, 2);
                }},
                {data: 'merchant',name: 'merchant',orderable: false},
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
                entry_number: '',
                transaction_date: `{{ Carbon\Carbon::now()->format('Y-m-d') }}`,
                project: '',
                field: '',
                funder: '',
                budget_number: '',
                account_name: '',
                name: '',
                description: '',
                association: '',
                account_type: '',
                amount: '',
                currency: '',
                currency_value: 3.6,
                fund: '',
                exchange_rate: '',
                debit_shekel: '',
                credit_shekel: '',
                debit_dollar: '',
                credit_dollar: '',
                merchant_id: '',
                notes: ''
            }
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>
    @endpush
</x-front-layout>
