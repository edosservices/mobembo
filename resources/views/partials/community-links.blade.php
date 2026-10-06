@php($community = \App\Models\PlatformSetting::current())
@php($variant = $variant ?? 'icons')
@if (filled($community->whatsapp_url) || filled($community->telegram_url))
    <div @class([
        'd-flex flex-wrap gap-2',
        'footer-social justify-content-center justify-content-lg-start' => $variant === 'icons',
        'community-actions justify-content-center' => $variant === 'buttons',
    ])>
        @if (filled($community->whatsapp_url))
            <a @class(['social-whatsapp', 'btn btn-outline-dark rounded-pill d-inline-flex align-items-center gap-2' => $variant === 'buttons']) href="{{ $community->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="Rejoindre WhatsApp" title="Rejoindre WhatsApp">
                @include('partials.community-icon', ['network' => 'whatsapp'])
                @if ($variant === 'buttons')
                    <span>WhatsApp</span>
                @endif
            </a>
        @endif
        @if (filled($community->telegram_url))
            <a @class(['social-telegram', 'btn btn-outline-dark rounded-pill d-inline-flex align-items-center gap-2' => $variant === 'buttons']) href="{{ $community->telegram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Rejoindre Telegram" title="Rejoindre Telegram">
                @include('partials.community-icon', ['network' => 'telegram'])
                @if ($variant === 'buttons')
                    <span>Telegram</span>
                @endif
            </a>
        @endif
    </div>
@endif
