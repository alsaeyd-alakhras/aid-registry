<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-edit"></i>
        تعديل بيانات التنفيذ
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <h3 class="mb-4">بيانات التنفيذ</h3>
    <div class="row">
        <div class="form-group col-md-3 position-relative">
            <x-form.input type="number" id="budget_number" name="budget_number" label="رقم الموازنة" placeholder="رقم الموزانة : 1212"/>
            {{-- قائمة الاقتراحات --}}
            <ul id="budget_suggestions"
                class="list-group position-absolute w-100"
                style="top:100%; z-index:1050; overflow-y: scroll; height: 210px; background-color: #ffffffde; display:none;"></ul>
        </div>
        <div class="form-group col-md-3">
            <x-form.input type="date" name="implementation_date" label="التاريخ" required />
        </div>
        <div class="form-group col-md-3 position-relative">
            <label for="broker_name">المؤسسة</label>
            <input type="text" id="broker_name" name="broker_name" autocomplete="off" class="form-control" placeholder="اكتب اسم المؤسسة">
            <ul id="broker_suggestions" class="list-group suggestion-list">
                @foreach ($brokers as $broker)
                    <li class="list-group-item list-group-item-action" data-value="{{ $broker }}">
                        {{ $broker }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="form-group col-md-3">
            <x-form.input name="account" label="الحساب" list="account_list" required />
            <datalist id="account_list">
                @foreach ($accounts as $account)
                    <option value="{{ $account }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <x-form.input name="affiliate_name" label="الاسم" list="affiliate_name_list" required />
            <datalist id="affiliate_name_list">
                @foreach ($affiliates as $affiliate_name)
                    <option value="{{ $affiliate_name }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <label for="field">المجال</label>
            <x-form.input name="field" list="field_list" required />
            <datalist id="field_list">
                @foreach ($fields_name as $field)
                    <option value="{{ $field }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <label for="project_name">المشروع</label>
            <x-form.input name="project_name" list="projects_list" required />
            <datalist id="projects_list">
                @foreach ($projects as $project)
                    <option value="{{ $project }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <label for="item_name">الصنف</label>
            <x-form.input name="item_name" list="items_list" required />
            <datalist id="items_list">
                @foreach ($items as $item)
                    <option value="{{ $item }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <x-form.input name="received" label="المستلم" list="received_list" />
            <datalist id="received_list">
                @foreach ($receiveds as $received)
                    <option value="{{ $received }}">
                @endforeach
            </datalist>
        </div>

        <div class="form-group col-md-3">
            <x-form.input type="text" class="calculation"  name="quantity" label="الكمية"/>
        </div>
        <div class="form-group col-md-3">
            <x-form.input type="text" class="calculation"  min="0" step="0.01" name="price" label="سعر الوحدة ₪" />
        </div>
        <div class="form-group col-md-3">
            <x-form.input type="text" class="calculation"  min="0" step="0.01" name="total_ils" label="الإجمالي ب ₪" />
        </div>
        <div class="my-2 form-group col-md-3">
            <label for="executive_status">حالة التنفيذ</label>
            <select class="text-center form-select" name="executive_status" id="executive_status" data-placeholder="اختر مؤسسة">
                <option value="implementation">تنفيذ</option>
                <option value="receipt">قبض</option>
            </select>
        </div>
        <div class="form-group col-md-3">
            <x-form.input name="detail" label="التفصيل.." list="detail_list" />
            <datalist id="detail_list">
                @foreach ($details as $detail)
                    <option value="{{ $detail }}">
                @endforeach
            </datalist>
        </div>

        <div class="form-group col-md-12">
            <x-form.textarea name="notes" label="ملاجظات" rows="2" />
        </div>
    </div>
    <hr>
    <h3>بنود الدفع</h3>
    <div class="row">
        <div class="my-2 form-group col-md-3">
            <label for="payment_status">حالة الدفع</label>
            <select class="text-center form-select" name="payment_status" id="payment_status" data-placeholder="اختر مؤسسة">
                <option value="later">آجل</option>
                <option value="cash">كاش</option>
                <option value="bank">بنكي</option>
            </select>
        </div>
        <div class="form-group col-md-3">
            <x-form.input type="text" class="calculation" min="0" step="0.01" name="amount_payments" label="الدفعات" />
        </div>
        <div class="form-group col-md-6">
            <x-form.textarea name="payment_mechanism" label="آلية الدفع" rows="1"  />
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="form-group col-md-3 editForm">
            <x-form.input name="user_name" label="اسم المستخدم" disabled />
        </div>
        <div class="form-group col-md-3 editForm">
            <x-form.input name="manager_name" label="المدير المستلم" disabled />
        </div>
    </div>
</div>

<div class="modal-footer" >
    <div class="gap-2 d-flex justify-content-end" id="btns_form">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fas fa-times"></i>
            إغلاق
        </button>
        <button type="button" id="update" class="btn btn-primary">
            <i class="fas fa-save"></i>
            حفظ التعديلات
        </button>
    </div>
</div>

@push('scripts')
    <script>
        $('.calculation').on('blur keypress', function (event) {
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
        // التعديلات الحاصلة على النموذج
        $('#quantity, #price').on('input blur', function () {
            // جلب القيم من الحقول
            let quantity = $('#quantity').val();
            let price = $('#price').val();

            if(quantity != '' && price != ''){
                quantity = parseFloat(quantity);
                price = parseFloat(price);
                let total_ils = quantity * price;
                $('#total_ils').val(total_ils);
            }
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
                        $('#broker_name').val(res.broker_name).trigger('change');
                        $('#project_name').val(res.project_name);
                        $('#item_name').val(res.item_name);
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
    <script>
        $(function() {
            const $input = $('#broker_name');
            const $list = $('#broker_suggestions');

            $input.on('input', function() {
                const val = $(this).val().toLowerCase();
                if (!val) {
                    $list.hide();
                    return;
                }

                let hasMatch = false;
                $list.find('li').each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes(val)) {
                        $(this).show();
                        hasMatch = true;
                    } else {
                        $(this).hide();
                    }
                });

                hasMatch ? $list.show() : $list.hide();
            });

            // اختيار عنصر
            $list.on('click', 'li', function() {
                $input.val($(this).data('value'));
                $list.hide();
            });

            // Enter يختار أول عنصر
            $input.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const first = $list.find('li:visible').first();
                    if (first.length) {
                        first.trigger('click');
                    }
                }
            });

            // إخفاء القائمة عند الضغط خارجها
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.form-group').length) {
                    $list.hide();
                }
            });
        });
    </script>
@endpush
