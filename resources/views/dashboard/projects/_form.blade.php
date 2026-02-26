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

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
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

                $('#total_amount_ils')
                    .prop('required', type === 'cash')
                    .prop('disabled', type !== 'cash');

                $('#aid_item_id')
                    .prop('required', type === 'in_kind')
                    .prop('disabled', type !== 'in_kind');

                $('#total_quantity')
                    .prop('required', type === 'in_kind')
                    .prop('disabled', type !== 'in_kind');

                if (type === 'cash') {
                    $('#aid_item_id').val('').trigger('change');
                    $('#total_quantity').val('');
                    $('input[name="estimated_amount"]').val('');
                } else if (type === 'in_kind') {
                    $('#total_amount_ils').val('');
                }
            }

            $('#project_type').on('change', toggleProjectTypeFields);
            toggleProjectTypeFields();
        });
    </script>
@endpush
