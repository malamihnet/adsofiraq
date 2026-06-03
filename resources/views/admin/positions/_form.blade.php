@props(['position' => null])

<div class="space-y-4">
    <div>
        <label class="section-label mb-1 block" for="name">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $position?->name) }}" required class="input-field max-w-lg">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-1 block" for="category">Category</label>
        <select name="category" id="category" required class="input-field max-w-lg">
            @foreach($categories as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $position?->category ?? 'other') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-1 block" for="sort_order">Sort order</label>
        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $position?->sort_order ?? 0) }}" min="0" class="input-field max-w-xs">
        @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
