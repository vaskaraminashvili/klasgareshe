@if ($showBreak)
    <div class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative h-full flex flex-col justify-end">
            <div class="mx-auto w-full max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom px-5 pt-4 pb-6 text-center">
                <div class="flex justify-center pt-1">
                    <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
                </div>
                <div class="mx-auto size-20 rounded-full tile-mint grid place-items-center text-4xl mt-3">🦉</div>
                <p class="h-display text-2xl mt-3 text-ink">{{ __('screen-time.break_title') }}</p>
                <p class="text-sm text-muted mt-1">{{ __('screen-time.break_body') }}</p>
                <button type="button" class="btn btn-primary w-full mt-5" wire:click="dismissBreak">{{ __('screen-time.break_continue') }}</button>
            </div>
        </div>
    </div>
@endif
