<?php

use App\Enums\SupportTopic;
use App\Repositories\UserRepository;
use App\Services\SupportService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('კონტაქტი · Kidzio')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $topic = 'general';

    public string $body = '';

    public bool $sent = false;

    public function mount(UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->name = $user->name;
        $this->email = $user->email;
        $requested = request()->query('topic');

        if (is_string($requested) && SupportTopic::tryFrom($requested) instanceof SupportTopic) {
            $this->topic = $requested;
        }
    }

    public function send(SupportService $support, UserRepository $users): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', Rule::enum(SupportTopic::class)],
            'body' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $support->submit(
            $users->authenticated(),
            $this->name,
            $this->email,
            SupportTopic::from($this->topic),
            $this->body,
        );

        $this->sent = true;
        $this->body = '';
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('support.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('support.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('support.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('support.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-friends">
      <div class="relative flex items-center gap-3">
        <div class="text-5xl">✉️</div>
        <div class="grow">
          <span class="chip bg-white/20 border-0 text-white">{{ __('support.chip') }}</span>
          <p class="h-display text-xl mt-1 leading-tight">{{ __('support.hero_title') }}</p>
          <p class="text-xs text-white/90">{{ __('support.hero_body') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-4 grid grid-cols-2 gap-3">
    <a href="mailto:{{ config('kidzio.support_email') }}" class="k-card p-3 text-center">
      <div class="size-10 rounded-2xl tile-violet grid place-items-center text-xl mx-auto">✉️</div>
      <p class="font-extrabold text-sm mt-2">{{ __('support.email') }}</p>
      <p class="text-[10px] text-muted">{{ __('support.email_hint') }}</p>
    </a>
    <a href="{{ route('help-faq') }}" wire:navigate class="k-card p-3 text-center">
      <div class="size-10 rounded-2xl tile-sun grid place-items-center text-xl mx-auto">📖</div>
      <p class="font-extrabold text-sm mt-2">{{ __('support.faq') }}</p>
      <p class="text-[10px] text-muted">{{ __('support.faq_hint') }}</p>
    </a>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('support.form') }}</p>
    @if ($sent)
      <p class="text-sm mt-3" style="color:var(--color-k-primary-ink)">{{ __('support.sent') }}</p>
    @endif
    <form class="mt-3 space-y-3" wire:submit="send">
      <div>
        <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('support.name') }}</label>
        <div class="input-wrap mt-1">
          <i class="ph ph-smiley i-left"></i>
          <input class="input has-left" placeholder="{{ __('support.name_placeholder') }}" wire:model="name" required/>
        </div>
        @error('name')
          <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('support.email_label') }}</label>
        <div class="input-wrap mt-1">
          <i class="ph ph-envelope-simple i-left"></i>
          <input type="email" class="input has-left" placeholder="{{ __('support.email_placeholder') }}" wire:model="email" required/>
        </div>
        @error('email')
          <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('support.topic') }}</label>
        <select class="input mt-1" wire:model="topic">
          @foreach (SupportTopic::cases() as $case)
            <option value="{{ $case->value }}">{{ $case->label() }}</option>
          @endforeach
        </select>
        @error('topic')
          <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('support.message') }}</label>
        <textarea class="input mt-1 !h-32 py-3 leading-relaxed" placeholder="{{ __('support.message_placeholder') }}" wire:model.live="body" required maxlength="500"></textarea>
        <p class="text-[10px] text-muted mt-1 text-right">{{ __('support.count', ['used' => mb_strlen($body), 'max' => 500]) }}</p>
        @error('body')
          <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
      </div>

      <p class="text-xs text-muted">{{ __('support.no_diagnostics') }}</p>

      <button type="submit" class="btn btn-primary w-full mt-2">
        <i class="ph-fill ph-paper-plane-right"></i> {{ __('support.send') }}
      </button>
    </form>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('support.channels') }}</p>
    <div class="mt-3 space-y-2">
      <a href="mailto:{{ config('kidzio.support_email') }}" class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-envelope-simple"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('support.support_email') }}</p>
          <p class="text-[11px] text-muted">{{ config('kidzio.support_email') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      <a href="mailto:{{ config('kidzio.support_email') }}?subject={{ rawurlencode(__('support.privacy_subject')) }}" class="setting-row">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-shield-check"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('support.privacy_email') }}</p>
          <p class="text-[11px] text-muted">{{ config('kidzio.support_email') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      <a href="mailto:{{ config('kidzio.support_email') }}?subject={{ rawurlencode(__('support.press_subject')) }}" class="setting-row">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-newspaper"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('support.press_email') }}</p>
          <p class="text-[11px] text-muted">{{ config('kidzio.support_email') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
    </div>
  </section>

  <section class="px-5 mt-5 mb-10">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('support.note_title') }}</p>
        <p class="text-xs text-muted">{{ __('support.note_body') }}</p>
      </div>
    </div>
  </section>
</main>
