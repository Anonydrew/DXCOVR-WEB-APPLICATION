{{-- Renders enabled page sections in order. Sections with no source content are skipped. --}}
@foreach ($sections as $section)
    @php($data = $sectionData[$section->id] ?? ['isEmpty' => false])
    @if (! ($data['isEmpty'] ?? false) && view()->exists('site.sections.'.$section->type))
        @include('site.sections.'.$section->type, ['section' => $section, 'data' => $data, 'isFirst' => $loop->first])
    @endif
@endforeach
