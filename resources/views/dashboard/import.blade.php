<x-front-layout>
    @push('styles')
        <style>
            .import-card {
                border: 1px solid #edf2f7;
                border-radius: 14px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, 0.05);
            }
            .import-upload-zone {
                border: 2px dashed #cbd5e1;
                border-radius: 10px;
                background: #f8fafc;
                padding: 1.5rem;
            }
        </style>
    @endpush

    <div class="m-4">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading mb-2">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    حدثت أخطاء أثناء المعالجة
                </h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card import-card overflow-hidden">
            <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="mb-0">استيراد المساعدات من Excel</h5>
                <p class="mb-0 mt-1 small text-muted">ارفع ملف الإكسل وفق قالب الاستيراد ثم اضغط رفع</p>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dashboard.import.excel') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="import-upload-zone mb-4">
                        <label for="import-file" class="form-label fw-medium">اختر الملف</label>
                        <input type="file" name="file" id="import-file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">رفع</button>
                        <a href="{{ asset('filesExcel/templateAidDistribution.xlsx') }}" download="قالب المساعدات.xlsx" class="btn btn-outline-secondary">تحميل نموذج الاستيراد</a>
                    </div>
                </form>
            </div>
        </div>

        @if (session('import_errors'))
            <div class="card import-card overflow-hidden mt-4">
                <div class="card-header bg-danger text-white border-bottom py-3">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-exclamation-circle me-2"></i>
                        أخطاء في الملف - لا يمكن الاستيراد
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset(session('import_errors')['validation_errors']))
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">
                                <i class="fa-solid fa-file-excel me-1"></i>
                                أخطاء في البيانات ({{ count(session('import_errors')['validation_errors']) }} صف):
                            </h6>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>رقم الصف</th>
                                            <th>الأخطاء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_errors')['validation_errors'] as $error)
                                            <tr>
                                                <td class="text-center"><strong>{{ $error['row'] }}</strong></td>
                                                <td>
                                                    <ul class="mb-0">
                                                        @foreach($error['errors'] as $errorMsg)
                                                            <li>{{ $errorMsg }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @if(isset(session('import_errors')['project_constraints']))
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                تجاوز قيود المشاريع:
                            </h6>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>رقم المشروع</th>
                                            <th>اسم المشروع</th>
                                            <th>المكتب</th>
                                            <th>النوع</th>
                                            <th>المطلوب</th>
                                            <th>المتاح</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_errors')['project_constraints'] as $constraint)
                                            @php
                                                $isOfficeMessageOnly = in_array($constraint['type'] ?? '', ['office_not_allowed', 'office_no_allocation', 'office_missing_limit']);
                                            @endphp
                                            <tr>
                                                <td>{{ $constraint['project_number'] ?? '-' }}</td>
                                                <td>{{ $constraint['project_name'] ?? '-' }}</td>
                                                <td>{{ $constraint['office_name'] ?? '-' }}</td>
                                                <td>
                                                    @if($constraint['type'] === 'beneficiaries')
                                                        مستفيدين
                                                    @elseif($constraint['type'] === 'cash_amount')
                                                        مبلغ نقدي
                                                    @elseif($constraint['type'] === 'quantity')
                                                        كمية
                                                    @elseif($constraint['type'] === 'office_beneficiaries')
                                                        مستفيدين (حصة المكتب)
                                                    @elseif($constraint['type'] === 'office_cash_amount')
                                                        مبلغ نقدي (حصة المكتب)
                                                    @elseif($constraint['type'] === 'office_quantity')
                                                        كمية (حصة المكتب)
                                                    @elseif($isOfficeMessageOnly)
                                                        {{ $constraint['type'] === 'office_not_allowed' ? 'غير مسموح' : ($constraint['type'] === 'office_no_allocation' ? 'بدون حصة' : 'حد غير محدد') }}
                                                    @else
                                                        {{ $constraint['type'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td class="text-danger">
                                                    @if($isOfficeMessageOnly && !empty($constraint['message']))
                                                        <span class="small text-muted">-</span>
                                                    @elseif(isset($constraint['requested']))
                                                        <strong>{{ is_numeric($constraint['requested']) ? number_format($constraint['requested'], 2) : $constraint['requested'] }}</strong>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-success">
                                                    @if($isOfficeMessageOnly && !empty($constraint['message']))
                                                        <span class="small">{{ $constraint['message'] }}</span>
                                                    @elseif(isset($constraint['available']))
                                                        {{ is_numeric($constraint['available']) ? number_format($constraint['available'], 2) : $constraint['available'] }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if(isset($batch) && $batch)
            <div class="card import-card overflow-hidden mt-4">
                <div class="card-header bg-warning border-bottom py-3">
                    <h6 class="mb-0">سجلات مكررة تحتاج موافقتك ({{ $batch->duplicate_rows }} سجل)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الاسم</th>
                                    <th>رقم الهوية</th>
                                    <th>تاريخ الصرف</th>
                                    <th>نوع التكرار</th>
                                    <th>تفاصيل</th>
                                    <th>القرار</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batch->rows as $row)
                                    <tr>
                                        <td>{{ $row->payload['full_name'] ?? '-' }}</td>
                                        <td>{{ $row->payload['national_id'] ?? '-' }}</td>
                                        <td>{{ $row->payload['distributed_at'] ?? '-' }}</td>
                                        <td>
                                            @if($row->duplicate_in_file)
                                                <span class="badge bg-warning">مكرر في الملف</span>
                                            @endif
                                            @if($row->duplicate_in_db)
                                                <span class="badge bg-danger">مكرر في قاعدة البيانات</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row->duplicate_details)
                                                @if(isset($row->duplicate_details['details']))
                                                    المكتب: {{ $row->duplicate_details['details']['office_name'] ?? '-' }}<br>
                                                    التاريخ: {{ $row->duplicate_details['details']['distributed_at'] ?? '-' }}
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" 
                                                    class="btn btn-success decision-btn" 
                                                    data-row-id="{{ $row->id }}"
                                                    data-decision="approved"
                                                    @if($row->decision === 'approved') disabled @endif>
                                                    <i class="fa-solid fa-check"></i> موافقة
                                                </button>
                                                <button type="button" 
                                                    class="btn btn-danger decision-btn" 
                                                    data-row-id="{{ $row->id }}"
                                                    data-decision="rejected"
                                                    @if($row->decision === 'rejected') disabled @endif>
                                                    <i class="fa-solid fa-times"></i> رفض
                                                </button>
                                            </div>
                                            @if($row->decision === 'approved')
                                                <span class="badge bg-success mt-1">تمت الموافقة</span>
                                            @elseif($row->decision === 'rejected')
                                                <span class="badge bg-secondary mt-1">مرفوض</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('dashboard.import.finalize', $batch->uuid) }}" id="finalize-form">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check-circle me-1"></i>
                            تنفيذ الاستيراد
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if (session('constraint_errors'))
            <div class="card import-card overflow-hidden mt-4">
                <div class="card-header bg-danger text-white border-bottom py-3">
                    <h6 class="mb-0">تجاوز قيود المشاريع</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach(session('constraint_errors') as $constraint)
                            <li class="mb-2">
                                <strong>المشروع:</strong> {{ $constraint['project_number'] ?? '-' }} - {{ $constraint['project_name'] ?? '-' }}
                                @if(!empty($constraint['office_name']))
                                    <br><strong>المكتب:</strong> {{ $constraint['office_name'] }}
                                @endif
                                <br><strong>النوع:</strong>
                                @if($constraint['type'] === 'beneficiaries')
                                    مستفيدين
                                @elseif($constraint['type'] === 'cash_amount')
                                    مبلغ نقدي
                                @elseif($constraint['type'] === 'quantity')
                                    كمية
                                @elseif(in_array($constraint['type'] ?? '', ['office_beneficiaries', 'office_cash_amount', 'office_quantity']))
                                    {{ $constraint['type'] === 'office_beneficiaries' ? 'مستفيدين (حصة المكتب)' : ($constraint['type'] === 'office_cash_amount' ? 'مبلغ نقدي (حصة المكتب)' : 'كمية (حصة المكتب)') }}
                                @else
                                    {{ $constraint['type'] ?? '-' }}
                                @endif
                                @if(!empty($constraint['message']))
                                    <br><span class="text-muted">{{ $constraint['message'] }}</span>
                                @elseif(isset($constraint['requested']) || isset($constraint['available']))
                                    <br>المطلوب: {{ $constraint['requested'] ?? '-' }} | المتاح: {{ $constraint['available'] ?? '-' }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.decision-btn').on('click', function() {
                    const $btn = $(this);
                    const rowId = $btn.data('row-id');
                    const decision = $btn.data('decision');
                    const batchUuid = '{{ isset($batch) ? $batch->uuid : '' }}';

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: `/import-excel/update-decision/${batchUuid}`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            row_id: rowId,
                            decision: decision
                        },
                        success: function() {
                            const $row = $btn.closest('tr');
                            $row.find('.decision-btn').prop('disabled', false);
                            $btn.prop('disabled', true);
                            
                            const badge = decision === 'approved' 
                                ? '<span class="badge bg-success mt-1">تمت الموافقة</span>'
                                : '<span class="badge bg-secondary mt-1">مرفوض</span>';
                            $row.find('td:last-child .badge').remove();
                            $row.find('td:last-child').append(badge);

                            toastr.success('تم تحديث القرار');
                        },
                        error: function() {
                            $btn.prop('disabled', false);
                            toastr.error('فشل تحديث القرار');
                        }
                    });
                });
            });
        </script>
    @endpush
</x-front-layout>
