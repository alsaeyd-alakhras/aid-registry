@php
    $currentUser = auth()->user();
    $isEmployee = $currentUser?->user_type == 'employee';
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">بيانات المشروع الأساسية</h5>
            </div>
            <div class="card-body">
                <div class="row">
                        <div class="mb-4 col-md-6">
                            <x-form.input
                                id="project_number"
                                type="number"
                                min="1"
                                name="project_number"
                                label="رقم المشروع"
                                :value="$project->project_number"
                                required
                            />
                            <small class="text-muted">يجب أن يكون رقم المشروع فريداً</small>
                        </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="name"
                            label="اسم المشروع"
                            :value="$project->name"
                            required
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <label class="form-label" for="institution_id">المؤسسة <span class="text-danger">*</span></label>
                        <select
                            id="institution_id"
                            name="institution_id"
                            class="form-select @error('institution_id') is-invalid @enderror"
                            required
                        >
                            <option value="">إختر القيمة</option>
                            @foreach ($institutions as $institution)
                                <option
                                    value="{{ $institution->id }}"
                                    @selected(old('institution_id', $project->institution_id) == $institution->id)
                                >
                                    {{ $institution->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('institution_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.select
                            name="project_type"
                            id="project_type"
                            label="نوع المشروع"
                            :options="[
                                'cash' => 'نقدي',
                                'in_kind' => 'عيني',
                            ]"
                            :value="$project->project_type"
                            required
                        />
                    </div>
                    <div class="mb-4 col-md-6" id="aid-item-wrapper">
                        <x-form.select
                            id="aid_item_id"
                            name="aid_item_id"
                            label="نوع المساعدة العينية"
                            :optionsId="$aidItems"
                            :value="$project->aid_item_id"
                        />
                    </div>
                    <div class="mb-4 col-md-6" id="quantity-wrapper">
                        <x-form.input
                            id="total_quantity"
                            type="number"
                            min="0.01"
                            step="0.01"
                            name="total_quantity"
                            label="الكمية الإجمالية"
                            :value="$project->total_quantity"
                        />
                    </div>
                    <div class="mb-4 col-md-6" id="cash-amount-wrapper">
                        <x-form.input
                            id="total_amount_ils"
                            type="number"
                            min="0.01"
                            step="0.01"
                            name="total_amount_ils"
                            label="المبلغ الإجمالي بالشيكل"
                            :value="$project->total_amount_ils"
                        />
                    </div>
                    <div class="mb-4 col-md-6" id="estimated-amount-wrapper">
                        <x-form.input
                            type="number"
                            min="0"
                            step="0.01"
                            name="estimated_amount"
                            label="المبلغ التقديري (اختياري)"
                            :value="$project->estimated_amount"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            type="number"
                            min="1"
                            name="beneficiaries_total"
                            label="عدد المستفيدين المسموح"
                            :value="$project->beneficiaries_total"
                            required
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            type="date"
                            name="project_date"
                            label="تاريخ المشروع (اختياري)"
                            :value="$project->project_date?->format('Y-m-d')"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            type="date"
                            name="execution_date"
                            label="تاريخ التنفيذ (اختياري)"
                            :value="$project->execution_date?->format('Y-m-d')"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            type="date"
                            name="receipt_date"
                            label="تاريخ الاستلام (اختياري)"
                            :value="$project->receipt_date?->format('Y-m-d')"
                        />
                    </div>
                    @if($isEdit)
                        <div class="mb-4 col-md-6">
                            <label class="form-label">الكمية المستهلكة</label>
                            <input type="text" class="form-control" value="{{ number_format($project->consumed_quantity ?? 0, 2) }}" disabled>
                            <small class="text-muted">يتم تحديثه تلقائياً عند الصرف</small>
                        </div>
                        <div class="mb-4 col-md-6">
                            <label class="form-label">المبلغ المصروف</label>
                            <input type="text" class="form-control" value="{{ number_format($project->consumed_amount ?? 0, 2) }}" disabled>
                            <small class="text-muted">يتم تحديثه تلقائياً عند الصرف</small>
                        </div>
                        <div class="mb-4 col-md-6">
                            <label class="form-label">المستفيدين الحاصلين</label>
                            <input type="text" class="form-control" value="{{ $project->beneficiaries_consumed ?? 0 }}" disabled>
                            <small class="text-muted">يتم تحديثه تلقائياً عند الصرف</small>
                        </div>
                        <div class="mb-4 col-md-6">
                            <label class="form-label">رصيد المخزن</label>
                            <input type="text" class="form-control" value="{{ $project->project_type === 'cash' ? number_format($project->storage_balance_amount, 2) . ' ₪' : number_format($project->storage_balance_quantity, 2) }}" disabled>
                            <small class="text-muted">الكمية/المبلغ المتبقي من الإجمالي ولم يُوزَّع على المكاتب</small>
                        </div>
                        <div class="mb-4 col-md-6">
                            <label class="form-label">رصيد المكاتب</label>
                            <input type="text" class="form-control" value="{{ $project->project_type === 'cash' ? number_format($project->offices_balance_amount, 2) . ' ₪' : number_format($project->offices_balance_quantity, 2) }}" disabled>
                            <small class="text-muted">مجموع الكمية/المبلغ المُوزَّع على المكاتب (حتى لو بدون مساعدات)</small>
                        </div>
                        <div class="mb-4 col-md-6">
                            <label class="form-label">التبعية</label>
                            <input type="text" class="form-control" 
                                value="{{ $project->dependency_type === 'admin' ? 'الإدارة' : ($project->dependencyOffice?->name ?? '-') }}" 
                                disabled>
                        </div>
                    @endif
                    <div class="mb-4 col-12">
                        <x-form.textarea
                            name="notes"
                            label="ملاحظات"
                            rows="3"
                            :value="$project->notes"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">تفاصيل المشروع</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="mb-4 col-md-6" id="unit-value-wrapper">
                        <x-form.input
                            id="unit_value_ils"
                            type="number"
                            min="0"
                            step="0.01"
                            name="unit_value_ils"
                            label="قيمة الوحدة بالشيكل"
                            :value="$project->unit_value_ils"
                        />
                        <small class="text-muted">للمشاريع العينية فقط</small>
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="department"
                            label="القسم (اختياري)"
                            :value="$project->department"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="supervisor_name"
                            label="اسم المشرف المتابع (اختياري)"
                            :value="$project->supervisor_name"
                        />
                    </div>
                    <div class="mb-4 col-12">
                        <x-form.input
                            name="execution_location"
                            label="مكان التنفيذ (اختياري)"
                            :value="$project->execution_location"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">توزيع الحصص على المكاتب</h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div id="allocation-summary-beneficiaries" class="badge bg-secondary" style="font-size: 0.85rem;">
                        👥 <span id="total-allocated-beneficiaries">0</span> / <span id="total-beneficiaries-display">0</span>
                    </div>
                    <div id="allocation-summary-amount" class="badge bg-secondary allocation-amount-summary" style="font-size: 0.85rem; display: none;">
                        💰 <span id="total-allocated-amount">0</span> / <span id="total-amount-display">0</span> ₪
                    </div>
                    <div id="allocation-summary-quantity" class="badge bg-secondary allocation-quantity-summary" style="font-size: 0.85rem; display: none;">
                        📦 <span id="total-allocated-quantity">0</span> / <span id="total-quantity-display">0</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    <small>حدد حصة كل مكتب من المشروع (اختياري). إذا لم يتم تحديد توزيعات، سيتمكن جميع المكاتب من الصرف من المشروع.</small>
                </p>
                <div class="alert alert-info py-2 mb-3" id="allocation-warning" style="display: none;">
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>تنبيه:</strong> <span id="allocation-warning-text"></span>
                    </small>
                </div>
                <div id="office-allocations-container">
                    @php
                        $existingAllocations = $isEdit ? $project->officeAllocations->keyBy('office_id') : collect();
                        $offices = $offices ?? \App\Models\Office::where('is_active', true)->orderBy('name')->get();
                    @endphp
                    
                    @foreach($offices as $office)
                        @php
                            $allocation = $existingAllocations->get($office->id);
                        @endphp
                        <div class="office-allocation-row mb-3 p-3 border rounded" data-office-id="{{ $office->id }}">
                            <div class="form-check mb-2">
                                <input 
                                    class="form-check-input office-allocation-checkbox" 
                                    type="checkbox" 
                                    name="allocations[{{ $office->id }}][enabled]"
                                    id="office_{{ $office->id }}_enabled"
                                    value="1"
                                    @checked($allocation !== null)
                                >
                                <label class="form-check-label fw-bold" for="office_{{ $office->id }}_enabled">
                                    {{ $office->name }}
                                </label>
                            </div>
                            <div class="allocation-fields" style="display: {{ $allocation ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label small">عدد المستفيدين</label>
                                        <input 
                                            type="number" 
                                            class="form-control form-control-sm allocation-beneficiaries-input" 
                                            name="allocations[{{ $office->id }}][max_beneficiaries]"
                                            min="0"
                                            step="1"
                                            value="{{ $allocation?->max_beneficiaries ?? 0 }}"
                                            data-office-id="{{ $office->id }}"
                                        >
                                    </div>
                                    <div class="col-md-4 mb-2 allocation-amount-field">
                                        <label class="form-label small">المبلغ (₪)</label>
                                        <input 
                                            type="number" 
                                            class="form-control form-control-sm allocation-amount-input" 
                                            name="allocations[{{ $office->id }}][max_amount]"
                                            min="0"
                                            step="0.01"
                                            value="{{ $allocation?->max_amount ?? '' }}"
                                            data-office-id="{{ $office->id }}"
                                        >
                                    </div>
                                    <div class="col-md-4 mb-2 allocation-quantity-field">
                                        <label class="form-label small">الكمية</label>
                                        <input 
                                            type="number" 
                                            class="form-control form-control-sm allocation-quantity-input" 
                                            name="allocations[{{ $office->id }}][max_quantity]"
                                            min="0"
                                            step="1"
                                            value="{{ $allocation?->max_quantity ?? '' }}"
                                            data-office-id="{{ $office->id }}"
                                        >
                                    </div>
                                </div>
                                @if($isEdit && !$isEmployee)
                                    <div class="row mt-2">
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    name="allocations[{{ $office->id }}][received]"
                                                    id="office_{{ $office->id }}_received"
                                                    value="1"
                                                    @checked($allocation?->received ?? false)
                                                >
                                                <label class="form-check-label small" for="office_{{ $office->id }}_received">
                                                    استلام
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            @if($allocation?->receipt_file_path)
                                                <a href="{{ route('dashboard.projects.allocations.receipt', [$project->id, $allocation->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-file-pdf me-1"></i> عرض كشف الإستلام
                                                </a>
                                            @else
                                                <span class="text-muted small">المكتب مش مسلم نهائي الملف</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">ملخص المشروع</h5>
            </div>
            <div class="card-body">
                @if($isEdit)
                    <div class="mb-3">
                        <strong>رقم المشروع:</strong>
                        <div class="text-primary">{{ $project->project_number }}</div>
                    </div>
                    <div class="mb-3">
                        <strong>المُنشئ:</strong>
                        <div>{{ $project->creator?->name ?? '-' }}</div>
                    </div>
                    <hr>
                    @if($project->project_type === 'in_kind' && $project->unit_value_ils)
                        <div class="mb-3">
                            <strong>قيمة الوحدة:</strong>
                            <div>{{ number_format($project->unit_value_ils, 2) }} ₪</div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <strong>المتبقي:</strong>
                        <div class="text-success">
                            @if($project->project_type === 'cash')
                                {{ number_format($project->remaining_amount, 2) }} ₪
                            @else
                                {{ number_format($project->remaining_quantity, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>مستفيدين متبقيين:</strong>
                        <div class="text-info">{{ $project->remaining_beneficiaries }}</div>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="fa-solid fa-info-circle me-1"></i>
                            التبعية سيتم تعيينها تلقائياً عند الحفظ
                        </small>
                    </div>
                @endif

                @if(!$isEmployee)
                    <hr>
                    <div class="mb-3">
                        <label class="form-label" for="status">حالة المشروع</label>
                        <select
                            id="status"
                            name="status"
                            class="form-select @error('status') is-invalid @enderror"
                        >
                            <option value="active" @selected(old('status', $project->status ?? 'active') === 'active')>فعال</option>
                            <option value="closed" @selected(old('status', $project->status ?? 'active') === 'closed')>مغلق</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">المشروع المغلق لا يقبل صرفاً جديداً</small>
                    </div>
                @endif

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" id="submit-project-btn">
                        {{ $isEdit ? 'تحديث المشروع' : 'حفظ المشروع' }}
                    </button>
                    <a href="{{ route('dashboard.projects.index') }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            function toggleProjectTypeFields() {
                const type = $('#project_type').val();
                $('#cash-amount-wrapper').toggle(type === 'cash');
                $('#aid-item-wrapper').toggle(type === 'in_kind');
                $('#quantity-wrapper').toggle(type === 'in_kind');
                $('#estimated-amount-wrapper').toggle(type === 'in_kind');
                $('#unit-value-wrapper').toggle(type === 'in_kind');

                $('#total_amount_ils')
                    .prop('required', type === 'cash')
                    .prop('disabled', type !== 'cash');

                $('#aid_item_id')
                    .prop('required', type === 'in_kind')
                    .prop('disabled', type !== 'in_kind');

                $('#total_quantity')
                    .prop('required', type === 'in_kind')
                    .prop('disabled', type !== 'in_kind');

                $('#unit_value_ils')
                    .prop('required', type === 'in_kind')
                    .prop('disabled', type !== 'in_kind');

                if (type === 'cash') {
                    $('#aid_item_id').val('').trigger('change');
                    $('#total_quantity').val('');
                    $('#unit_value_ils').val('');
                    $('input[name="estimated_amount"]').val('');
                } else if (type === 'in_kind') {
                    $('#total_amount_ils').val('');
                }

                $('.allocation-amount-field').toggle(type === 'cash');
                $('.allocation-quantity-field').toggle(type === 'in_kind');
                $('.allocation-amount-summary').toggle(type === 'cash');
                $('.allocation-quantity-summary').toggle(type === 'in_kind');
                updateAllocationSummary();
            }

            $('#project_type').on('change', toggleProjectTypeFields);
            toggleProjectTypeFields();

            $('.office-allocation-checkbox').on('change', function() {
                const $row = $(this).closest('.office-allocation-row');
                const $fields = $row.find('.allocation-fields');
                
                if ($(this).is(':checked')) {
                    $fields.slideDown();
                } else {
                    $fields.slideUp();
                    $row.find('.allocation-beneficiaries-input').val(0);
                    $row.find('.allocation-amount-input').val('');
                    $row.find('.allocation-quantity-input').val('');
                }
                updateAllocationSummary();
            });

            function updateAllocationSummary() {
                const projectType = $('#project_type').val();
                const totalBeneficiaries = parseInt($('input[name="beneficiaries_total"]').val()) || 0;
                const totalAmount = parseFloat($('#total_amount_ils').val()) || 0;
                const totalQuantity = parseFloat($('#total_quantity').val()) || 0;
                
                let allocatedBeneficiaries = 0;
                let allocatedAmount = 0;
                let allocatedQuantity = 0;

                $('.office-allocation-checkbox:checked').each(function() {
                    const $row = $(this).closest('.office-allocation-row');
                    allocatedBeneficiaries += parseInt($row.find('.allocation-beneficiaries-input').val()) || 0;
                    allocatedAmount += parseFloat($row.find('.allocation-amount-input').val()) || 0;
                    allocatedQuantity += parseFloat($row.find('.allocation-quantity-input').val()) || 0;
                });

                $('#total-beneficiaries-display').text(totalBeneficiaries);
                $('#total-allocated-beneficiaries').text(allocatedBeneficiaries);
                $('#total-amount-display').text(totalAmount.toFixed(2));
                $('#total-allocated-amount').text(allocatedAmount.toFixed(2));
                $('#total-quantity-display').text(totalQuantity.toFixed(2));
                $('#total-allocated-quantity').text(allocatedQuantity.toFixed(2));

                const $summaryBeneficiaries = $('#allocation-summary-beneficiaries');
                const $summaryAmount = $('#allocation-summary-amount');
                const $summaryQuantity = $('#allocation-summary-quantity');
                const $warning = $('#allocation-warning');
                const $warningText = $('#allocation-warning-text');
                const $submitBtn = $('#submit-project-btn');

                let hasError = false;
                let hasWarning = false;
                let warnings = [];

                if (allocatedBeneficiaries > totalBeneficiaries) {
                    $summaryBeneficiaries.removeClass('bg-secondary bg-warning bg-success').addClass('bg-danger');
                    warnings.push(`المستفيدين: المخصص (${allocatedBeneficiaries}) يتجاوز الإجمالي (${totalBeneficiaries})`);
                    hasError = true;
                } else if (totalBeneficiaries > 0 && allocatedBeneficiaries < totalBeneficiaries && allocatedBeneficiaries > 0) {
                    $summaryBeneficiaries.removeClass('bg-secondary bg-danger bg-success').addClass('bg-warning');
                    const remaining = totalBeneficiaries - allocatedBeneficiaries;
                    warnings.push(`المستفيدين: باقي ${remaining} غير مخصص`);
                    hasWarning = true;
                } else if (totalBeneficiaries > 0 && allocatedBeneficiaries === totalBeneficiaries) {
                    $summaryBeneficiaries.removeClass('bg-secondary bg-danger bg-warning').addClass('bg-success');
                } else {
                    $summaryBeneficiaries.removeClass('bg-danger bg-warning bg-success').addClass('bg-secondary');
                }

                if (projectType === 'cash') {
                    if (allocatedAmount > totalAmount) {
                        $summaryAmount.removeClass('bg-secondary bg-warning bg-success').addClass('bg-danger');
                        warnings.push(`المبلغ: المخصص (${allocatedAmount.toFixed(2)} ₪) يتجاوز الإجمالي (${totalAmount.toFixed(2)} ₪)`);
                        hasError = true;
                    } else if (allocatedAmount < totalAmount && allocatedAmount > 0 && totalAmount > 0) {
                        $summaryAmount.removeClass('bg-secondary bg-danger bg-success').addClass('bg-warning');
                        const remaining = totalAmount - allocatedAmount;
                        warnings.push(`المبلغ: باقي ${remaining.toFixed(2)} ₪ غير مخصص`);
                        hasWarning = true;
                    } else if (allocatedAmount === totalAmount && allocatedAmount > 0) {
                        $summaryAmount.removeClass('bg-secondary bg-danger bg-warning').addClass('bg-success');
                    } else {
                        $summaryAmount.removeClass('bg-danger bg-warning bg-success').addClass('bg-secondary');
                    }
                }

                if (projectType === 'in_kind') {
                    if (allocatedQuantity > totalQuantity) {
                        $summaryQuantity.removeClass('bg-secondary bg-warning bg-success').addClass('bg-danger');
                        warnings.push(`الكمية: المخصص (${allocatedQuantity.toFixed(2)}) يتجاوز الإجمالي (${totalQuantity.toFixed(2)})`);
                        hasError = true;
                    } else if (allocatedQuantity < totalQuantity && allocatedQuantity > 0 && totalQuantity > 0) {
                        $summaryQuantity.removeClass('bg-secondary bg-danger bg-success').addClass('bg-warning');
                        const remaining = totalQuantity - allocatedQuantity;
                        warnings.push(`الكمية: باقي ${remaining.toFixed(2)} غير مخصص`);
                        hasWarning = true;
                    } else if (allocatedQuantity === totalQuantity && allocatedQuantity > 0) {
                        $summaryQuantity.removeClass('bg-secondary bg-danger bg-warning').addClass('bg-success');
                    } else {
                        $summaryQuantity.removeClass('bg-danger bg-warning bg-success').addClass('bg-secondary');
                    }
                }

                if (hasError) {
                    $warningText.html('<strong>خطأ:</strong><br>' + warnings.join('<br>'));
                    $warning.removeClass('alert-info alert-success').addClass('alert-danger').show();
                    $submitBtn.prop('disabled', true).addClass('disabled');
                } else {
                    $submitBtn.prop('disabled', false).removeClass('disabled');
                    
                    if (hasWarning) {
                        $warningText.html('<strong>تنبيه:</strong><br>' + warnings.join('<br>'));
                        $warning.removeClass('alert-danger alert-success').addClass('alert-info').show();
                    } else if (allocatedBeneficiaries > 0 || allocatedAmount > 0 || allocatedQuantity > 0) {
                        $warningText.text('✓ تم توزيع الحصص بشكل صحيح!');
                        $warning.removeClass('alert-danger alert-info').addClass('alert-success').show();
                    } else {
                        $warning.hide();
                    }
                }
            }

            $('input[name="beneficiaries_total"]').on('input', updateAllocationSummary);
            $('#total_amount_ils').on('input', updateAllocationSummary);
            $('#total_quantity').on('input', updateAllocationSummary);
            $('.allocation-beneficiaries-input').on('input', updateAllocationSummary);
            $('.allocation-amount-input').on('input', updateAllocationSummary);
            $('.allocation-quantity-input').on('input', updateAllocationSummary);

            $('form').on('submit', function(e) {
                const projectType = $('#project_type').val();
                const totalBeneficiaries = parseInt($('input[name="beneficiaries_total"]').val()) || 0;
                const totalAmount = parseFloat($('#total_amount_ils').val()) || 0;
                const totalQuantity = parseFloat($('#total_quantity').val()) || 0;
                
                let allocatedBeneficiaries = 0;
                let allocatedAmount = 0;
                let allocatedQuantity = 0;
                let hasAllocations = false;
                let errors = [];

                $('.office-allocation-checkbox:checked').each(function() {
                    hasAllocations = true;
                    const $row = $(this).closest('.office-allocation-row');
                    allocatedBeneficiaries += parseInt($row.find('.allocation-beneficiaries-input').val()) || 0;
                    allocatedAmount += parseFloat($row.find('.allocation-amount-input').val()) || 0;
                    allocatedQuantity += parseFloat($row.find('.allocation-quantity-input').val()) || 0;
                });

                if (hasAllocations) {
                    if (allocatedBeneficiaries > totalBeneficiaries) {
                        errors.push(`• عدد المستفيدين المخصص (${allocatedBeneficiaries}) يتجاوز الإجمالي (${totalBeneficiaries})`);
                    }

                    if (projectType === 'cash' && totalAmount > 0 && allocatedAmount > totalAmount) {
                        errors.push(`• المبلغ المخصص (${allocatedAmount.toFixed(2)} ₪) يتجاوز الإجمالي (${totalAmount.toFixed(2)} ₪)`);
                    }

                    if (projectType === 'in_kind' && totalQuantity > 0 && allocatedQuantity > totalQuantity) {
                        errors.push(`• الكمية المخصصة (${allocatedQuantity.toFixed(2)}) تتجاوز الإجمالي (${totalQuantity.toFixed(2)})`);
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        alert('خطأ في التوزيعات:\n\n' + errors.join('\n') + '\n\nيرجى تعديل التوزيعات قبل الحفظ.');
                        return false;
                    }
                }
            });

            updateAllocationSummary();
        });
    </script>
@endpush
