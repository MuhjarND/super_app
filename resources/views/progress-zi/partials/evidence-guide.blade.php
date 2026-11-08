@if($guide)
    <div class="zi-evidence-guide" style="font-size:.85rem; line-height:1.6; overflow-wrap:anywhere;">
        @if($guide['area_guidance'] || $guide['point_guidance'])
            <details class="mb-3">
                <summary class="text-muted">Tujuan dan konteks {{ $guide['point_title'] }}</summary>
                @if($guide['area_guidance'])<div class="mt-2" style="white-space:pre-wrap;">{{ $guide['area_guidance'] }}</div>@endif
                @if($guide['point_guidance'])<div class="mt-2" style="white-space:pre-wrap;">{{ $guide['point_guidance'] }}</div>@endif
            </details>
        @endif
        @if($guide['notes'] ?? $guide['guidance'])
            <strong>Catatan/Keterangan/Penjelasan</strong>
            <div class="mt-1 mb-3" style="white-space:pre-wrap;">{{ $guide['notes'] ?? $guide['guidance'] }}</div>
        @endif
        @if($guide['explanation'])
            <strong>Penjelasan pemenuhan</strong>
            <div class="mt-1 mb-3" style="white-space:pre-wrap;">{{ $guide['explanation'] }}</div>
        @endif
        @if(count($guide['supporting_data']))
            <strong>Data pendukung yang perlu disiapkan</strong>
            <ul class="mt-1 mb-3 pl-3">
                @foreach($guide['supporting_data'] as $support)
                    <li>{{ ltrim($support['label'], '- ') }}
                        @if($support['guidance'])<div style="white-space:pre-wrap;">{{ $support['guidance'] }}</div>@endif
                        @if($support['explanation'])<div style="white-space:pre-wrap;">{{ $support['explanation'] }}</div>@endif
                    </li>
                @endforeach
            </ul>
        @endif
        @foreach($guide['images'] as $example)
            <figure class="my-3">
                <a href="{{ asset($example['path']) }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset($example['path']) }}" alt="{{ $example['alt'] }}" loading="lazy" class="img-fluid border rounded" style="max-height:280px; object-fit:contain;">
                </a>
                <figcaption class="small text-muted">Gambar contoh dari panduan. Klik gambar untuk memperbesar.</figcaption>
            </figure>
        @endforeach
    </div>
@else
    <div class="small text-muted">Panduan untuk poin ini belum tersedia.</div>
@endif
