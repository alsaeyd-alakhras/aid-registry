<x-front-layout>
    <div class="m-4 card">
        <div class="card-body">
            <form action="{{ route('dashboard.financialtransactions.import') }}" method="post" class="col-12" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <input type="file" name="file" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">رفع</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-front-layout>
