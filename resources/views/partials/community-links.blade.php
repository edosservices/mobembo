@php($community = \App\Models\PlatformSetting::current())
@if (filled($community->whatsapp_url) || filled($community->telegram_url))
    <div class="footer-social">
        @if (filled($community->whatsapp_url))
            <a class="social-whatsapp" href="{{ $community->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="Rejoindre WhatsApp">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.5 3.5A11 11 0 0 0 2.1 16.8L1 23l6.4-1.1A11 11 0 0 0 20.5 3.5Zm-8.5 17a9.1 9.1 0 0 1-4.6-1.3l-.3-.2-3.8.6.6-3.7-.2-.3A9.1 9.1 0 1 1 12 20.5Zm5-6.8c-.3-.1-1.6-.8-1.8-.9s-.4-.1-.6.1-.7.9-.8 1-.3.2-.6.1a7.4 7.4 0 0 1-2.2-1.4 8.2 8.2 0 0 1-1.5-1.9c-.2-.3 0-.4.1-.6l.4-.5.2-.3a.5.5 0 0 0 0-.5c0-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4c.6.2 1.1.4 1.5.5a3.6 3.6 0 0 0 1.6.1 2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .2-1.2c-.1-.1-.3-.2-.6-.3Z"/></svg>
            </a>
        @endif
        @if (filled($community->telegram_url))
            <a class="social-telegram" href="{{ $community->telegram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Rejoindre Telegram">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21.5 4.4 18.3 20c-.2 1-.8 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.2.3-.5.5-.9.5l.3-4.6 8.4-7.6c.4-.3-.1-.5-.6-.2L6.5 13.1 2.1 11.7c-1-.3-1-.9.2-1.4L20.1 3.1c.8-.3 1.5.2 1.4 1.3Z"/></svg>
            </a>
        @endif
    </div>
@endif
