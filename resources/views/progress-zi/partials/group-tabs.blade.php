<nav class="zi-tabs {{ $tabsClass ?? '' }}" aria-label="Kelompok ZI">
    @foreach(\App\ZiArea::groupOptions() as $groupType => $groupLabel)
        <a href="{{ route($tabsRoute, ['group_type' => $groupType, 'period_id' => optional($tabsPeriod)->id]) }}"
           class="btn zi-group-tab zi-group-tab--{{ $groupType }}"
           @if($tabsSelected === $groupType) aria-current="page" @endif>
            @if($tabsSelected === $groupType)<i class="fas fa-check" aria-hidden="true"></i>@endif
            <span>{{ $groupLabel }}</span>
            @if($tabsSelected === $groupType)<span class="zi-active-label">Aktif</span>@endif
        </a>
    @endforeach
    @isset($tabsAreaCount)<span class="zi-tab-count">{{ $tabsAreaCount }} area</span>@endisset
</nav>
