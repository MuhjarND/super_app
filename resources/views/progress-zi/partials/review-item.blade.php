<div class="zi-followup-item">
    <a class="zi-review-item-title" title="{{ $reviewItem['title'] }}" href="{{ route('progress-zi.activities.index', ['period_id' => optional($reviewPeriod)->id, 'area_id' => $reviewItem['activity']->zi_area_id, 'group_type' => optional($reviewItem['activity']->area)->group_type]) }}">{{ $reviewItem['title'] }}</a>
    <div class="small text-muted mt-1">{{ optional($reviewItem['activity']->area)->name }}</div>
    <span class="zi-review-status zi-review-status--{{ $reviewItem['status'] }}">{{ $reviewItem['label'] }}</span>
</div>
