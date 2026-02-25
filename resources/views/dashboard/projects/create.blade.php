<x-front-layout>
    <div class="container-fluid">
        <form method="POST" action="{{ route('dashboard.projects.store') }}">
            @csrf
            @include('dashboard.projects._form')
        </form>
    </div>
</x-front-layout>
