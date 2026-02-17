@if ($errors->any())
    <div class="alert alert-danger">
        <h3> Ooops Error</h3>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            @foreach ($errors->keys() as $key)
                <li>{{ $key }}</li>
            @endforeach
        </ul>
    </div>
@endif
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-invoice.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endpush
<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="rounded card-body invoice-preview-header">
                <div class="flex-wrap d-flex flex-column flex-sm-row justify-content-between text-heading">
                    <div class="mb-6 mb-md-0">
                        <div class="gap-2 mb-6 d-flex svg-illustration align-items-center">
                            <div class="">
                                <img src="{{ asset('imgs/logo-brand.png') }}" alt="" width="50">
                            </div>
                            <span class="app-brand-text fw-bold fs-4 ms-50">{{ isset($btn_label) ? 'تعديل تخصيص': 'إضافة تخصيص جديدة' }}</span>
                        </div>
                    </div>
                    <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
                        <dl class="mb-0 row">
                            <dt class="mb-2 col-sm-5 d-md-flex align-items-center justify-content-end">
                                <span class="mb-0 h5 text-capitalize text-nowrap">رقم الموازنة</span>
                            </dt>
                            <dd class="col-sm-7">
                                <div class="input-group input-group-merge disabled">
                                    <span class="input-group-text">#</span>
                                    <input type="hidden" name="type" value="allocation">
                                    <x-form.input type="number" name="budget_number" :value="$allocation->budget_number" />
                                </div>
                            </dd>
                            <dt class="mb-2 col-sm-5 d-md-flex align-items-center justify-content-end">
                                <span class="fw-normal">التاريخ:</span>
                            </dt>
                            <dd class="col-sm-7">
                                <x-form.input class="invoice-date flatpickr-input" type="date" placeholder="YYYY-MM-DD" :value="$allocation->date_allocation" name="date_allocation" required />
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            {{-- <hr class="mt-0">
            <div class="pt-4 card-body">
            </div> --}}
            <hr class="mt-0">
            <div class="pt-4 card-body">
                <div class="d-flex align-items-center justify-content-between">
                    {{-- <h4>التخصيصات</h4> --}}
                </div>
                <input type="hidden" name="items_count" value="0" id="items_count">
                <div class="row" id="items">

                </div>
                <div class="mt-2 d-flex justify-content-end">
                    <button type="button" class="text-white btn btn-success" id="add-item">
                        <i class="fa-solid fa-plus"></i> اضافة
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-4 card">
            <div class="card-body">
                <div class="mb-4">
                    <label for="broker_name">المؤسسة</label>
                    <select class="text-center form-select select2" name="broker_name" id="broker_name">
                        <option value="" disabled selected>إختر المؤسسة</option>
                        @foreach ($brokers as $broker)
                        <option value="{{ $broker }}">{{ $broker }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label for="organization_name">المتبرع</label>
                    <x-form.input name="organization_name" :value="$allocation->organization_name" list="organizations_list" required />
                    <datalist id="organizations_list">
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization }}">
                        @endforeach
                    </datalist>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <button type="submit" class="mb-4 btn btn-primary d-grid w-100 waves-effect waves-light">
                    <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="ti ti-send ti-xs me-2"></i>{{ $btn_label ?? 'إضافة' }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery-repeater/jquery-repeater.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>

    <script src="{{ asset('assets/js/offcanvas-send-invoice.js') }}"></script>
    <script src="{{ asset('assets/js/app-invoice-add.js') }}"></script>
    <script>
        $(document).ready(function () {
            $(document).on('keypress', 'form', function (event) {
                // تحقق إذا كان الحدث هو الضغط على مفتاح
                if (event.key == "Enter" && event.target.tagName != 'TEXTAREA') {
                    event.preventDefault();
                    return;
                }
            });
            $(document).on('blur keypress', '.calculation', function (event) {
                // تحقق إذا كان الحدث هو الضغط على مفتاح
                if (event.type == 'keypress' && event.key != "Enter") {
                    return;
                }
                // استرجاع القيمة المدخلة
                var input = $(this).val();
                try {
                    // استخدام eval لحساب الناتج (مع الاحتياطات الأمنية)
                    var result = eval(input);
                    // عرض الناتج في الحقل
                    $(this).val(result);
                } catch (e) {
                    // في حالة وجود خطأ (مثل إدخال غير صحيح)
                    alert('يرجى إدخال معادلة صحيحة!');
                }
            });
            // Select2
            $(".select2").select2();

            // Items
            let item_count = $("#items_count").val();
            $("#add-item").on("click", function () {
                let index = parseFloat($("#items").children().length);
                let item =
                `<div class="py-4 repeater-wrapper" data-repeater-item=""  id="item-${index}">
                    <div class="rounded border d-flex border-primary position-relative pe-0">
                        <div class="p-6 row w-100">
                            <h5>بنود التخصيص</h5>
                            <div class="row">
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input name="project_name[${index}]" list="projects_list_${index}" label="المشروع" required />
                                    <datalist id="projects_list_${index}">
                                        @foreach ($projects as $project)
                                            <option value="{{ $project }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.select label="الصنف" name="item_name[${index}]" class="select2" :options="$items" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" name="quantity[${index}]" class="calculation quantity" id="quantity-${index}"  data-index="${index}"  label="الكمية" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" step="0.01" name="price[${index}]" label="سعر الوحدة" class="calculation price" id="price-${index}" data-index="${index}" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" step="0.01" name="total_dollar[${index}]" label="الإجمالي" class="total_dollar" readonly id="total_dollar-${index}" data-index="${index}" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" step="0.01" name="allocation[${index}]" label="التخصيص" class="calculation allocation" id="allocation-${index}" data-index="${index}" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <label for="currency_allocation_${index}">العملة</label>
                                    <select class="text-center border-2 form-control currency_allocation border-primary" name="currency_allocation[${index}]" id="currency_allocation-${index}" data-index="${index}">
                                        <option label="اختر العملة"></option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->code }}" data-val="{{ $currency->value }}" @selected($currency->code == "USD")>{{ $currency->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" step="0.01" name="currency_allocation_value[${index}]" label="سعر الدولار للعملة" class="calculation currency_allocation_value" :value="$USD" id="currency_allocation_value-${index}" data-index="${index}" />
                                </div>

                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="number" min="0" step="any" id="amount-${index}" name="amount[${index}]" label="المبلغ $" class="amount" data-index="${index}" readonly />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="number" min="0" step="any" id="exchange_rate-${index}" name="exchange_rate[${index}]" label="سعر صرف الدولار" class="exchange_rate" value="{{ number_format(1 / $ILS, 2) }}" data-index="${index}" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input type="text" min="0" class="calculation" name="number_beneficiaries[${index}]" id="number_beneficiaries-${index}" label="عدد المستفيدين" class="calculation number_beneficiaries" data-index="${index}" />
                                </div>

                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.textarea name="implementation_items[${index}]" id="implementation_items-${index}" class="implementation_items" data-index="${index}" rows="1" label="بنود التنفيذ" />
                                </div>
                                <div class="mb-4 col-md-3 col-sm-4">
                                    <x-form.input value="5" type="number" min="0" max="100" step="1" name="percentage_female_administrators[${index}]" label="نسبة الإداريات %" id="percentage_female_administrators-${index}" data-index="${index}" />
                                </div>
                            </div>
                            <hr class="mt-0">
                            <h5>بنود القبض</h5>
                            <div class="row">
                                <div class="mb-4 col-md-6 col-sm-4">
                                    <x-form.input type="date" name="date_implementation[${index}]" id="date_implementation-${index}" label="تاريخ القبض" class="date_implementation" data-index="${index}" />
                                </div>
                                <div class="mb-4 col-md-6 col-sm-4">
                                    <x-form.input type="text" class="calculation amount_received" min="0" step="0.01" name="amount_received[${index}]"
                                        data-index="${index}" label="المبلغ المقبوض" id="amount_received-${index}" />
                                </div>
                                <div class="mb-4 col-md-6 col-sm-4">
                                    <x-form.input type="number" min="0" name="arrest_receipt_number[${index}]" label="رقم إيصال القبض"
                                        class="arrest_receipt_number" data-index="${index}" id="arrest_receipt_number-${index}" />
                                </div>
                                <div class="mb-4 col-md-6 col-sm-4">
                                    <x-form.textarea name="implementation_statement[${index}]" data-index="${index}" class="implementation_statement" label="بيان" rows="1" id="implementation_statement-${index}" />
                                </div>
                            </div>
                        </div>
                        <div class="p-2 d-flex flex-column align-items-center justify-content-between border-start">
                            <i class="cursor-pointer ti ti-x ti-lg remove-item" data-repeater-delete="" data-index="${index}"></i>
                        </div>
                    </div>
                </div>`;
                $("#items").append(item);
                $(".select2").select2();
                $("#items_count").val(index + 1);
            });

            $(document).on("click", ".remove-item", function () {
                let index = $(this).data("index");
                $("#item-" + index).remove();
                let items_count = $("#items_count").val();
                items_count = items_count - 1;
                $("#items_count").val(items_count);
            });

            // Calculate
            $(document).on('input','.quantity, .price', function () {
                const index = $(this).data('index');
                let quantity = parseFloat($("#quantity-" + index).val()) || 0;
                let price = parseFloat($("#price-" + index).val()) || 0;
                let currencyAllocation = parseFloat($('#currency_allocation_value-' + index).val()) || 0;
                let total_dollar = quantity * price;
                $('#total_dollar-' + index).val(total_dollar);
                $('#allocation-' + index).val(total_dollar);
                $('#amount-' + index).val(parseFloat(total_dollar / currencyAllocation).toFixed(2));
            });

            $(document).on('input', '.allocation', function () {
                const index = $(this).data('index');
                let allocation = parseFloat($("#allocation-" + index).val()) || 0;
                let currencyAllocation = parseFloat($('#currency_allocation_value-' + index).val()) || 0;
                $('#amount-' + index).val(parseFloat(allocation / currencyAllocation).toFixed(2));
            });

            $(document).on('change', '.currency_allocation', function () {
                const index = $(this).data('index');
                var currencyAllocation = parseFloat($(this).find('option:selected').data('val')) || 0; //إذا كان الحقل فارغًا، اعتبر القيمة 0
                $('#currency_allocation_value-' + index).val(parseFloat(1 / currencyAllocation).toFixed(2))
                $('#amount-' + index).val((parseFloat($('#allocation-' + index).val()) / currencyAllocation).toFixed(2));
            });

            $(document).on('input', '.currency_allocation_value', function () {
                const index = $(this).data('index');
                let currencyAllocation = parseFloat($('#currency_allocation_value-' + index).val()) || 0;
                $('#amount-' + index).val((parseFloat($('#allocation-' + index).val()) / currencyAllocation).toFixed(2));
            });

        });

    </script>
@endpush
