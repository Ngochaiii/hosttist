{{-- resources/views/source/web/wallet/partials/language_switcher.blade.php --}}
<div class="d-flex justify-content-end mb-3">
    <div class="btn-group">
        <a href="{{ route('deposit') }}?lang=vi" 
           class="btn btn-outline-primary {{ $locale === 'vi' ? 'active' : '' }}">
            🇻🇳 Tiếng Việt
        </a>
        <a href="{{ route('deposit') }}?lang=en" 
           class="btn btn-outline-primary {{ $locale === 'en' ? 'active' : '' }}">
            🇺🇸 English
        </a>
    </div>
</div>