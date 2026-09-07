@php
    $path = $getRecord()?->listeningResource?->media_path;
    $mime = $getRecord()?->listeningResource?->mime_type;
    $url = $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
@endphp
@if ($url)
    <div class="fi-section rounded-xl bg-gray-50 dark:bg-gray-800 p-4 space-y-2">
        <div class="text-xs font-semibold uppercase tracking-widest text-primary-600 dark:text-primary-400">Preview saved file</div>
        @if (str_starts_with((string) $mime, 'video/'))
            <video controls preload="metadata" class="w-full max-h-64 rounded-lg"><source src="{{ $url }}" type="{{ $mime }}"></video>
        @else
            <audio controls preload="metadata" class="w-full"><source src="{{ $url }}" type="{{ $mime ?: 'audio/mpeg' }}"></audio>
        @endif
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ $getRecord()->listeningResource->original_filename ?? basename($path) }}
            @if ($getRecord()->listeningResource->file_size)
                · {{ number_format($getRecord()->listeningResource->file_size / 1024, 0) }} KB
            @endif
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Save the form to attach a newly uploaded file to the resource. The preview above shows the file already saved to disk.</p>
    </div>
@endif
