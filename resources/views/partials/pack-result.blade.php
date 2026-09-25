@if ($showResult)
    <div class="fixed inset-0 z-40" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative h-full flex flex-col justify-end">
            <div class="mx-auto w-full max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom px-5 pt-4 pb-6 text-center">
                <div class="flex justify-center pt-1">
                    <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
                </div>
                <div class="mx-auto size-20 rounded-full tile-mint grid place-items-center text-4xl mt-3">⭐</div>
                <p class="h-display text-2xl mt-3 text-ink">{{ __('quiz.result_title') }}</p>
                <p class="h-display text-4xl mt-2 text-ink">{{ $correctCount }} / {{ $resultTotal }}</p>
                <p class="text-sm text-muted mt-1">{{ __('quiz.xp_earned', ['xp' => $resultXp]) }}</p>
                <p class="text-sm font-extrabold mt-3 text-primary-ink">
                    @if ($yesterdayCorrect === null)
                        {{ __('quiz.no_yesterday') }}
                    @elseif ($beatYesterday)
                        {{ __('quiz.beat_yesterday') }}
                    @else
                        {{ __('quiz.short_of_yesterday', ['count' => $yesterdayCorrect]) }}
                    @endif
                </p>
                <button type="button" class="btn btn-primary w-full mt-5" wire:click="continueFromResult">
                    <i class="ph-fill ph-arrow-right"></i> {{ __('quiz.continue') }}
                </button>
            </div>
        </div>
    </div>
@endif
