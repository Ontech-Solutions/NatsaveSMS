@if(app()->environment('local'))
    @vite('resources/js/app.js')
@else
    <script src="{{ asset('build/assets/app.js') }}"></script>
@endif