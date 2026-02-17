@php
    $isEdit = isset($distribution) && $distribution->exists;
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
                <h5 class="mb-0">بيانات الأسرة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="primary_name"
                            label="الاسم الرباعي"
                            :value="$familyForm['primary_name'] ?? ''"
                            required
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="national_id"
                            label="رقم الهوية"
                            maxlength="10"
                            :value="$familyForm['national_id'] ?? ''"
                            required
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="mobile"
                            label="رقم الجوال"
                            maxlength="10"
                            :value="$familyForm['mobile'] ?? ''"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            type="number"
                            min="1"
                            name="family_members_count"
                            label="عدد أفراد الأسرة"
                            :value="$familyForm['family_members_count'] ?? ''"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.input
                            name="housing_location"
                            label="مكان السكن"
                            :value="$familyForm['housing_location'] ?? ''"
                        />
                    </div>
                    <div class="mb-4 col-md-6">
                        <x-form.select
                            name="marital_status"
                            id="marital_status"
                            label="الحالة الزوجية"
                            :options="[
                                'single' => 'أعزب/عزباء',
                                'married' => 'متزوج/ة',
                                'widowed' => 'أرمل/ة',
                                'divorced' => 'مطلق/ة',
                            ]"
                            :value="$familyForm['marital_status'] ?? 'single'"
                            required
                        />
                    </div>
                    <div id="spouse-fields" class="row">
                        <div class="mb-4 col-md-6">
                            <x-form.input
                                id="spouse_name"
                                name="spouse_name"
                                label="اسم الزوج/الزوجة"
                                :value="$familyForm['spouse_name'] ?? ''"
                            />
                        </div>
                        <div class="mb-4 col-md-6">
                            <x-form.input
                                id="spouse_national_id"
                                name="spouse_national_id"
                                label="رقم هوية الزوج/الزوجة"
                                maxlength="10"
                                :value="$familyForm['spouse_national_id'] ?? ''"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">بيانات المساعدة</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <x-form.select
                        name="office_id"
                        label="المكتب"
                        :optionsId="$offices"
                        :value="$distribution->office_id"
                        required
                    />
                </div>
                <div class="mb-4">
                    <x-form.select
                        name="aid_mode"
                        id="aid_mode"
                        label="نوع المساعدة"
                        :options="[
                            'cash' => 'نقدية',
                            'in_kind' => 'عينية',
                        ]"
                        :value="$distribution->aid_mode"
                        required
                    />
                </div>
                <div class="mb-4" id="cash-amount-wrapper">
                    <x-form.input
                        id="cash_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        name="cash_amount"
                        label="قيمة المساعدة"
                        :value="$distribution->cash_amount"
                    />
                </div>
                <div class="mb-4" id="aid-item-wrapper">
                    <x-form.select
                        id="aid_item_id"
                        name="aid_item_id"
                        label="نوع المساعدة العينية"
                        :optionsId="$aidItems"
                        :value="$distribution->aid_item_id"
                    />
                </div>
                <div class="mb-4">
                    <x-form.input
                        type="date"
                        name="distributed_date"
                        label="تاريخ الصرف"
                        :value="$distribution->distributed_at ? $distribution->distributed_at->format('Y-m-d') : ''"
                    />
                </div>
                <div class="mb-4">
                    <x-form.textarea
                        name="distribution_notes"
                        label="ملاحظات"
                        rows="3"
                        :value="$distribution->notes"
                    />
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $isEdit ? 'تعديل' : 'حفظ' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            function toggleSpouseFields() {
                const isMarried = $('#marital_status').val() === 'married';
                $('#spouse-fields').toggle(isMarried);

                $('#spouse_name, #spouse_national_id')
                    .prop('required', isMarried)
                    .prop('disabled', !isMarried);

                if (!isMarried) {
                    $('#spouse_name, #spouse_national_id').val('');
                }
            }

            function toggleAidModeFields() {
                const mode = $('#aid_mode').val();
                $('#cash-amount-wrapper').toggle(mode === 'cash');
                $('#aid-item-wrapper').toggle(mode === 'in_kind');

                $('#cash_amount')
                    .prop('required', mode === 'cash')
                    .prop('disabled', mode !== 'cash');

                $('#aid_item_id')
                    .prop('required', mode === 'in_kind')
                    .prop('disabled', mode !== 'in_kind');

                if (mode === 'cash') {
                    $('#aid_item_id').val('').trigger('change');
                } else if (mode === 'in_kind') {
                    $('#cash_amount').val('');
                }
            }

            $('#marital_status').on('change', toggleSpouseFields);
            $('#aid_mode').on('change', toggleAidModeFields);

            toggleSpouseFields();
            toggleAidModeFields();
        });
    </script>
@endpush
