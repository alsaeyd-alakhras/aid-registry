<x-front-layout>
    <x-slot:breadcrumb>
        <li><a href="{{ route('dashboard.accreditations.index') }}">مشاريع الإعتماد</a></li>
    </x-slot:breadcrumb>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="mx-3 mt-3 mb-6 nav-align-top">
                    <div class="d-flex justify-content-between">
                        <div class="p-3 d-flex justify-content-start align-items-start">
                            <ul class="nav nav-pills" role="tablist">
                                <li class="nav-item me-2">
                                    <button
                                    type="button"
                                    class="nav-link active"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-home"
                                    aria-controls="navs-pills-top-home"
                                    aria-selected="true">
                                        التخصيصات
                                        <span class="text-white badge bg-success badge-center ms-1" id="accreditationsAllocationCount">{{ $accreditationsAllocation->count() == 0 ? '' : $accreditationsAllocation->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item me-2">
                                    <button
                                    type="button"
                                    class="nav-link"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-profile"
                                    aria-controls="navs-pills-top-profile"
                                    aria-selected="false">
                                    التنفيذات
                                        <span class="text-white badge bg-info badge-center ms-1" id="accreditationsExecutiveCount">{{ $accreditationsExecutive->count() == 0 ? '' : $accreditationsExecutive->count() }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="p-3 d-flex justify-content-end align-items-start">
                            @can('allocation','App\\Models\AccreditationProject')
                            <a href="{{route('dashboard.accreditations.create')}}" class="m-0 btn btn-primary me-2">
                                <i class="fa-solid fa-plus"></i> إضافة تخصيص
                            </a>
                            @endcan
                            @can('execution','App\\Models\AccreditationProject')
                            <a href="{{route('dashboard.accreditations.createExecutive')}}" class="m-0 btn btn-info">
                                <i class="fa-solid fa-plus"></i> إضافة تنفيذ
                            </a>
                            @endcan
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table mb-0 align-items-center table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th class="text-center text-secondary opacity-7">#</th>
                                            <th>رقم الموازنة</th>
                                            <th>التاريخ</th>
                                            <th>المؤسسة</th>
                                            <th>المشروع</th>
                                            <th>الصنف</th>
                                            <th>الكمية</th>
                                            <th>السعر</th>
                                            <th>التخصيص</th>
                                            <th>المبلغ الإجمالي</th>
                                            <th>اسم المستخدم</th>
                                            <th>تاريخ الإضافة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($accreditationsAllocation as $accreditation)
                                        <tr>
                                            <td class="text-center d-flex justify-content-center align-items-center">
                                                @can('adoption', 'App\\Models\AccreditationProject')
                                                <form action="{{route('dashboard.accreditations.adoption', $accreditation->id)}}" method="POST">
                                                    @csrf
                                                    <input type="text" name="adoption" value="{{$accreditation->id}}" hidden>
                                                    <input type="text" name="type" value="{{$accreditation->type}}" hidden>
                                                    <button type="submit" title="اعتماد" class="p-2 mx-2 btn btn-success btn-sm">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                                @can('update', 'App\\Models\AccreditationProject')
                                                <a href="{{route('dashboard.accreditations.edit', $accreditation->id)}}" class="p-2 btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                @endcan
                                                @can('delete', 'App\\Models\AccreditationProject')
                                                <form action="{{route('dashboard.accreditations.destroy', $accreditation->id)}}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="حذف" class="p-2 mx-2 btn btn-danger btn-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </td>
                                            <td class="text-center">{{$loop->iteration}}</td>
                                            <td>{{ $accreditation->budget_number ?? '' }}</td>
                                            <td>{{ $accreditation->date_allocation ?? '' }}</td>
                                            <td>{{ $accreditation->broker_name ?? '' }}</td>
                                            <td>{{ $accreditation->project_name ?? '' }}</td>
                                            <td>{{ $accreditation->item_name ?? '' }}</td>
                                            <td>{{ $accreditation->quantity ?? '' }}</td>
                                            <td>{{ $accreditation->price ?? '' }}</td>
                                            <td>{{ $accreditation->allocation ?? '' }}</td>
                                            <td>{{ ($accreditation->amount != null) ? $accreditation->amount : $accreditation->total_ils }}</td>
                                            <td>{{ $accreditation->user->name ?? '' }}</td>
                                            <td>{{ $accreditation->created_at ?? '' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div>
                                    {{ $accreditationsAllocation->links() }}
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table mb-0 align-items-center table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th class="text-center text-secondary opacity-7">#</th>
                                            <th>رقم الموازنة</th>
                                            <th>التاريخ</th>
                                            <th>المؤسسة</th>
                                            <th>المشروع</th>
                                            <th>الحساب</th>
                                            <th>الصنف</th>
                                            <th>الكمية</th>
                                            <th>السعر</th>
                                            <th>المبلغ الإجمالي</th>
                                            <th>اسم المستخدم</th>
                                            <th>تاريخ الإضافة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($accreditationsExecutive as $accreditation)
                                        <tr>
                                            <td class="text-center d-flex justify-content-center align-items-center">
                                                @can('adoption', 'App\\Models\AccreditationProject')
                                                <form action="{{route('dashboard.accreditations.adoption', $accreditation->id)}}" method="POST">
                                                    @csrf
                                                    <input type="text" name="adoption" value="{{$accreditation->id}}" hidden>
                                                    <input type="text" name="type" value="{{$accreditation->type}}" hidden>
                                                    <button type="submit" title="اعتماد" class="p-2 mx-2 btn btn-success btn-sm">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                                @can('update', 'App\\Models\AccreditationProject')
                                                <a href="{{route('dashboard.accreditations.editExecutive', $accreditation->id)}}" class="p-2 btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                @endcan
                                                @can('delete', 'App\\Models\AccreditationProject')
                                                <form action="{{route('dashboard.accreditations.destroy', $accreditation->id)}}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 mx-2 btn btn-danger btn-sm">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </td>
                                            <td class="text-center">{{$loop->iteration}}</td>
                                            <td>{{ $accreditation->budget_number ?? '' }}</td>
                                            <td>{{ $accreditation->implementation_date ?? '' }}</td>
                                            <td>{{ $accreditation->broker_name ?? '' }}</td>
                                            <td>{{ $accreditation->project_name ?? '' }}</td>
                                            <td>{{ $accreditation->account ?? '' }}</td>
                                            <td>{{ $accreditation->item_name ?? '' }}</td>
                                            <td>{{ $accreditation->quantity ?? '' }}</td>
                                            <td>{{ $accreditation->price ?? '' }}</td>
                                            <td>{{ ($accreditation->amount != null) ? $accreditation->amount : $accreditation->total_ils }}</td>
                                            <td>{{ $accreditation->user->name ?? '' }}</td>
                                            <td>{{ $accreditation->created_at ?? '' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div>
                                    {{ $accreditationsExecutive->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function () {
                setInterval(function () {
                    let accreditations = $.ajax({
                        url: "{{route('dashboard.accreditations.checkNew')}}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (data) {
                            if(accreditations_count != data){
                                window.location.reload();
                            }
                            let accreditationsAllocation = data.accreditationsAllocation;
                            let accreditationsExecutive = data.accreditationsExecutive;
                            $('#accreditationsAllocationCount').text(accreditationsAllocation);
                            $('#accreditationsExecutiveCount').text(accreditationsExecutive);
                        }
                    })
                }, 10000);
            });
        </script>
    @endpush
</x-front-layout>
