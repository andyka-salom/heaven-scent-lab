<div class="flex items-center gap-2">
    <a href="{{ route('stocks.ledger', $s->material_id) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">
        Buku Besar
    </a>
    @can('stock.set_alert')
    <span class="text-gray-300">|</span>
    <button type="button" onclick="openSetAlert({{ $s->warehouse_id }}, {{ $s->material_id }}, '{{ addslashes($s->material?->name) }}', '{{ addslashes($s->warehouse?->name) }}', {{ $s->min_alert }})" class="text-amber-600 hover:text-amber-800 text-xs font-medium">
        Set Min
    </button>
    @endcan
</div>
