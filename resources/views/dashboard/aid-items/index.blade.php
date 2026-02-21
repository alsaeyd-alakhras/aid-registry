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
    @endpush
    <x-slot:extra_nav>
        <div class="nav-item">
            <select class="form-control" name="advanced-pagination" id="advanced-pagination">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="-1">all</option>
            </select>
        </div>
        @can('export-excel', 'App\\Models\AidItem')
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
        @can('create', 'App\\Models\AidItem')
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
            'name' => 'اسم نوع المساعدة',
            'estimated_value' => 'القيمة التقديرية',
            'description' => 'الوصف',
            'is_active_badge' => 'الحالة',
        ];
    @endphp
    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="aid-items-table"
                        class="table enhanced-sticky table-striped table-hover"style="display: table; width:100%; height: auto;">
                        <thead>
                            <tr>
                                <th class="text-center enhanced-sticky">#</th>
                                @foreach ($fields as $index => $label)
                                    <th class="{{ $loop->index < 4 ? 'enhanced-sticky' : '' }}">
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
        <div class="modal-dialog modal-lg modal-dialog-centerd" role="document">
            <div class="modal-content">
                <form id="editForm">
                    @include('dashboard.aid-items.editModal')
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
            const tableId = 'aid-items-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";

            const pageLength = $('#advanced-pagination').val();

            // urls
            const _token = "{{ csrf_token() }}";
            const urlIndex = `{{ route('dashboard.aid-items.index') }}`;
            const urlFilters = `{{ route('dashboard.aid-items.filters', ':column') }}`;
            const urlCreate = `{{ route('dashboard.aid-items.create') }}`;
            const urlStore = `{{ route('dashboard.aid-items.store') }}`;
            const urlEdit = `{{ route('dashboard.aid-items.edit', ':id') }}`;
            const urlUpdate = `{{ route('dashboard.aid-items.update', ':id') }}`;
            const urlDelete = `{{ route('dashboard.aid-items.destroy', ':id') }}`;

            // ability
            const abilityCreate = "{{ Auth::user()->can('create', 'App\\Models\\AidItem') }}";
            const abilityEdit = "{{ Auth::user()->can('update', 'App\\Models\\AidItem') }}";
            const abilityDelete = "{{ Auth::user()->can('delete', 'App\\Models\\AidItem') }}";

            const fields = [
                '#',
                'edit',
                'name',
                'estimated_value',
                'description',
                'is_active_badge',
                'delete'
            ];
            const SUMMABLE_COLUMNS = {
                // تفعيل/إلغاء مجاميع tfoot
                enabled: false,
                columns: {}
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
                },
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
                        return `
                        <div class="d-flex align-items-center justify-content-evenly">
                            ${linkedit}
                        </div>
                    `;
                    }
                },
                {
                    data: 'name',
                    name: 'name',
                    orderable: false,
                    class: 'enhanced-sticky'
                },
                {
                    data: 'estimated_value_formatted',
                    name: 'estimated_value',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'description',
                    name: 'description',
                    orderable: false
                },
                {
                    data: 'is_active_badge',
                    name: 'is_active_badge',
                    orderable: false,
                    class: 'text-center'
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
                name: '',
                estimated_value: '',
                description: '',
                is_active: '',
            }
            const columnsCopy = [1, 2, 3, 4, 5];
            const columnNamesCopy = ['name', 'estimated_value', 'description', 'is_active'];
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>

    @endpush
</x-front-layout>
