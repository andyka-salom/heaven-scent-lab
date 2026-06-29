@php
$classes = match($s) {
    'draft' => 'bg-gray-100 text-gray-700',
    'released' => 'bg-blue-100 text-blue-700',
    'in_progress' => 'bg-amber-100 text-amber-700',
    'completed' => 'bg-emerald-100 text-emerald-700',
    'cancelled' => 'bg-red-100 text-red-700',
    default => 'bg-gray-100 text-gray-700',
};
$labels = [
    'draft' => 'Draft', 'released' => 'Released', 'in_progress' => 'In Progress',
    'completed' => 'Completed', 'cancelled' => 'Cancelled',
];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $labels[$s] ?? $s }}
</span>
