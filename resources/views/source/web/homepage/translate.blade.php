<!-- Language Switcher Button -->
<div class="language-switcher">
    <button class="lang-btn" id="langBtn">
        <i class="fas fa-globe"></i>
        <span id="currentLang">VI</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="lang-dropdown" id="langDropdown">
        <a href="#" data-lang="vi" class="lang-option active">
            <img src="https://flagcdn.com/w20/vn.png" alt="Vietnamese">
            Tiếng Việt
        </a>
        <a href="#" data-lang="en" class="lang-option">
            <img src="https://flagcdn.com/w20/gb.png" alt="English">
            English
        </a>
        <a href="#" data-lang="ja" class="lang-option">
            <img src="https://flagcdn.com/w20/jp.png" alt="Japanese">
            日本語
        </a>
        <a href="#" data-lang="ko" class="lang-option">
            <img src="https://flagcdn.com/w20/kr.png" alt="Korean">
            한국어
        </a>
        <a href="#" data-lang="zh-CN" class="lang-option">
            <img src="https://flagcdn.com/w20/cn.png" alt="Chinese">
            中文
        </a>
    </div>
</div>

<!-- Google Translate Element (ẩn) -->
<div id="google_translate_element" style="display:none;"></div>

<style>
.language-switcher {
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 9999;
}

.lang-btn {
    background: white;
    border: 2px solid #4154f1;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #4154f1;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.lang-btn:hover {
    background: #4154f1;
    color: white;
}

.lang-btn i.fa-globe {
    font-size: 18px;
}

.lang-btn i.fa-chevron-down {
    font-size: 12px;
}

.lang-dropdown {
    position: absolute;
    top: 50px;
    right: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.lang-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.lang-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s;
    border-bottom: 1px solid #f0f0f0;
}

.lang-option:first-child {
    border-radius: 10px 10px 0 0;
}

.lang-option:last-child {
    border-bottom: none;
    border-radius: 0 0 10px 10px;
}

.lang-option:hover {
    background: #f8f9fa;
}

.lang-option.active {
    background: #e3f2fd;
    color: #4154f1;
    font-weight: 600;
}

.lang-option img {
    width: 20px;
    height: auto;
}

/* Ẩn Google Translate UI mặc định */
.goog-te-banner-frame.skiptranslate {
    display: none !important;
}

.goog-te-gadget-icon {
    display: none !important;
}

body {
    top: 0px !important;
}

.skiptranslate {
    display: none !important;
}

/* Loading indicator */
.lang-loading {
    pointer-events: none;
    opacity: 0.6;
}

.lang-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #4154f1;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .language-switcher {
        top: 80px;
        right: 10px;
    }
}
</style>

<script type="text/javascript">
// Biến global để check Google Translate đã load chưa
window.googleTranslateLoaded = false;

// Initialize Google Translate
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'vi',
        includedLanguages: 'en,vi,ja,ko,zh-CN',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
    
    // Đánh dấu đã load xong
    window.googleTranslateLoaded = true;
    console.log('Google Translate loaded successfully');
}

// Custom Language Switcher
document.addEventListener('DOMContentLoaded', function() {
    const langBtn = document.getElementById('langBtn');
    const langDropdown = document.getElementById('langDropdown');
    const currentLang = document.getElementById('currentLang');
    const langOptions = document.querySelectorAll('.lang-option');

    // Toggle dropdown
    if (langBtn) {
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            langDropdown.classList.toggle('active');
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.language-switcher')) {
            langDropdown.classList.remove('active');
        }
    });

    // Handle language selection
    langOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const lang = this.getAttribute('data-lang');
            const langText = this.textContent.trim();
            
            console.log('Language selected:', lang);
            
            // Show loading
            langBtn.classList.add('lang-loading');
            
            // Update active state
            langOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Update button text
            currentLang.textContent = lang.toUpperCase();
            
            // Close dropdown
            langDropdown.classList.remove('active');
            
            // Save preference
            localStorage.setItem('preferred_lang', lang);
            
            // Trigger translation với retry
            translateToLanguage(lang);
        });
    });

    // Auto-translate based on saved preference
    const savedLang = localStorage.getItem('preferred_lang');
    if (savedLang && savedLang !== 'vi') {
        // Đợi Google Translate load xong
        setTimeout(() => {
            translateToLanguage(savedLang);
            // Update UI
            langOptions.forEach(opt => {
                if (opt.getAttribute('data-lang') === savedLang) {
                    opt.classList.add('active');
                    currentLang.textContent = savedLang.toUpperCase();
                } else {
                    opt.classList.remove('active');
                }
            });
        }, 2000);
    }
});

// Function để trigger translation với retry mechanism
function translateToLanguage(lang) {
    let attempts = 0;
    const maxAttempts = 10;
    
    const tryTranslate = setInterval(function() {
        attempts++;
        
        // Tìm select element của Google Translate
        const selectElement = document.querySelector('.goog-te-combo');
        
        if (selectElement) {
            console.log('Found Google Translate element, translating to:', lang);
            selectElement.value = lang;
            selectElement.dispatchEvent(new Event('change'));
            
            // Remove loading
            const langBtn = document.getElementById('langBtn');
            if (langBtn) {
                langBtn.classList.remove('lang-loading');
            }
            
            clearInterval(tryTranslate);
        } else if (attempts >= maxAttempts) {
            console.error('Google Translate not loaded after', maxAttempts, 'attempts');
            
            // Remove loading
            const langBtn = document.getElementById('langBtn');
            if (langBtn) {
                langBtn.classList.remove('lang-loading');
            }
            
            alert('Không thể tải công cụ dịch. Vui lòng tải lại trang.');
            clearInterval(tryTranslate);
        } else {
            console.log('Waiting for Google Translate... Attempt:', attempts);
        }
    }, 500); // Check mỗi 500ms
}
</script>

<!-- Load Google Translate Script -->
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>