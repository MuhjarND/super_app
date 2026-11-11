@php
    $memberList = collect($members ?? []);
    if ($memberList->isEmpty() && !empty($fallback)) {
        $memberList = collect([$fallback]);
    }
    $variant = $variant ?? 'stacked';
    $memberList = $memberList->filter(function ($member) {
        return trim(is_object($member) ? (string) $member->name : (string) $member) !== '';
    })->values();
    $isCompact = $variant === 'compact';
    $visibleMembers = $isCompact ? $memberList->take(2) : $memberList;
@endphp

<div class="zi-member-list zi-member-list--{{ $variant }}" aria-label="Anggota area">
    @if($isCompact && $memberList->isNotEmpty())<span class="zi-member-count">{{ $memberList->count() }} penanggung jawab</span>@endif
    @forelse($visibleMembers as $member)
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
    @if($isCompact && $memberList->isNotEmpty())
        <button type="button" class="btn zi-btn-teal btn-sm zi-members-trigger" data-toggle="modal" data-target="#ziMembersModal"
                data-members-template="{{ $memberListId }}" data-area-title="{{ $areaTitle }}" aria-haspopup="dialog"
                aria-label="Lihat {{ $memberList->count() }} penanggung jawab {{ $areaTitle }}">
            {{ $memberList->count() > 2 ? '+' . ($memberList->count() - 2) . ' lainnya' : 'Lihat daftar' }}
        </button>
        <template id="{{ $memberListId }}">
            @include('progress-zi.partials.member-list', ['members' => $memberList, 'variant' => 'directory'])
        </template>
    @endif
</div>
