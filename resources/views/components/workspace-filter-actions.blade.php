@props(['resetUrl' => null])

<div class="workspace-filter__actions">
    <x-portal.button type="submit">Lọc</x-portal.button>
    @if ($resetUrl)
        <x-portal.button variant="secondary" :href="$resetUrl">Xóa bộ lọc</x-portal.button>
    @else
        <x-portal.button type="submit" variant="secondary" name="reset" value="1" formnovalidate>Xóa bộ lọc</x-portal.button>
    @endif
</div>
