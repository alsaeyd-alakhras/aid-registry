<x-front-layout>
    <div class="row">
        <form action="{{ route('dashboard.accreditations.store') }}" method="post">
            @csrf
            @include('dashboard.accreditations.executives._form')
        </form>
    </div>
</x-front-layout>
