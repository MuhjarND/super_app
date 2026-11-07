<div class="voting-live-toolbar">
    <span class="voting-live-connection" data-live-connection role="status" aria-live="polite">Menghubungkan pemantauan langsung…</span>
    <button type="button" data-live-refresh>Perbarui sekarang</button>
    <span>Status voting: <strong data-live-voting-status>{{ ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai'][$voting->status] ?? $voting->status }}</strong></span>
</div>
<div class="voting-live-complete" data-live-complete {{ $stats['all_voted'] ? '' : 'hidden' }}>Semua peserta sudah memberikan suara.</div>
<div data-results-grid>
    @foreach($stats['items'] as $item)
        <section class="voting-result-card" data-result-item="{{ $item['id'] }}">
            <div class="voting-result-header">
                <div><h2>{{ $item['judul'] }}</h2><span class="voting-result-meta">{{ $item['uses_candidates'] ? 'Pemilihan kandidat' : 'Voting pilihan jawaban' }}</span></div>
                <span class="voting-result-total" data-result-total>{{ $item['total_votes'] }} suara</span>
            </div>
            <div data-result-choices>
                @foreach($item['candidates'] as $choice)
                    <div class="voting-choice-result" data-result-choice="{{ $choice['id'] }}">
                        <div class="voting-choice-header"><strong class="voting-choice-identity">{{ $choice['nama'] }}</strong><span class="voting-choice-count" data-choice-count>{{ $choice['count'] }} suara ({{ $choice['percentage'] }}%)</span></div>
                        <div class="voting-choice-track"><div class="voting-choice-fill" data-choice-fill style="width:{{ $choice['percentage'] }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
