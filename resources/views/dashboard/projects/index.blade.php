<x-front-layout>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/datatable/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.bootstrap4.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/dataTables.dataTables.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.css') }}">

        <link id="stickyTableLight" rel="stylesheet" href="{{ asset('css/custom2/stickyTable.css') }}">

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
        @can('create', 'App\\Models\Project')
            <div class="mx-2 nav-item">
                <a href="{{ route('dashboard.projects.create') }}" class="m-0 text-white btn btn-primary">
                    <i class="fa-solid fa-plus fe-16"></i> اضافة
                </a>
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
            'project_number' => 'رقم المشروع',
            'name' => 'اسم المشروع',
            'institution_name' => 'المؤسسة',
            'project_type' => 'نوع المشروع',
            'aid_item_name' => 'الصنف',
            'total_display' => 'الإجمالي',
            'consumed_display' => 'المصروف',
            'remaining_display' => 'المتبقي',
            'beneficiaries_total' => 'عدد المستفيدين',
            'beneficiaries_consumed' => 'المستفيدين الحاصلين',
            'beneficiaries_remaining' => 'المتبقي',
            'dependency_display' => 'التبعية',
            'creator_name' => 'المُنشئ',
            'status_display' => 'الحالة',
        ];
    @endphp
    <div class="shadow-lg enhanced-card">
        <div class="enhanced-card-body">
            <div class="col-12" style="padding: 0;">
                <div class="table-container">
                    <table id="projects-table"
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

    @push('scripts')
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

        <script>
            const tableId = 'projects-table';
            const arabicFileJson = "{{ asset('files/Arabic.json') }}";

            const pageLength = $('#advanced-pagination').val();

            const _token = "{{ csrf_token() }}";
            const urlIndex = `{{ route('dashboard.projects.index') }}`;
            const urlFilters = `{{ route('dashboard.projects.filters', ':column') }}`;
            const urlCreate = `{{ route('dashboard.projects.create') }}`;
            const urlStore = `{{ route('dashboard.projects.store') }}`;
            const urlEdit = `{{ route('dashboard.projects.edit', ':id') }}`;
            const urlUpdate = `{{ route('dashboard.projects.update', ':id') }}`;
            const urlDelete = `{{ route('dashboard.projects.destroy', ':id') }}`;

            const abilityCreate = "{{ Auth::user()->can('create', 'App\\Models\\Project') }}";
            // abilityEdit و abilityDelete يُحدّدان لكل صف من السيرفر (can_edit, can_delete)

            const fields = [
                '#',
                'edit',
                'project_number',
                'name',
                'institution_name',
                'project_type',
                'aid_item_name',
                'total_display',
                'consumed_display',
                'remaining_display',
                'beneficiaries_total',
                'beneficiaries_consumed',
                'beneficiaries_remaining',
                'dependency_display',
                'creator_name',
                'status_display',
                'delete'
            ];

            const SUMMABLE_COLUMNS = {
                enabled: false,
                columns: {}
            };

            const columnsTable = [
                {
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
                        if (row.can_edit) {
                            linkedit = `
                            <a href="${urlEdit.replace(':id', data)}"
                                class="action-btn btn-edit"
                                title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
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
                    data: 'project_number',
                    name: 'project_number',
                    orderable: false,
                    class: 'enhanced-sticky text-center'
                },
                {
                    data: 'name',
                    name: 'name',
                    orderable: false,
                    class: 'enhanced-sticky'
                },
                {
                    data: 'institution_name',
                    name: 'institution_name',
                    orderable: false,
                    class: 'enhanced-sticky'
                },
                {
                    data: 'project_type',
                    name: 'project_type',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'aid_item_name',
                    name: 'aid_item_name',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'total_display',
                    name: 'total_display',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'consumed_display',
                    name: 'consumed_display',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'remaining_display',
                    name: 'remaining_display',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'beneficiaries_total',
                    name: 'beneficiaries_total',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'beneficiaries_consumed',
                    name: 'beneficiaries_consumed',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'beneficiaries_remaining',
                    name: 'beneficiaries_remaining',
                    orderable: false,
                    class: 'text-center'
                },
                {
                    data: 'dependency_display',
                    name: 'dependency_display',
                    orderable: false
                },
                {
                    data: 'creator_name',
                    name: 'creator_name',
                    orderable: false
                },
                {
                    data: 'status_display',
                    name: 'status_display',
                    orderable: false,
                    class: 'text-center',
                    render: function(data) {
                        const badge = data === 'فعال' ? 'bg-success' : 'bg-secondary';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                {
                    data: 'delete',
                    name: 'delete',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let linkdelete = '';
                        if (row.can_delete) {
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

            const dataForm = {};
            const columnsCopy = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
            const columnNamesCopy = ['project_number', 'name', 'institution_name', 'project_type', 'aid_item_name', 'total_display', 'consumed_display', 'remaining_display', 'beneficiaries_total', 'beneficiaries_consumed', 'beneficiaries_remaining', 'dependency_display', 'creator_name', 'status_display'];
        </script>
        <script type="text/javascript" src="{{ asset('js/datatable.js') }}"></script>

    @endpush
</x-front-layout>
