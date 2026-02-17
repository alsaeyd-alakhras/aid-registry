<x-front-layout>
    <div class="mb-2 row align-items-center">
        <div class="col">
            <h2 class="h5 page-title">جدول المكاتب</h2>
        </div>
        <div class="col-auto">
            @can('create', 'App\\Models\Office')
                <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">
                    <i class="fa-solid fa-plus"></i>
                </a>
            @endcan
        </div>
    </div>
    <div class="row">
        <!-- Small table -->
        <div class="my-4 col-md-12">
            <div class="shadow card">
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>المكان</th>
                                <th>الحدث</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($offices as $office)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$office->name}}</td>
                                    <td>{{$office->location}}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="p-0 btn dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @can('update', 'App\\Models\Office')
                                                    <button type="button" class="dropdown-item"
                                                        style="margin: 0.5rem -0.75rem; text-align: right;"
                                                        data-bs-toggle="modal" data-bs-target="#edit_{{$office->id}}">
                                                        <i class="ti ti-pencil me-1"></i>تعديل
                                                    </button>
                                                @endcan
                                                @can('delete', 'App\\Models\Office')
                                                    <form action="{{route('dashboard.offices.destroy', $office->id)}}"
                                                        method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit" class="dropdown-item"
                                                            style="margin: 0.5rem -0.75rem; text-align: right;">
                                                            <i class="ti ti-trash me-1"></i>حذف
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- customized table -->
    </div> <!-- end section -->
    @can('update', 'App\\\Models\Office')
        @foreach ($offices as $office)
            <div class="modal fade" id="edit_{{$office->id}}" tabindex="-1" role="dialog"
                aria-labelledby="edit{{$office->id}}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="edit{{$office->id}}Label">تعديل عملة : {{$office->name}}</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{route('dashboard.offices.update', $office->id)}}" method="post">
                                @csrf
                                @method('put')
                                <div class="form-group">
                                    <x-form.input required label="إسم العملة" :value="$office->name" min="0" name="name"
                                        placeholder="الدولار" />
                                </div>
                                <div class="form-group">
                                    <x-form.input required label="رمز العملة" :value="$office->code" min="0" name="code"
                                        placeholder="USD" />
                                </div>
                                <div class="form-group">
                                    <x-form.input required label="قيمة العملة" type="number" :value="$office->value" step=".01"
                                        min="0" name="value" placeholder="3.80" />
                                </div>
                                <div class="form-group">
                                    <x-form.input required label="قيمة العملة مقابل الشيكل" type="number"
                                        :value="$office->value_to_ils" step=".01" min="0" name="value_to_ils"
                                        placeholder="1.00" />
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">تعديل</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan
    @can('create', 'App\\\Models\Office')
        <div class="modal fade" id="create" tabindex="-1" role="dialog" aria-labelledby="createLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createLabel">إنشاء مكتب جديد</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{route('dashboard.offices.store')}}" method="post">
                        <div class="modal-body">
                            @csrf
                            <div class="form-group">
                                <x-form.input required label="اسم المكتب" name="name" placeholder="" />
                            </div>
                            <div class="form-group">
                                <x-form.input required label="المكان" name="location" placeholder="" />
                            </div>
                            <div class="form-group">
                                <x-form.input label="الملاحظة" name="notes" placeholder="" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">أضف</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endcan

</x-front-layout>