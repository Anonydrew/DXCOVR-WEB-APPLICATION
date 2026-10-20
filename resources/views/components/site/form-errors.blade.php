@if ($errors->any())
    <div class="alert alert-error" role="alert" tabindex="-1" data-error-summary {{ $attributes }}>
        <p class="font-semibold">Please check the form</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
