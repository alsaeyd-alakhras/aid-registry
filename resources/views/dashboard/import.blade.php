<x-front-layout>
    <div class="m-4 card">
        <div class="card-body">
            <form action="{{ route('dashboard.import') }}" method="post" class="col-12 mb-2" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="file" name="file" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <button type="submit" class="btn btn-primary">رفع</button>
                    </div>
                </div>
            </form>
            <a href="{{ asset('filesExcel/templateAllocation.xlsx') }}" download="templateAllocation.xlsx" class="btn btn-secondary">تحميل نموذج الاستيراد</a>
        </div>
    </div>
</x-front-layout>
