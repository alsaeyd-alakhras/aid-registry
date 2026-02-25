<x-front-layout>
    @push('styles')
        <style>
            .dashboard-soft-bg {
                background: #f8fafc;
                border-radius: 12px;
            }

            .kpi-card {
                border: 1px solid #edf2f7;
                border-radius: 14px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, 0.05);
            }

            .kpi-value {
                font-size: 1.7rem;
                font-weight: 700;
                line-height: 1.2;
            }

            .kpi-sub {
                font-size: 0.85rem;
                color: #64748b;
            }

            .cash-text {
                color: #198754;
                font-weight: 700;
            }

            .section-card {
                border: 1px solid #edf2f7;
                border-radius: 14px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, 0.04);
            }
        </style>
    @endpush

    <x-slot:extra_nav>
        <div class="mx-2 nav-item">
            <form method="POST" action="{{ route('dashboard.home.refresh-cache') }}" class="d-inline">
                @csrf
                <button class="p-2 border-0 btn btn-outline-primary rounded-pill me-n1 waves-effect waves-light"
                    type="submit" title="تحديث الإحصائيات من المصدر مباشرة">
                    <i class="fa-solid fa-rotate-right fe-16"></i>
                </button>
            </form>
        </div>
    </x-slot:extra_nav>

    <x-slot:breadcrumb>
        <li><a href="#">الرئيسية</a></li>
    </x-slot:breadcrumb>

    @php
        $formatMoney = fn ($value) => number_format((float) $value, 2);
        $formatCount = fn ($value) => number_format((int) $value);
    @endphp

    <div class="p-3 mb-4 dashboard-soft-bg">
        <h5 class="mb-1">لوحة الإحصائيات</h5>
        <p class="mb-0 text-muted">متابعة فورية لإجمالي الأسر والمساعدات وأداء المكاتب لسنة {{ $year }}</p>
    </div>

    <div class="mb-4 row g-3">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">إجمالي الأسر</div>
                <div class="kpi-value">{{ $formatCount($globalStats['total_families']) }}</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['total_families'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">إجمالي عمليات الصرف</div>
                <div class="kpi-value">{{ $formatCount($globalStats['total_distributions']) }}</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['total_distributions'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">إجمالي النقد المصروف</div>
                <div class="kpi-value cash-text">{{ $formatMoney($globalStats['total_cash_all_time']) }} ₪</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['total_cash_all_time'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">صرف الشهر الحالي</div>
                <div class="kpi-value">{{ $formatCount($globalStats['current_month_distributions']) }}</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['current_month_distributions'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">نقد الشهر الحالي</div>
                <div class="kpi-value cash-text">{{ $formatMoney($globalStats['current_month_cash']) }} ₪</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['current_month_cash'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">المكاتب المفعلة</div>
                <div class="kpi-value">{{ $formatCount($globalStats['active_offices']) }}</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['active_offices'] }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="p-3 card kpi-card h-100">
                <div class="mb-1 text-muted">إجمالي المؤسسات</div>
                <div class="kpi-value">{{ $formatCount($globalStats['total_institutions']) }}</div>
                <div class="kpi-sub">{{ $globalStats['comparison']['total_institutions'] }}</div>
            </div>
        </div>
    </div>

    <div class="mb-4 card section-card">
        <div class="py-3 card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Monthly Distribution Overview</h6>
            <span class="text-muted small">{{ $year }}</span>
        </div>
        <div class="card-body">
            <canvas id="monthlyOverviewChart" height="90"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card section-card h-100">
                <div class="py-3 card-header">
                    <h6 class="mb-0">أداء المكاتب</h6>
                </div>
                <div class="p-0 card-body">
                    <div class="table-responsive">
                        <table class="table mb-0 table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>المكتب</th>
                                    <th>عدد العمليات</th>
                                    <th>إجمالي النقد</th>
                                    <th>العيني</th>
                                    <th>آخر عملية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($officeStats as $office)
                                    <tr>
                                        <td>{{ $office->name }}</td>
                                        <td>{{ $formatCount($office->total_distributions) }}</td>
                                        <td class="cash-text">{{ $formatMoney($office->cash_total) }} ₪</td>
                                        <td>{{ $formatCount($office->in_kind_count) }}</td>
                                        <td>{{ $office->last_distribution_date ? \Carbon\Carbon::parse($office->last_distribution_date)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-muted">لا توجد بيانات مكاتب لعرضها</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pt-3 bg-white card-footer">
                    {{ $officeStats->appends(request()->except('office_page'))->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card section-card h-100">
                <div class="py-3 card-header">
                    <h6 class="mb-0">أكثر المساعدات العينية استخداماً</h6>
                </div>
                <div class="p-0 card-body">
                    <div class="table-responsive">
                        <table class="table mb-0 table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الصنف</th>
                                    <th>مرات الصرف</th>
                                    <th>آخر صرف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topAidItems as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $formatCount($item->total_distributed) }}</td>
                                        <td>{{ $item->last_distribution_date ? \Carbon\Carbon::parse($item->last_distribution_date)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-muted">لا توجد بيانات مساعدة عينية</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pt-3 bg-white card-footer">
                    {{ $topAidItems->appends(request()->except('aid_item_page'))->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 card section-card">
        <div class="py-3 card-header">
            <h6 class="mb-0">المؤسسات والمصروف عليها</h6>
        </div>
        <div class="p-0 card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المؤسسة</th>
                            <th>عدد عمليات الصرف</th>
                            <th>المنصرف النقدي</th>
                            <th>الكمية المصروفة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($institutionStats as $institution)
                            <tr>
                                <td>{{ $institution->name }}</td>
                                <td>{{ $formatCount($institution->total_distributions ?? 0) }}</td>
                                <td class="cash-text">{{ $formatMoney($institution->total_spent_cash ?? 0) }} ₪</td>
                                <td>{{ number_format((float) ($institution->total_spent_quantity ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted">لا توجد بيانات مؤسسات لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pt-3 bg-white card-footer">
            {{ $institutionStats->appends(request()->except('institution_page'))->links() }}
        </div>
    </div>

    <div class="mt-4 card section-card">
        <div class="py-3 card-header">
            <h6 class="mb-0">حالة المشاريع</h6>
        </div>
        <div class="p-0 card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم المشروع</th>
                            <th>اسم المشروع</th>
                            <th>المؤسسة</th>
                            <th>النوع</th>
                            <th>عدد المساعدات</th>
                            <th>الإجمالي</th>
                            <th>المصروف</th>
                            <th>المتبقي</th>
                            <th>المستفيدين</th>
                            <th>تفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projectStats as $project)
                            <tr>
                                <td>{{ $project['project_number'] }}</td>
                                <td>{{ $project['name'] }}</td>
                                <td>{{ $project['institution_name'] }}</td>
                                <td>
                                    @if($project['project_type'] === 'cash')
                                        <span class="badge bg-success">نقدي</span>
                                    @else
                                        <span class="badge bg-info">عيني</span>
                                        <div class="small text-muted">{{ $project['aid_item_name'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $formatCount($project['aid_distributions_count']) }}</td>
                                <td>
                                    @if($project['project_type'] === 'cash')
                                        {{ $formatMoney($project['total_amount']) }} ₪
                                    @else
                                        {{ number_format($project['total_quantity'], 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($project['project_type'] === 'cash')
                                        <span class="text-danger">{{ $formatMoney($project['consumed_amount']) }} ₪</span>
                                    @else
                                        <span class="text-danger">{{ number_format($project['consumed_quantity'], 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($project['project_type'] === 'cash')
                                        <span class="text-success">{{ $formatMoney($project['remaining_amount']) }} ₪</span>
                                    @else
                                        <span class="text-success">{{ number_format($project['remaining_quantity'], 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $project['beneficiaries_total'] }}</span>
                                    /
                                    <span class="badge bg-warning">{{ $project['beneficiaries_consumed'] }}</span>
                                    /
                                    <span class="badge bg-success">{{ $project['remaining_beneficiaries'] }}</span>
                                </td>
                                <td>
                                    <button type="button" 
                                        class="btn btn-sm btn-outline-primary view-project-breakdown-btn" 
                                        data-project-id="{{ $project['id'] }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-4 text-center text-muted">لا توجد مشاريع لعرضها</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pt-3 bg-white card-footer">
            {{ $projectStats->appends(request()->except('project_page'))->links() }}
        </div>
    </div>

    <div class="mt-4 card section-card">
        <div class="py-3 card-header">
            <h6 class="mb-0">آخر عمليات الصرف</h6>
        </div>
        <div class="p-0 card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>الأسرة</th>
                            <th>المكتب</th>
                            <th>نوع المساعدة</th>
                            <th>النقد / الصنف</th>
                            <th>مدخل العملية</th>
                            <th>الحالة</th>
                            <th>تفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentDistributions as $distribution)
                            @php
                                $isCancelled = $distribution->status === 'cancelled';
                            @endphp
                            <tr class="{{ $isCancelled ? 'table-danger' : '' }}">
                                <td>{{ optional($distribution->distributed_at)->format('Y-m-d') }}</td>
                                <td>{{ $distribution->family?->full_name ?? '-' }}</td>
                                <td>{{ $distribution->office?->name ?? '-' }}</td>
                                <td>
                                    @if ($distribution->aid_mode === 'cash')
                                        <span class="badge bg-label-success">نقدية</span>
                                    @else
                                        <span class="badge bg-label-info">عينية</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($distribution->aid_mode === 'cash')
                                        <span class="cash-text">{{ $formatMoney($distribution->cash_amount) }} ₪</span>
                                    @else
                                        {{ $distribution->aidItem?->name ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $distribution->creator?->name ?? '-' }}</td>
                                <td>
                                    @if ($isCancelled)
                                        <span class="badge bg-danger">ملغي</span>
                                    @else
                                        <span class="badge bg-success">نشط</span>
                                    @endif
                                </td>
                                <td>
                                    @can('update', App\Models\AidDistribution::class)
                                        <a href="{{ route('dashboard.aid-distributions.show', $distribution->id) }}" class="btn btn-sm btn-outline-primary">عرض التفاصيل</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-4 text-center text-muted">لا توجد عمليات حديثة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pt-3 bg-white card-footer">
            {{ $recentDistributions->appends(request()->except('recent_page'))->links() }}
        </div>
    </div>

    <div class="modal fade" id="projectBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل المشروع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>رقم المشروع:</strong> <span id="breakdown-project-number"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>اسم المشروع:</strong> <span id="breakdown-project-name"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>المؤسسة:</strong> <span id="breakdown-institution"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>النوع:</strong> <span id="breakdown-type"></span>
                        </div>
                    </div>
                    <hr>
                    <h6>توزيع الصرف حسب المكتب والموظف:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>المكتب</th>
                                    <th>الموظف</th>
                                    <th>عدد المستفيدين</th>
                                    <th>المبلغ/الكمية</th>
                                </tr>
                            </thead>
                            <tbody id="breakdown-table-body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
        <script>
            const monthlyLabels = @json($monthlyStats['labels']);
            const monthlyDistributionSeries = @json($monthlyStats['distribution_series']);
            const monthlyCashSeries = @json($monthlyStats['cash_series']);

            const chartCanvas = document.getElementById('monthlyOverviewChart');
            if (chartCanvas) {
                new Chart(chartCanvas, {
                    data: {
                        labels: monthlyLabels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'عدد العمليات',
                                data: monthlyDistributionSeries,
                                backgroundColor: 'rgba(54, 162, 235, 0.35)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                type: 'line',
                                label: 'إجمالي النقد (₪)',
                                data: monthlyCashSeries,
                                borderColor: 'rgba(25, 135, 84, 1)',
                                backgroundColor: 'rgba(25, 135, 84, 0.12)',
                                tension: 0.35,
                                fill: false,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'عدد العمليات'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'إجمالي النقد (₪)'
                                }
                            }
                        }
                    }
                });
            }

            $('.view-project-breakdown-btn').on('click', function() {
                const projectId = $(this).data('project-id');
                
                $.ajax({
                    url: `/api/projects/${projectId}/breakdown`,
                    method: 'GET',
                    success: function(response) {
                        $('#breakdown-project-number').text(response.project.project_number);
                        $('#breakdown-project-name').text(response.project.name);
                        $('#breakdown-institution').text(response.project.institution_name);
                        
                        const typeText = response.project.project_type === 'cash' 
                            ? 'نقدي' 
                            : `عيني (${response.project.aid_item_name})`;
                        $('#breakdown-type').text(typeText);

                        const $tbody = $('#breakdown-table-body');
                        $tbody.empty();

                        if (response.breakdown.length === 0) {
                            $tbody.append('<tr><td colspan="4" class="text-center text-muted">لا توجد عمليات صرف لهذا المشروع</td></tr>');
                        } else {
                            response.breakdown.forEach(function(item) {
                                const valueDisplay = response.project.project_type === 'cash'
                                    ? parseFloat(item.total_cash).toFixed(2) + ' ₪'
                                    : parseFloat(item.total_quantity).toFixed(2);

                                $tbody.append(`
                                    <tr>
                                        <td>${item.office_name}</td>
                                        <td>${item.creator_name}</td>
                                        <td>${item.beneficiaries}</td>
                                        <td>${valueDisplay}</td>
                                    </tr>
                                `);
                            });
                        }

                        new bootstrap.Modal(document.getElementById('projectBreakdownModal')).show();
                    },
                    error: function() {
                        toastr.error('فشل تحميل تفاصيل المشروع');
                    }
                });
            });
        </script>
    @endpush
</x-front-layout>
