<x-front-layout>
    <div class="row">
        <form action="{{ route('dashboard.accreditations.update', $accreditation->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('put')
            @include('dashboard.accreditations.allocations._formEdit')
        </form>
    </div>
</x-front-layout>
