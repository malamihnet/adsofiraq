@props(['campaign' => null])

@php
    $maxVideos = (int) config('upload.max_videos', 5);

    $initialRows = [];

    if (old('videos') !== null) {
        foreach (old('videos', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $initialRows[] = [
                'key' => 'old-'.$index,
                'id' => $row['id'] ?? null,
                'type' => $row['type'] ?? '',
                'title' => $row['title'] ?? '',
                'url' => $row['url'] ?? '',
                'hasExistingFile' => false,
                'existingFileUrl' => null,
            ];
        }
    } elseif ($campaign?->videos?->isNotEmpty()) {
        foreach ($campaign->videos as $video) {
            $initialRows[] = [
                'key' => 'video-'.$video->id,
                'id' => $video->id,
                'type' => $video->type,
                'title' => $video->title ?? '',
                'url' => $video->url ?? '',
                'hasExistingFile' => $video->type === 'file' && $video->file_path,
                'existingFileUrl' => $video->file_url,
            ];
        }
    }
@endphp

<div
    x-data="campaignVideosManager({ initialRows: @js($initialRows), maxVideos: @js($maxVideos) })"
    class="md:col-span-2 border border-archive-border p-6"
>
    <p class="section-label mb-2">Videos</p>
    <p class="mb-4 text-xs text-archive-gray">
        You can add multiple campaign videos. Each video can be an uploaded file, YouTube link, or Vimeo link.
        For large videos, we recommend adding a Vimeo or YouTube link instead of uploading directly.
    </p>

    @php $maxVideoMb = max(1, (int) round((int) config('upload.max_video_kb', 51200) / 1024)); @endphp

    @error('videos')
        <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
    @enderror

    @foreach(old('videos', []) as $videoIndex => $videoRow)
        @error("videos.{$videoIndex}.file")
            <p class="mb-2 text-sm text-red-600">Video {{ (int) $videoIndex + 1 }}: {{ $message }}</p>
        @enderror
        @error("videos.{$videoIndex}.url")
            <p class="mb-2 text-sm text-red-600">Video {{ (int) $videoIndex + 1 }}: {{ $message }}</p>
        @enderror
        @error("videos.{$videoIndex}.type")
            <p class="mb-2 text-sm text-red-600">Video {{ (int) $videoIndex + 1 }}: {{ $message }}</p>
        @enderror
    @endforeach

    <div class="space-y-6">
        <template x-for="(row, index) in rows" :key="row.key">
            <div class="border border-archive-border p-4">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <p class="text-sm font-medium" x-text="'Video ' + (index + 1)"></p>
                    <button type="button" class="text-xs underline" @click="removeRow(index)">Remove</button>
                </div>

                <input type="hidden" :name="`videos[${index}][id]`" x-model="row.id">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="section-label mb-2 block">Source type</label>
                        <select :name="`videos[${index}][type]`" x-model="row.type" class="input-field">
                            <option value="">Select source</option>
                            <option value="file">Upload file</option>
                            <option value="youtube">YouTube</option>
                            <option value="vimeo">Vimeo</option>
                        </select>
                    </div>

                    <div>
                        <label class="section-label mb-2 block">Video title (optional)</label>
                        <input type="text" :name="`videos[${index}][title]`" x-model="row.title" class="input-field" placeholder="e.g. Director's cut">
                    </div>
                </div>

                <div x-show="row.type === 'file'" x-cloak class="mt-4 space-y-2">
                    <label class="section-label mb-2 block">Video file</label>
                    <input
                        type="file"
                        :name="`videos[${index}][file]`"
                        accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime"
                        class="input-field"
                        :disabled="row.type !== 'file'"
                    >
                    <p class="text-xs text-archive-gray">MP4, WebM, or MOV. Max {{ $maxVideoMb }}MB per file.</p>
                    <p class="text-xs text-archive-gray">If no thumbnail or still is uploaded, Ads of Iraq will try to use a frame from your uploaded video.</p>
                    <template x-if="row.hasExistingFile && row.existingFileUrl">
                        <p class="text-xs text-archive-gray">
                            Current file:
                            <a :href="row.existingFileUrl" target="_blank" rel="noopener" class="underline">View uploaded video</a>
                        </p>
                    </template>
                </div>

                <div x-show="row.type === 'youtube'" x-cloak class="mt-4">
                    <label class="section-label mb-2 block">YouTube URL</label>
                    <input
                        type="url"
                        :name="`videos[${index}][url]`"
                        x-model="row.url"
                        class="input-field"
                        placeholder="https://www.youtube.com/watch?v=..."
                        :disabled="row.type !== 'youtube'"
                    >
                </div>

                <div x-show="row.type === 'vimeo'" x-cloak class="mt-4">
                    <label class="section-label mb-2 block">Vimeo URL</label>
                    <input
                        type="url"
                        :name="`videos[${index}][url]`"
                        x-model="row.url"
                        class="input-field"
                        placeholder="https://vimeo.com/..."
                        :disabled="row.type !== 'vimeo'"
                    >
                </div>
            </div>
        </template>
    </div>

    <button type="button" class="btn-outline mt-6 text-xs" @click="addRow()" x-show="canAdd()" x-cloak>
        Add another video
    </button>
    <p class="mt-2 text-xs text-archive-gray" x-show="!canAdd()" x-cloak>Maximum {{ $maxVideos }} videos per campaign.</p>
</div>
