<x-front-layout>
    <div class="container-fluid">
        <form method="POST" action="{{ route('dashboard.projects.update', $project->id) }}">
            @csrf
            @method('PUT')
            @include('dashboard.projects._form')
        </form>
    </div>
</x-front-layout>
