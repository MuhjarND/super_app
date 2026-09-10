@php
    $memberList = collect($members ?? []);
    if ($memberList->isEmpty() && !empty($fallback)) {
        $memberList = collect([$fallback]);
    }
    $variant = $variant ?? 'stacked';
@endphp

<div class="zi-member-list zi-member-list--{{ $variant }}" aria-label="Anggota area">
    @forelse($memberList as $member)
        @php
            $memberName = is_object($member) ? $member->name : (string) $member;
            $parts = preg_split('/\s+/', trim($memberName), -1, PREG_SPLIT_NO_EMPTY);
            $initials = collect(array_slice($parts ?: ['-'], 0, 2))->map(function ($part) {
                return strtoupper(substr($part, 0, 1));
            })->implode('');
        @endphp
        <span class="zi-member-chip" title="{{ $memberName }}">
            <span class="zi-member-avatar" aria-hidden="true">{{ $initials ?: '-' }}</span>
            <span class="zi-member-name">{{ $memberName }}</span>
        </span>
    @empty
        <span class="zi-member-empty">PIC belum ditentukan</span>
    @endforelse
</div>
