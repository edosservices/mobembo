@php($community = \App\Models\PlatformSetting::current())
@if (filled($community->whatsapp_url) || filled($community->telegram_url))
    <div class="community-links">
        @if (filled($community->whatsapp_url))
            <a class="btn-z-ghost small {{ $light ?? '' }}" href="{{ $community->whatsapp_url }}" target="_blank" rel="noopener noreferrer">Rejoindre WhatsApp</a>
        @endif
        @if (filled($community->telegram_url))
            <a class="btn-z-ghost small {{ $light ?? '' }}" href="{{ $community->telegram_url }}" target="_blank" rel="noopener noreferrer">Rejoindre Telegram</a>
        @endif
    </div>
@endif
