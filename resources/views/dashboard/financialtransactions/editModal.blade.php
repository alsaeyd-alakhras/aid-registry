<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-file-invoice-dollar"></i>
        إضافة/تعديل حركة مالية
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="row">
        <!-- المعلومات الأساسية -->
        <div class="col-12">
            <h6 class="pb-2 mb-3 text-primary border-bottom">
                <i class="fas fa-info-circle"></i> المعلومات الأساسية
            </h6>
        </div>

        <!-- رقم القيد والتاريخ -->
        <div class="form-group col-md-6">
            <x-form.input type="number" name="entry_number" id="entry_number" label="رقم القيد"
                placeholder="سيتم توليده تلقائياً" readonly />
        </div>
        <div class="form-group col-md-6">
            <x-form.input type="date" name="transaction_date" id="transaction_date" label="تاريخ المعاملة"
                required />
        </div>

        <!-- اسم الحساب والاسم -->
        <div class="form-group col-md-6 position-relative">
            <label for="account_name">اسم الحساب</label>
            <input type="text" id="account_name" name="account_name" autocomplete="off" class="form-control" placeholder="اكتب اسم الحساب" required>
            <ul class="list-group suggestion-list">
                @foreach ($account_names as $account)
                    <li class="list-group-item list-group-item-action" data-value="{{ $account }}">
                        {{ $account }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="form-group col-md-6 position-relative">
            <label for="name">الاسم</label>
            <input type="text" id="name" name="name" autocomplete="off" class="form-control" placeholder="اكتب الاسم">
            <ul class="list-group suggestion-list">
                @foreach ($names as $name)
                    <li class="list-group-item list-group-item-action" data-value="{{ $name }}">
                        {{ $name }}
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- البيان -->
        <div class="form-group col-md-12">
            <label class="form-label fw-bold">البيان</label>
            <textarea class="form-control" name="description" id="description" rows="2" placeholder="وصف المعاملة"></textarea>
        </div>

    </div>
    <div class="row">
        <!-- معلومات المشروع -->
        <div class="mt-3 col-12">
            <h6 class="pb-2 mb-3 text-success border-bottom">
                <i class="fas fa-project-diagram"></i> معلومات المشروع
            </h6>
        </div>

        <div class="form-group col-md-4 position-relative">
            <label for="project">المشروع</label>
            <input type="text" id="project" name="project" autocomplete="off" class="form-control" placeholder="اكتب اسم المشروع">
            <ul class="list-group suggestion-list">
                @foreach ($projects as $project)
                    <li class="list-group-item list-group-item-action" data-value="{{ $project }}">
                        {{ $project }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="form-group col-md-4 position-relative">
            <label for="field">المجال</label>
            <input type="text" id="field" name="field" autocomplete="off" class="form-control" placeholder="اكتب اسم المشروع">
            <ul class="list-group suggestion-list">
                @foreach ($fields as $field)
                    <li class="list-group-item list-group-item-action" data-value="{{ $field }}">
                        {{ $field }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="form-group col-md-4 position-relative">
            <label for="funder">الممول</label>
            <input type="text" id="funder" name="funder" autocomplete="off" class="form-control" placeholder="اكتب اسم الممول">
            <ul class="list-group suggestion-list">
                @foreach ($funders as $funder)
                    <li class="list-group-item list-group-item-action" data-value="{{ $funder }}">
                        {{ $funder }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="form-group col-md-6 position-relative">
            <x-form.input type="number" id="budget_number" name="budget_number" label="رقم الموازنة"
                placeholder="رقم الموازنة (اختياري)" />
            <ul id="budget_suggestions" class="list-group position-absolute w-100"
                style="top:100%; z-index:1050; overflow-y: scroll; height: 210px; background-color: #ffffffde; display:none;">
            </ul>
        </div>
        <div class="form-group col-md-6 position-relative">
            <label for="association">الجمعية</label>
            <input type="text" id="association" name="association" autocomplete="off" class="form-control" placeholder="اكتب اسم الجمعية">
            <ul class="list-group suggestion-list">
                @foreach ($associations as $association)
                    <li class="list-group-item list-group-item-action" data-value="{{ $association }}">
                        {{ $association }}
                    </li>
                @endforeach
            </ul>
        </div>

    </div>
    <div class="row">
        <!-- المبلغ والعملة -->
        <div class="mt-3 col-12">
            <h6 class="pb-2 mb-3 text-warning border-bottom">
                <i class="fas fa-coins"></i> المبلغ والعملة
            </h6>
        </div>

        <div class="form-group col-md-4">
            <x-form.input type="number" min="0" step="0.01" name="amount" id="amount"
                label="المبلغ الأساسي" required />
        </div>
        <div class="form-group col-md-4">
            <label class="form-label fw-bold">العملة <span class="text-danger">*</span></label>
            <select class="form-select" name="currency" id="currency" required>
                <option value="">اختر العملة...</option>
                <option value="شيكل">شيكل (₪)</option>
                <option value="دولار">دولار ($)</option>
            </select>
        </div>
        <div class="form-group col-md-4 d-none" id="currency_value_container">
            <x-form.input type="number" min="0" step="0.0001" name="currency_value" id="currency_value"
                label="قيمة الدولار للشيكل (تحسب فقط في حساب التاجر)" />
        </div>
        <div class="form-group col-md-4">
            <label class="form-label fw-bold">نوع الحساب <span class="text-danger">*</span></label>
            <select class="form-select" name="account_type" id="account_type" required>
                <option value="">اختر النوع...</option>
                <option value="صرف">صرف</option>
                <option value="قبض">قبض</option>
                <option value="دائن">دائن</option>
                <option value="مدين">مدين</option>
            </select>
        </div>
    </div>
    <div class="row">

        <!-- تفاصيل إضافية -->
        <div class="form-group col-md-6 position-relative">
            <label for="fund">الصندوق</label>
            <input type="text" id="fund" name="fund" autocomplete="off" class="form-control" placeholder="اكتب اسم الصندوق">
            <ul class="list-group suggestion-list">
                @foreach ($funds as $fund)
                    <li class="list-group-item list-group-item-action" data-value="{{ $fund }}">
                        {{ $fund }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="form-group col-md-6">
            <x-form.input type="text" name="exchange_rate" id="exchange_rate" label="سعر التحويل"
                placeholder="سعر التحويل" />
        </div>
    </div>
    <div class="row">

        <!-- المبالغ التفصيلية -->
        <div class="mt-3 col-12">
            <h6 class="pb-2 mb-3 text-info border-bottom">
                <i class="fas fa-calculator"></i> المبالغ التفصيلية
            </h6>
        </div>

        <div class="form-group col-md-6">
            <x-form.input type="number" min="0" step="0.01" name="debit_shekel" id="debit_shekel"
                label="مدين بالشيكل (₪)" />
        </div>
        <div class="form-group col-md-6">
            <x-form.input type="number" min="0" step="0.01" name="credit_shekel" id="credit_shekel"
                label="دائن بالشيكل (₪)" />
        </div>

        <div class="form-group col-md-6">
            <x-form.input type="number" min="0" step="0.01" name="debit_dollar" id="debit_dollar"
                label="مدين بالدولار ($)" />
        </div>
        <div class="form-group col-md-6">
            <x-form.input type="number" min="0" step="0.01" name="credit_dollar" id="credit_dollar"
                label="دائن بالدولار ($)" />
        </div>

    </div>
    <div class="row">
        <!-- ربط التاجر -->
        <div class="mt-3 col-12">
            <h6 class="pb-2 mb-3 text-secondary border-bottom">
                <i class="fas fa-link"></i> ربط التاجر (اختياري)
            </h6>
        </div>

        <div class="form-group col-md-12">
            <label class="form-label fw-bold">التاجر المرتبط</label>
            <select class="form-select" name="merchant_id" id="merchant_id">
                <option value="">اختر التاجر (اختياري)...</option>
                @if (isset($merchants))
                    @foreach ($merchants as $merchant)
                        <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                    @endforeach
                @endif
            </select>
            <small class="form-text text-muted">يمكن ربط المعاملة بتاجر موجود أو تركها بدون ربط</small>
        </div>

        <!-- ملاحظات -->
        <div class="mt-3 form-group col-md-12">
            <label class="form-label fw-bold">ملاحظات إضافية</label>
            <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="ملاحظات إضافية (اختياري)"></textarea>
        </div>
    </div>
</div>

<div class="modal-footer">
    <div class="gap-2 d-flex justify-content-end" id="btns_form">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i>
            إلغاء
        </button>
        <button type="button" class="btn btn-primary" id="update">
            <i class="fas fa-save"></i>
            حفظ التعديلات
        </button>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            // تحديث المبالغ التفصيلية عند تغيير المبلغ الأساسي أو العملة
            function updateDetailedAmounts() {
                const amount = parseFloat($('#amount').val()) || 0;
                const currency = $('#currency').val();
                const currency_value = parseFloat($('#currency_value').val()) || 0;
                const accountType = $('#account_type').val();

                if (currency == 'دولار') {
                    $('#currency_value_container').removeClass('d-none');
                } else {
                    $('#currency_value_container').addClass('d-none');
                }

                if (amount > 0 && currency && accountType) {
                    // إعادة تعيين جميع الحقول
                    $('#debit_shekel, #credit_shekel, #debit_dollar, #credit_dollar').val(0);

                    // تحديد الحقل المناسب حسب العملة ونوع الحساب
                    if (currency === 'شيكل') {
                        if (accountType === 'دائن' || accountType === 'صرف') {
                            $('#credit_shekel').val(amount.toFixed(2));
                        } else if (accountType === 'مدين' || accountType === 'قبض') {
                            $('#debit_shekel').val(amount.toFixed(2));
                        }
                    } else if (currency === 'دولار') {
                        if (accountType === 'دائن' || accountType === 'صرف') {
                            $('#credit_dollar').val(amount.toFixed(2));
                        } else if (accountType === 'مدين' || accountType === 'قبض') {
                            $('#debit_dollar').val(amount.toFixed(2));
                        }
                    }
                }
            }

            // استماع للتغييرات
            $('#amount, #currency, #account_type').on('change input', updateDetailedAmounts);

            // تطبيق التحديث عند التحميل إذا كانت القيم موجودة
            if ($('#amount').val() && $('#currency').val() && $('#account_type').val()) {
                updateDetailedAmounts();
            }

            // تحسين UX: إضافة validation بصري
            $('input[required], select[required], textarea[required]').on('blur', function() {
                if ($(this).val()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            // تنظيف التحديد عند إغلاق المودال
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('input, select, textarea').removeClass('is-valid is-invalid');
            });

            $(document).on('click', '#addExecutive', function(e) {
                $('input[required], select[required], textarea[required]').trigger('blur');
                if ($('input[required], select[required], textarea[required]').hasClass('is-invalid')) {
                    e.preventDefault();
                    return;
                }
            });
        });
    </script>
    <script>
        // كود عام للـ Autocomplete لجميع العناصر
        $(document).ready(function() {
            // تطبيق الـ autocomplete على جميع العناصر التي تحتوي على suggestion-list
            $('.suggestion-list').each(function() {
                const $list = $(this);
                const $input = $list.prev('input'); // الـ input الذي قبل القائمة

                if (!$input.length) return; // إذا لم يوجد input تجاهل

                // البحث عند الكتابة
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

                // اختيار عنصر من القائمة
                $list.on('click', 'li', function() {
                    const value = $(this).data('value') || $(this).text();
                    $input.val(value);
                    $list.hide();

                    // إطلاق event مخصص للعناصر التي تحتاج معالجة إضافية
                    $input.trigger('autocomplete:selected', [value, $(this)]);
                });

                // Enter يختار أول عنصر ظاهر
                $input.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const $first = $list.find('li:visible').first();
                        if ($first.length) {
                            $first.trigger('click');
                        }
                    }

                    // ESC يخفي القائمة
                    if (e.key === 'Escape') {
                        $list.hide();
                    }
                });

                // إظهار القائمة عند التركيز
                $input.on('focus', function() {
                    if ($(this).val()) {
                        $input.trigger('input');
                    }
                });
            });

            // إخفاء جميع القوائم عند الضغط خارجها
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('.suggestion-list').hide();
                }
            });
        });

        // وظيفة مساعدة لإضافة autocomplete لعنصر جديد ديناميكياً
        function initAutocomplete(inputSelector, data) {
            const $input = $(inputSelector);
            const $container = $input.parent();

            // إنشاء قائمة الاقتراحات
            let listHtml = '<ul class="list-group suggestion-list">';
            data.forEach(function(item) {
                const value = typeof item === 'object' ? item.value : item;
                const text = typeof item === 'object' ? item.text : item;
                listHtml += `<li class="list-group-item list-group-item-action" data-value="${value}">${text}</li>`;
            });
            listHtml += '</ul>';

            // إضافة القائمة وتطبيق الوظائف
            $container.append(listHtml);

            // التأكد من أن الـ container له position relative
            if (!$container.hasClass('position-relative')) {
                $container.addClass('position-relative');
            }
        }
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
                        $('#funder').val(res.allocation.broker_name);
                        $('#project').val(res.allocation.project_name);
                        $('#field').val(res.allocation.project_name);
                        $('#association').val(res.allocation.implementation_statement);
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
