<style>
    /* ───── جدول ───── */
    #brokerTable thead th,
    #brokerTable tfoot td {
        background: #f8f9fa;
        font-size: 1rem;
        /* أكبر قليلاً */
        font-weight: 700;
        color: #000;
        /* أسود غامق */
    }

    #brokerTable td,
    #brokerTable th {
        color: #000;
        padding: 10px !important;
    }

    .neg {
        color: red !important;
        font-weight: bold;
    }

    /* لأسماء الشركات أيضاً أسود */
    #brokerTable td:nth-child(2) {
        color: #000;
    }
</style>
{{-- نموذج الفلترة --}}
{{-- <form id="brokerFilter" class="row g-3 align-items-end">
    <div class="col-md-2">
        <label class="form-label">من تاريخ</label>
        <input type="month" name="from_date" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">إلى تاريخ</label>
        <input type="month" name="to_date" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">المؤسسات</label>
        <div class="dropdown w-100">
            <button class="form-control text-start dropdown-toggle" type="button" data-bs-toggle="dropdown"
                id="brokerBtn">اختر المؤسسة</button>
            <div class="p-2 dropdown-menu w-100" style="max-height:260px;overflow-y:auto;">
                <input type="text" placeholder="بحث..." id="brokerSearch" class="mb-2 form-control">
                <div class="mb-2 form-check">
                    <input type="checkbox" class="form-check-input" id="selectVisible">
                    <label class="form-check-label" for="selectVisible">اختيار الكل</label>
                </div>
                <ul class="mb-0 list-unstyled" id="brokerMenu">
                    @foreach ($allBrokers as $broker)
                        <li>
                            <label class="gap-2 form-check d-flex">
                                <input type="checkbox" class="form-check-input accOpt" value="{{ $broker }}">
                                <span>{{ $broker }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <input type="hidden" name="broker" id="brokerField">
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> تصفية</button>
        {{-- <button type="button" id="brokerPrint" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> PDF
        </button>
    </div>
</form> --}}

<!-- فورم التصدير المخفي -->
<form id="exportForm" action="{{ route('dashboard.reports.export') }}" method="POST" target="_blank" {{-- يفتح في تبويب جديد --}}
    class="d-none">
    @csrf
    <input type="hidden" name="report_type" value="brokers_reve">
    <input type="hidden" name="export_type" value="view">
    <input type="hidden" name="month" id="exMonth"> {{-- من تاريخ --}}
    <input type="hidden" name="to_month" id="exToMonth"> {{-- إلى تاريخ --}}
    {{-- حقول broker[] تُضاف بالدالة JS أدناه --}}
</form>
<div class="alert alert-warning">
    <span>ملاحظة: الحسابات تبدأ من تاريخ 1/1/2025 فما بعد</span>
</div>
<hr>
<div class="table-responsive" style="max-height:65vh; overflow:auto;">
    <table id="brokerTable" class="table mb-0 text-center align-middle table-bordered table-striped">
        <thead class="text-center align-middle table-light" style="position: sticky; top: 0; z-index: 2;">
            <tr>
                <th rowspan="2" class="align-middle">#</th>
                <th rowspan="2" class="align-middle">المؤسسة</th>
                <th colspan="2">التخصيصات</th>
                <th colspan="2">التنفيذات</th>
                <th colspan="2">الرصيد</th>
            </tr>
            <tr>
                <th>($)</th>
                <th>(₪)</th>
                <th>($)</th>
                <th>(₪)</th>
                <th>($)</th>
                <th>(₪)</th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot class="table-light fw-bold" style="position: sticky; bottom: 0; z-index: 2;">
            <tr>
                <td colspan="2" class="text-start">المجموع</td>

                <!-- التخصيصات -->
                <td id="total_allocations_usd"></td>
                <td id="total_allocations_ils"></td>

                <!-- التنفيذات -->
                <td id="total_implementations_usd"></td>
                <td id="total_implementations_ils"></td>

                <!-- الرصيد -->
                <td id="total_balance_usd"></td>
                <td id="total_balance_ils"></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="brokerModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"> <!-- كبرناها -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brokerModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="p-0 modal-body">
                <div class="table-responsive" style="max-height:70vh; overflow-y:auto;">
                    <table class="table mb-0 text-center align-middle table-sm table-bordered">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th>#</th>
                                <th>رقم الموازنة</th>
                                <th>المؤسسة</th>
                                <th>المشروع</th>
                                <th>الصنف</th>
                                <th>الكمية</th>
                                <th>المنفَّذ</th>
                                <th>المتبقّي</th>
                                <th>مبلغ التخصيص</th>
                                <th>إجمالي التنفيذ (₪)</th>
                            </tr>
                        </thead>
                        <tbody id="allocBody"></tbody>
                        <tfoot class="table-light fw-bold" style="position: sticky; bottom: 0; z-index: 2;">
                            <tr>
                                <td colspan="5" class="text-start">المجموع</td>
                                <td id="sQty"></td>
                                <td id="sExec"></td>
                                <td id="sRemain"></td>
                                <td id="sAllocAmt"></td>
                                <td id="sExecTot"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(function() {
            /* ───── قائمة الشركات ───── */
            $('#brokerSearch').on('keyup', function() {
                const term = this.value.toLowerCase();
                $('#brokerMenu li').each(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(term));
                });
            });

            $('#selectVisible').on('change', function() {
                $('#brokerMenu li:visible .accOpt').prop('checked', this.checked).trigger('change');
            });

            // السماح بالنقر على السطر لتبديل الـ checkbox
            $('#brokerMenu').on('click', 'li', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = $(this).find('.accOpt');
                    cb.prop('checked', !cb.prop('checked')).trigger('change');
                }
            });

            $('#brokerMenu').on('change', '.accOpt', function() {
                let selected = $('.accOpt:checked').map(function() {
                    return this.value;
                }).get();
                $('#brokerField').val(selected.join(','));
                $('#brokerBtn').text(selected.length ? `(${selected.length}) شركة مختارة` :
                    'اختر الشركات');
            });

            /* ───── جلب البيانات ───── */
            getBrokers();
            $('#brokerFilter').on('submit', function(e) {
                e.preventDefault();
                getBrokers();
            });

            $('#brokerPrint').on('click', function() {

                // 1. أربط قيم التاريخين
                $('#exMonth').val($('[name="from_date"]').val());
                $('#exToMonth').val($('[name="to_date"]').val());

                // 2. أبني مصفوفة الشركات المختارة
                const brokers = $('#brokerField').val().split(',').filter(Boolean);

                // 3. أزيل أي حقول broker[] سابقة ثم أضيف الحالية
                const form = $('#exportForm');
                form.find('input[name="broker[]"]').remove();
                brokers.forEach(acc => {
                    $('<input>', {
                        type: 'hidden',
                        name: 'broker[]',
                        value: acc
                    }).appendTo(form);
                });

                // 4. أرسل الفورم (يفتح تبويب PDF)
                form.trigger('submit');
            });


            function format(num) {
                return Number(num).toLocaleString();
            }

            const fmt = n => Number(n).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            function getBrokers() {
                const tbody = $('#brokerTable tbody').html(
                    '<tr><td colspan="8" class="py-3 text-center">جارٍ التحميل…</td></tr>'
                );

                $.ajax({
                    url: "{{ route('dashboard.reports.brokersReve') }}",
                    data: $('#brokerFilter').serialize(),
                    dataType: 'json',
                    success: function(res) {
                        tbody.empty();

                        if (!res.rows.length) {
                            tbody.append('<tr><td colspan="8" class="py-3">لا توجد بيانات</td></tr>');
                            return;
                        }

                        let totals = {
                            allocations_usd: 0,
                            allocations_ils: 0,
                            implementations_usd: 0,
                            implementations_ils: 0,
                            balance_usd: 0,
                            balance_ils: 0
                        };

                        res.rows.forEach((row, i) => {
                            totals.allocations_usd += parseFloat(row.allocations_usd);
                            totals.allocations_ils += parseFloat(row.allocations_ils);
                            totals.implementations_usd += parseFloat(row.implementations_usd);
                            totals.implementations_ils += parseFloat(row.implementations_ils);
                            totals.balance_usd += parseFloat(row.balance_usd);
                            totals.balance_ils += parseFloat(row.balance_ils);

                            tbody.append(`
                                <tr>
                                    <td class="fw-bold">${i + 1}</td>
                                    <td class="d-flex justify-content-between align-items-center">
                                        ${row.broker_name}
                                        <button class="p-0 btn btn-link view-details"
                                                data-broker="${row.broker_name}" title="تفاصيل">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                    <td class="fw-bold">${row.allocations_usd}</td>
                                    <td class="fw-bold">${row.allocations_ils}</td>
                                    <td class="fw-bold">${row.implementations_usd}</td>
                                    <td class="fw-bold">${row.implementations_ils}</td>
                                    <td class="fw-bold">${row.balance_usd}</td>
                                    <td class="fw-bold">${row.balance_ils}</td>
                                </tr>
                            `);
                        });

                        // تحديث المجموع في الفوتر
                        $('#total_allocations_usd').text(totals.allocations_usd.toFixed(2));
                        $('#total_allocations_ils').text(totals.allocations_ils.toFixed(2));
                        $('#total_implementations_usd').text(totals.implementations_usd.toFixed(2));
                        $('#total_implementations_ils').text(totals.implementations_ils.toFixed(2));
                        $('#total_balance_usd').text(totals.balance_usd.toFixed(2));
                        $('#total_balance_ils').text(totals.balance_ils.toFixed(2));
                    },
                    error: () => tbody.html(
                        '<tr><td colspan="8" class="py-3 text-danger">حدث خطأ، حاول لاحقًا</td></tr>'
                    )
                });
            }

        });
    </script>
    <script>
        $(document).on('click', '.view-details', function() {
            const broker = $(this).data('broker');

            const modal = $('#brokerModal'),
                allocTB = $('#allocBody');

            allocTB.html('<tr><td colspan="6" class="py-3">جارٍ التحميل…</td></tr>');
            modal.find('#brokerModalLabel').text(broker);
            modal.modal('show');

            $.ajax({
                url: "{{ route('dashboard.reports.brokerDetails') }}",
                data: {
                    broker
                },
                dataType: 'json',
                success: function(res) {
                    allocTB.empty();

                    if (!res.rows.length) {
                        allocTB.html('<tr><td colspan="6" class="py-3">لا توجد بيانات</td></tr>');
                        fillTotals(res.totals);
                        return;
                    }

                    res.rows.forEach((r, i) => {
                        allocTB.append(`
                        <tr>
                            <td>${i + 1}</td>
                            <td>${(r.budget_number)}</td>
                            <td>${(r.organization_name || '-') }</td>
                            <td>${(r.project_name)}</td>
                            <td>${(r.item_name)}</td>
                            <td>${fmt(r.quantity)}</td>
                            <td>${fmt(r.exec_qty)}</td>
                            <td>${fmt(r.remain)}</td>
                            <td>${fmt(r.amount)}</td>
                            <td>${fmt(r.exec_total)}</td>
                        </tr>
                        `);
                    });


                    fillTotals(res.totals);
                },
                error: () => allocTB.html(
                    '<tr><td colspan="6" class="py-3 text-danger">حدث خطأ، حاول لاحقًا</td></tr>'
                )
            });
        });

        function fillTotals(t) {
            $('#sQty').text(fmt(t.qty));
            $('#sExec').text(fmt(t.exec_qty));
            $('#sRemain').text(fmt(t.remain));
            $('#sAllocAmt').text(fmt(t.alloc_amt));
            $('#sExecTot').text(fmt(t.exec_total));
        }

        /* تنسيق أرقام بسيط */
        function fmt(n) {
            return Number(n || 0).toLocaleString('en');
        }
    </script>
@endpush
