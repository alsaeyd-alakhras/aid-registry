<style>
    /* ───── جدول ───── */
    #traderTable thead th {
        position: sticky;
        top: 0;
        /* ثابت أعلى الشاشة */
        z-index: 2;
        background: #f8f9fa;
        font-size: 1rem;
        /* أكبر قليلاً */
        font-weight: 700;
        color: #000;
        /* أسود غامق */
    }

    #traderTable td,
    #traderTable th {
        color: #000;
    }

    .neg {
        color: red !important;
        font-weight: bold;
    }

    /* لأسماء الشركات أيضاً أسود */
    #traderTable td:nth-child(2) {
        color: #000;
    }
</style>
{{-- نموذج الفلترة --}}
<form id="traderFilter" class="row g-3 align-items-end">
    <div class="col-md-2">
        <label class="form-label">من تاريخ</label>
        <input type="month" name="from_date" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">إلى تاريخ</label>
        <input type="month" name="to_date" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">الشركات</label>
        <div class="dropdown w-100">
            <button class="form-control text-start dropdown-toggle" type="button" data-bs-toggle="dropdown"
                id="companyBtn">اختر الشركات</button>

            <div class="p-2 dropdown-menu w-100" style="max-height:260px;overflow-y:auto;">
                <input type="text" placeholder="بحث..." id="companySearch" class="mb-2 form-control">
                <div class="mb-2 form-check">
                    <input type="checkbox" class="form-check-input" id="selectVisible">
                    <label class="form-check-label" for="selectVisible">اختيار الكل</label>
                </div>
                <ul class="mb-0 list-unstyled" id="companyMenu">
                    @foreach ($allAccounts as $acc)
                        <li>
                            <label class="gap-2 form-check d-flex">
                                <input type="checkbox" class="form-check-input accOpt" value="{{ $acc }}">
                                <span>{{ $acc }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <input type="hidden" name="account" id="accountField">
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> تصفية</button>
        <button type="button" id="traderPrint" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> PDF
        </button>
    </div>
</form>

<!-- فورم التصدير المخفي -->
<form id="exportForm"
      action="{{ route('dashboard.reports.export') }}"
      method="POST"
      target="_blank"   {{-- يفتح في تبويب جديد --}}
      class="d-none">
    @csrf
    <input type="hidden" name="report_type" value="traders_reve">
    <input type="hidden" name="export_type" value="view">
    <input type="hidden" name="month"     id="exMonth">     {{-- من تاريخ --}}
    <input type="hidden" name="to_month"  id="exToMonth">  {{-- إلى تاريخ --}}
    {{-- حقول account[] تُضاف بالدالة JS أدناه --}}
</form>

<hr>

<div class="table-responsive" style="max-height:65vh; overflow:auto;">
    <table id="traderTable" class="table mb-0 text-center align-middle table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>الشركة</th>
                <th>المستحق</th>
                <th>الدفعات</th>
                <th>الرصيد</th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="2" class="text-start">المجموع</td>
                <td id="tTotal" class="fw-bold"></td>
                <td id="tPayments" class="fw-bold"></td>
                <td id="tBalance" class="fw-bold"></td>
            </tr>
        </tfoot>
    </table>
</div>
@push('scripts')
    <script>
        $(function() {
            /* ───── قائمة الشركات ───── */
            $('#companySearch').on('keyup', function() {
                const term = this.value.toLowerCase();
                $('#companyMenu li').each(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(term));
                });
            });

            $('#selectVisible').on('change', function() {
                $('#companyMenu li:visible .accOpt').prop('checked', this.checked).trigger('change');
            });

            // السماح بالنقر على السطر لتبديل الـ checkbox
            $('#companyMenu').on('click', 'li', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = $(this).find('.accOpt');
                    cb.prop('checked', !cb.prop('checked')).trigger('change');
                }
            });

            $('#companyMenu').on('change', '.accOpt', function() {
                let selected = $('.accOpt:checked').map(function() {
                    return this.value;
                }).get();
                $('#accountField').val(selected.join(','));
                $('#companyBtn').text(selected.length ? `(${selected.length}) شركة مختارة` :
                    'اختر الشركات');
            });

            /* ───── جلب البيانات ───── */
            getTraders();
            $('#traderFilter').on('submit', function(e) {
                e.preventDefault();
                getTraders();
            });

            $('#traderPrint').on('click', function () {

                // 1. أربط قيم التاريخين
                $('#exMonth').val( $('[name="from_date"]').val() );
                $('#exToMonth').val( $('[name="to_date"]').val() );

                // 2. أبني مصفوفة الشركات المختارة
                const accounts = $('#accountField').val().split(',').filter(Boolean);

                // 3. أزيل أي حقول account[] سابقة ثم أضيف الحالية
                const form = $('#exportForm');
                form.find('input[name="account[]"]').remove();
                accounts.forEach(acc => {
                    $('<input>', {type:'hidden', name:'account[]', value:acc}).appendTo(form);
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

            function getTraders() {
                let tbody = $('#traderTable tbody').html(
                    '<tr><td colspan="5" class="py-3 text-center">جارٍ التحميل…</td></tr>');
                $.ajax({
                    url: "{{ route('dashboard.reports.tradersReve') }}",
                    data: $('#traderFilter').serialize(),
                    dataType: 'json',
                    success: function(res) {
                        tbody.empty();
                        if (!res.rows.length) {
                            tbody.append('<tr><td colspan="5" class="py-3">لا توجد بيانات</td></tr>');
                            $('#tTotal,#tPayments,#tBalance').text('0').removeClass('neg').addClass(
                                'text-dark');
                            return;
                        }
                        let totalBalance = 0;
                        res.rows.forEach((row, i) => {
                            const bal = Number(row.balance.replace(/,/g, ''));
                            totalBalance += bal;
                            tbody.append(`<tr>
                        <td class="fw-bold">${i+1}</td>
                        <td class="text-start">${row.account}</td>
                        <td class="fw-bold">${(row.total_ils)}</td>
                        <td class="fw-bold">${(row.amount_payments)}</td>
                        <td class="fw-bold ${bal<0?'neg':''}">${(row.balance)}</td>
                    </tr>`);
                        });
                        // إجماليات
                        $('#tTotal').text(fmt(res.totals.total_ils));
                        $('#tPayments').text(fmt(res.totals.amount_payments));
                        const balFmt = fmt(totalBalance);
                        $('#tBalance').text(balFmt).removeClass('neg text-dark').addClass(totalBalance <
                            0 ? 'neg' : 'text-dark');
                    },
                    error: () => tbody.html(
                        '<tr><td colspan="5" class="py-3 text-danger">حدث خطأ، حاول لاحقًا</td></tr>')
                });
            }
        });
    </script>
@endpush
