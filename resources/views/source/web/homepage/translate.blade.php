{{-- Custom language switcher — drives Google Translate hidden widget --}}
<div id="language_switcher" class="lang-switcher notranslate" translate="no">
    <button type="button" class="lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-globe"></i>
        <span id="langBtnLabel">VI</span>
        <i class="fa fa-chevron-down lang-caret"></i>
    </button>
    <ul class="lang-menu" id="langMenu" role="menu">
        <li role="menuitem" data-lang="vi">🇻🇳 Tiếng Việt</li>
        <li role="menuitem" data-lang="en">🇬🇧 English</li>
        <li role="menuitem" data-lang="ja">🇯🇵 日本語</li>
        <li role="menuitem" data-lang="ko">🇰🇷 한국어</li>
        <li role="menuitem" data-lang="zh-CN">🇨🇳 中文</li>
    </ul>
</div>

{{-- Google Translate – hidden container; ta dùng combo box bên trong --}}
<div id="google_translate_element" aria-hidden="true"></div>

<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'vi',
            includedLanguages: 'vi,en,ja,ko,zh-CN',
            autoDisplay: false,
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE
        }, 'google_translate_element');
    }

    (function () {
        const LABELS = { vi: 'VI', en: 'EN', ja: 'JA', ko: 'KO', 'zh-CN': 'ZH' };
        const btn   = document.getElementById('langBtn');
        const menu  = document.getElementById('langMenu');
        const label = document.getElementById('langBtnLabel');

        function currentLang() {
            const m = document.cookie.match(/googtrans=\/[^/]+\/([^;]+)/);
            return m ? m[1] : 'vi';
        }

        function setLabel(lang) {
            label.textContent = LABELS[lang] || lang.toUpperCase();
        }

        function waitForCombo(cb, tries = 60) {
            const combo = document.querySelector('.goog-te-combo');
            if (combo) return cb(combo);
            if (tries <= 0) return;
            setTimeout(() => waitForCombo(cb, tries - 1), 150);
        }

        function switchLang(lang) {
            const host = location.hostname;
            const expires = new Date(Date.now() + 365 * 24 * 3600 * 1000).toUTCString();
            // Google Translate sử dụng cookie googtrans để lưu cặp ngôn ngữ.
            const val = '/vi/' + lang;
            document.cookie = `googtrans=${val}; path=/; expires=${expires}`;
            document.cookie = `googtrans=${val}; domain=.${host}; path=/; expires=${expires}`;
            if (lang === 'vi') {
                // Xoá cookie để trở về ngôn ngữ gốc
                document.cookie = 'googtrans=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = `googtrans=; domain=.${host}; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
            }
            location.reload();
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });

        menu.addEventListener('click', function (e) {
            const li = e.target.closest('li[data-lang]');
            if (!li) return;
            e.stopPropagation();
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
            switchLang(li.dataset.lang);
        });

        // Set label ngay từ cookie hiện tại
        setLabel(currentLang());
        // Đảm bảo combo có sẵn (phòng khi user muốn dùng sau này)
        waitForCombo(function () {});
    })();
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>

<style>
    /* ---- Ẩn toàn bộ UI mặc định của Google Translate ---- */
    #google_translate_element,
    .goog-te-gadget,
    .goog-te-banner-frame,
    .goog-te-banner-frame.skiptranslate,
    .skiptranslate > iframe,
    iframe.goog-te-banner-frame,
    .goog-te-gadget-icon,
    .goog-logo-link,
    .goog-te-gadget-simple,
    .VIpgJd-ZVi9od-ORHb-OEVmcd,
    .VIpgJd-ZVi9od-l4eHX-hSRGPd,
    #goog-gt-tt,
    .goog-te-balloon-frame,
    div.goog-te-spinner-pos {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        border: 0 !important;
    }
    /* GT hay đẩy body xuống 40px cho banner – ép về 0 */
    body {
        top: 0 !important;
        position: static !important;
    }
    /* Bỏ highlight vàng/cam khi hover vào text đã dịch */
    .goog-text-highlight {
        background: none !important;
        box-shadow: none !important;
        border: 0 !important;
    }
    font[style*="background-color"], font[style*="vertical-align"] {
        background-color: transparent !important;
        box-shadow: none !important;
    }

    /* ---- Custom language switcher ---- */
    .lang-switcher {
        position: fixed;
        top: 90px;
        right: 20px;
        z-index: 1050;
        font-family: inherit;
    }
    .lang-switcher .lang-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #2c3e50;
        border: 1px solid #e1e4ea;
        border-radius: 999px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        transition: all 0.2s ease;
        line-height: 1;
    }
    .lang-switcher .lang-btn:hover {
        border-color: #4154f1;
        color: #4154f1;
        box-shadow: 0 6px 18px rgba(65, 84, 241, 0.18);
    }
    .lang-switcher .lang-btn .fa-globe {
        color: #4154f1;
        font-size: 15px;
    }
    .lang-switcher .lang-caret {
        font-size: 10px;
        opacity: 0.6;
        transition: transform 0.2s ease;
    }
    .lang-switcher .lang-btn[aria-expanded="true"] .lang-caret {
        transform: rotate(180deg);
    }
    .lang-switcher .lang-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 180px;
        margin: 0;
        padding: 6px;
        list-style: none;
        background: #ffffff;
        border: 1px solid #eef0f4;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        opacity: 0;
        transform: translateY(-6px);
        pointer-events: none;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .lang-switcher .lang-menu.open {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .lang-switcher .lang-menu li {
        padding: 9px 12px;
        border-radius: 8px;
        font-size: 14px;
        color: #2c3e50;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .lang-switcher .lang-menu li:hover {
        background: #f4f6ff;
        color: #4154f1;
    }
    @media (max-width: 576px) {
        .lang-switcher { top: 80px; right: 12px; }
        .lang-switcher .lang-btn { padding: 7px 12px; font-size: 13px; }
    }
</style>
