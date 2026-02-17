@push('styles')
    <link rel="stylesheet" href="{{ asset('css/custom/select2.min.css') }}">
@endpush
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 col-sm-12">
            <input type="hidden" name="type" value="executive">
            <input type="hidden" name="allocation_id" id="allocation_id" value="{{ $accreditation->allocation_id }}">
            <h3> {{ $btn_label ?? 'اضافة' }} مشروع إعتماد - تنفيذ </h3>
            <div class="p-3 card">
                <h3 class="mb-4">بيانات التنفيذ</h3>
                <div class="row">
                    <div class="my-2 form-group col-md-3 position-relative">
                        <x-form.input type="number" id="budget_number" name="budget_number" label="رقم الموازنة"
                            placeholder="رقم الموزانة : 1212" :value="$accreditation->budget_number" />
                        <ul id="budget_suggestions" class="list-group position-absolute w-100"
                            style="top:100%; z-index:1050; overflow-y: scroll; height: 210px; background-color: #ffffffde; display:none;">
                        </ul>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input type="date" name="implementation_date" label="التاريخ" required
                            :value="$accreditation->implementation_date" />
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <label for="broker_name">المؤسسة</label>
                        <select class="text-center form-select" name="broker_name" id="broker_name"
                            data-placeholder="اختر مؤسسة">
                            <option label="فتح القائمة">
                                @foreach ($brokers as $broker)
                            <option value="{{ $broker }}" @selected($broker == $accreditation->broker_name)>{{ $broker }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input name="account" label="الحساب" list="account_list" required :value="$accreditation->account" />
                        <datalist id="account_list">
                            @foreach ($accounts as $account)
                                <option value="{{ $account }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input name="affiliate_name" label="الاسم" list="affiliate_name_list" required
                            :value="$accreditation->affiliate_name" />
                        <datalist id="affiliate_name_list">
                            @foreach ($affiliates as $affiliate_name)
                                <option value="{{ $affiliate_name }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <label for="field">المجال</label>
                        <select class="text-center form-select" name="field" id="field"
                            data-placeholder="اختر شعبة">
                            <option label="فتح القائمة">
                                @foreach ($fields as $field)
                            <option value="{{ $field }}" @selected($field == $accreditation->field)>{{ $field }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <label for="project_name">المشروع</label>
                        <x-form.input name="project_name" list="projects_list" required :value="$accreditation->project_name" />
                        <datalist id="projects_list">
                            @foreach ($projects as $project)
                                <option value="{{ $project }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <label for="item_name">الصنف</label>
                        <x-form.input name="item_name" list="items_list" required :value="$accreditation->item_name" />
                        <datalist id="items_list">
                            @foreach ($items as $item)
                                <option value="{{ $item }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input name="received" label="المستلم" list="received_list" :value="$accreditation->received" />
                        <datalist id="received_list">
                            @foreach ($receiveds as $received)
                                <option value="{{ $received }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input type="text" class="calculation" name="quantity" label="الكمية"
                            :value="$accreditation->quantity" />
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input type="text" class="calculation" min="0" step="0.01" name="price"
                            label="سعر الوحدة ₪" :value="$accreditation->price" />
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input type="text" class="calculation" min="0" step="0.01" name="total_ils"
                            label="الإجمالي ب ₪" :value="$accreditation->total_ils" />
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <label for="executive_status">حالة التنفيذ</label>
                        <select class="text-center form-select" name="executive_status" id="executive_status"
                            data-placeholder="اختر مؤسسة">
                            <option value="implementation" @selected($accreditation->executive_status == 'implementation')>تنفيذ</option>
                            <option value="receipt" @selected($accreditation->executive_status == 'receipt')>قبض</option>
                        </select>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input name="detail" label="التفصيل.." list="detail_list" :value="$accreditation->detail" />
                        <datalist id="detail_list">
                            @foreach ($details as $detail)
                                <option value="{{ $detail }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="form-group col-md-12">
                        <x-form.textarea name="notes" label="ملاجظات" rows="2" :value="$accreditation->notes" />
                    </div>
                </div>
            </div>
            <hr>
            <div class="p-3 card">
                <h3 class="mb-4">بنود الدفع</h3>
                <div class="row">
                    <div class="my-2 form-group col-md-3">
                        <label for="payment_status">حالة الدفع</label>
                        <select class="text-center form-select" name="payment_status" id="payment_status"
                            data-placeholder="اختر مؤسسة">
                            <option value="later" @selected($accreditation->payment_status == 'later')>آجل</option>
                            <option value="cash" @selected($accreditation->payment_status == 'cash')>كاش</option>
                            <option value="bank" @selected($accreditation->payment_status == 'bank')>بنكي</option>
                        </select>
                    </div>
                    <div class="my-2 form-group col-md-3">
                        <x-form.input type="text" class="calculation" min="0" step="0.01"
                            name="amount_payments" label="الدفعات" :value="$accreditation->amount_payments" />
                    </div>
                    <div class="form-group col-md-6">
                        <x-form.textarea name="payment_mechanism" label="آلية الدفع" rows="1"
                            :value="$accreditation->payment_mechanism" />
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-12">
            <h3 class="mb-4" id="title-data">بيانات</h3>
            <div class="p-3 mb-4 card" id="card-allocation" style="display: none;">
                <table class="w-full text-right border-collapse table-auto">
                    <thead>
                        <tr class="text-gray-700 bg-gray-100">
                            <th class="px-4 py-2 border border-gray-300">النوع</th>
                            <th class="px-4 py-2 border border-gray-300">الإجمالي $</th>
                            <th class="px-4 py-2 border border-gray-300">المنفذ</th>
                            <th class="px-4 py-2 border border-gray-300">المتبقي</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800" id="tbody-allocation">
                    </tbody>
                </table>
            </div>
            {{-- مسـاحة لإظهار بيانات التخصيص المختار --}}
            @if (isset($btn_label))
                <div class="p-3 mb-4 card">
                    <div class="row">
                        <div class="my-2 form-group col-md-3">
                            <x-form.input name="user_id" label="اسم المستخدم" :value="$accreditation->user_name" disabled />
                        </div>
                        <div class="my-2 form-group col-md-3">
                            <x-form.input name="manager_name" label="المدير المستلم" :value="$accreditation->manager_name" disabled />
                        </div>
                    </div>
                </div>
            @endif
            <div class="p-3 card">
                <div class="d-flex justify-content-end" id="btns_form">
                    @if (isset($btn_label))
                        @can('adoption', 'App\\Models\AccreditationProject')
                            <button type="button" class="p-2 mx-2 btn btn-success btn-sm" id="adoption">
                                <i class="fa-solid fa-check"></i> إعتماد
                            </button>
                        @endcan
                    @endif

                    <button type="submit" id="update" class="mx-2 btn btn-primary">
                        <i class="fa-solid fa-edit"></i>
                        {{ $btn_label ?? 'إضافة' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        const csrf_token = "{{ csrf_token() }}";
        const app_link = "{{ config('app.url') }}";
    </script>
    <script src='{{ asset('js/plugins/select2.min.js') }}'></script>
    <script>
        $(document).ready(function() {
            $('#broker_name').select2();

            $(document).on('blur keypress', '.calculation', function(event) {
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

            $(document).on('keypress', 'form', function(event) {
                // تحقق إذا كان الحدث هو الضغط على مفتاح
                if (event.key == "Enter") {
                    event.preventDefault();
                    return;
                }
            });

            // حساب المبلغ
            // $(document).on('input', '.calculation', function () {});
            $(document).on('input', '#quantity , #price', function() {
                let quantity = $('#quantity').val();
                let price = $('#price').val();
                if (quantity != '' && price != '') {
                    quantity = parseFloat(quantity) || 0;
                    price = parseFloat(price) || 0;
                    let total_ils = quantity * price;
                    $('#total_ils').val(total_ils);

                    // remaining
                    let amount_allocations_remaining = $('#amount_allocations_remaining_span').text()
                        .replace(/,/g, '');
                    let amount_allocations_remaining_num = parseFloat(amount_allocations_remaining);


                    let diff = amount_allocations_remaining_num - total_ils;

                    $('#amount_allocations_remaining').html(
                        amount_allocations_remaining_num + ' <span style="color:red;">(' + diff +
                        ')</span>'
                    );
                }
                if (quantity != '') {
                    // remaining
                    let quantity_remaining = $('#quantity_remaining_span').text().replace(/,/g, '');
                    let quantity_remaining_num = parseFloat(quantity_remaining);


                    let diff = quantity_remaining_num - quantity;

                    $('#quantity_remaining').html(
                        quantity_remaining_num + ' <span style="color:red;">(' + diff + ')</span>'
                    );
                }
                if (quantity == '') {
                    // remaining
                    let quantity_remaining = $('#quantity_remaining_span').text().replace(/,/g, '');
                    let quantity_remaining_num = parseFloat(quantity_remaining);

                    $('#quantity_remaining').text(quantity_remaining)
                }
            });
            $('#adoption').click(function() {
                let form = new FormData();
                form.append('adoption', 1);
                form.append('type', 'executive');
                form.append('_token', csrf_token);
                $.ajax({
                    url: `{{ route('dashboard.accreditations.adoption', $accreditation->id ?? '0') }}`,
                    type: 'POST',
                    data: form,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        window.location.href = '{{ route('dashboard.accreditations.index') }}';
                    },
                    error: function(err) {
                        console.log(err);
                    }
                })
            });
        });
    </script>
    <script>
        // دالة صغيرة لتأخير الطلبات 300مللي ثانية (تخفيف الضغط على الخادم)
        const debounce = (fn, delay = 300) => {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        };

        $(function() {
            const csrf = "{{ csrf_token() }}";
            const list = $('#budget_suggestions');

            $('#budget_number').on('input', debounce(function() {
                let val = $('#budget_number').val();
                if (!val.length) {
                    list.hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('dashboard.allocations.getDataByBudgetNumber') }}",
                    method: "POST",
                    data: {
                        _token: csrf,
                        budget_number: val
                    },
                    success(res) {
                        if (!res.length) {
                            list.hide().empty();
                            return;
                        }

                        let items = '';
                        res.forEach(item => {
                            items += `<li class="list-group-item list-group-item-action"
                                        data-id="${item.id}"
                                        data-label="${item.budget_number}">
                                        ${item.budget_number} — ${item.broker_name} (${item.project_name} - ${item.item_name})
                                    </li>`;
                        });
                        list.html(items).show();
                    },
                    error(xhr) {
                        console.error(xhr.responseText)
                    }
                });
            }));

            // اختيار من القائمة عند الضغط على عنصر
            $('#budget_suggestions').on('click', 'li', function() {
                selectBudgetItem($(this));
            });

            // اختيار أول عنصر عند الضغط على Enter + منع إرسال الفورم
            $('#budget_number').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // منع إرسال الفورم
                    const firstItem = $('#budget_suggestions li').first();
                    if (firstItem.length) {
                        selectBudgetItem(firstItem);
                    }
                }
            });

            // دالة اختيار عنصر وتعبئة الحقول
            function selectBudgetItem($item) {
                const id = $item.data('id');
                const label = $item.data('label');

                $('#budget_number').val(label);
                list.hide().empty();

                $.ajax({
                    url: "{{ route('dashboard.allocations.getAllocationDetails') }}",
                    method: "POST",
                    data: {
                        _token: csrf,
                        allocation_id: id
                    },
                    success(res) {
                        $('#card-allocation').show();
                        $('#broker_name').val(res.allocation.broker_name).trigger('change');
                        $('#project_name').val(res.allocation.project_name);
                        $('#allocation_id').val(res.allocation.id);
                        $('#item_name').val(res.allocation.item_name);

                        let quantity_allocation = res.quantity_allocation;
                        let amount_allocation = res.amount_allocation;
                        let amount_received_allocation = res.amount_received_allocation;
                        let quantity_executives = res.quantity_executives;
                        let total_ils_executives = res.total_ils_executives;
                        let amount_payments_executives = res.amount_payments_executives;

                        let tbody = document.getElementById("tbody-allocation");
                        tbody.innerHTML = ''; // تفريغ الجدول أولاً

                        // البيانات ككائنات مصفوفة منظمة
                        let rows = [{
                                id: 'quantity_remaining',
                                idSpan: 'quantity_remaining_span',
                                name: 'الكمية',
                                total: quantity_allocation,
                                executed: quantity_executives,
                                remaining: quantity_allocation - quantity_executives,
                            },
                            {
                                id: "amount_allocations_remaining",
                                idSpan: 'amount_allocations_remaining_span',
                                name: 'المبلغ',
                                total: amount_allocation,
                                executed: total_ils_executives,
                                // remaining : amount_allocation - total_ils_executives,
                                remaining: '',
                            },
                            {
                                id: "amount_received_allocations_remaining",
                                idSpan: 'amount_received_allocations_remaining_span',
                                name: 'المستلم / الدفعات',
                                total: amount_received_allocation,
                                executed: amount_payments_executives,
                                remaining: '',
                            }
                        ];
                        // توليد الصفوف ديناميكياً
                        rows.forEach(row => {
                            let tr = document.createElement("tr");
                            tr.innerHTML = `
                                <td class="px-4 py-2 border border-gray-300">${row.name}</td>
                                <td class="px-4 py-2 border border-gray-300">${row.total}</td>
                                <td class="px-4 py-2 border border-gray-300">${row.executed}</td>
                                <td class="px-4 py-2 border border-gray-300" id="${row.id}">${row.remaining}</td>
                                <span style="display:none;" id="${row.idSpan}">${row.remaining}</span>
                            `;
                            tbody.appendChild(tr);
                        });

                    },
                    error(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }

            // إخفاء القائمة إذا ضغط المستخدم خارجها
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.form-group').length) {
                    list.hide().empty();
                }
            });

            // تأكيد إضافي منع إرسال الفورم إذا Enter ضغط بحقل الموازنة
            $('#budget_number').closest('form').on('submit', function(e) {
                if ($('#budget_suggestions').is(':visible')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
