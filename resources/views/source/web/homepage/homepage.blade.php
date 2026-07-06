@extends('layouts.web.index')

{{-- Style trang chủ: assets/web/hostit/css/homepage.css (link trong layouts/web/index) --}}

@section('content')
    {{-- Dịch vụ --}}
    <section class="home-section">
        <div class="container">
            <div class="home-section-head">
                <span class="section-badge">Dịch vụ</span>
                <h2>Giải pháp hạ tầng web toàn diện</h2>
                <p>Từ tên miền, hosting đến Cloud VPS và bảo mật SSL — mọi thứ website của bạn cần, tại một nơi.</p>
            </div>

            @php
                $iconMap = [
                    'ssl'            => ['icon' => 'fas fa-shield-alt',   'color' => '#10b981'],
                    'cloud-hosting'  => ['icon' => 'fas fa-cloud',        'color' => '#3b82f6'],
                    'hosting'        => ['icon' => 'fas fa-server',       'color' => '#4154f1'],
                    'domain'         => ['icon' => 'fas fa-globe',        'color' => '#f59e0b'],
                    'design'         => ['icon' => 'fas fa-paint-brush',  'color' => '#8b5cf6'],
                    'anti-ddos'      => ['icon' => 'fas fa-shield-virus', 'color' => '#ef4444'],
                    'vps'            => ['icon' => 'fas fa-microchip',    'color' => '#06b6d4'],
                    'reseller'       => ['icon' => 'fas fa-store',        'color' => '#14b8a6'],
                    'email'          => ['icon' => 'fas fa-envelope',     'color' => '#6366f1'],
                    'backup'         => ['icon' => 'fas fa-hdd',          'color' => '#64748b'],
                ];
                $pickIcon = function ($slug) use ($iconMap) {
                    $slug = strtolower($slug ?? '');
                    if (isset($iconMap[$slug])) return $iconMap[$slug];
                    foreach ($iconMap as $key => $val) {
                        if (str_contains($slug, $key)) return $val;
                    }
                    return ['icon' => 'fas fa-cube', 'color' => '#4154f1'];
                };
            @endphp

            <div class="row services-grid">
                @foreach ($services as $category)
                    @php $meta = $pickIcon($category->slug); @endphp
                    <div class="col-md-6 col-lg-4 d-flex">
                        <div class="service-card w-100">
                            <div class="service-card__icon" style="background: {{ $meta['color'] }}1a; color: {{ $meta['color'] }};">
                                @if ($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}">
                                @else
                                    <i class="{{ $meta['icon'] }}"></i>
                                @endif
                            </div>
                            <div class="service-card__body">
                                <h4>{{ $category->name }}</h4>
                                <p>
                                    @if ($category->description)
                                        {{ Str::limit(trim(html_entity_decode(strip_tags($category->description), ENT_QUOTES, 'UTF-8')), 140) }}
                                    @else
                                        Dịch vụ {{ $category->name }} chất lượng cao, hỗ trợ 24/7.
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('category.detail', $category->slug) }}" class="service-card__link">
                                Xem chi tiết <i class="fa fa-long-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Gói dịch vụ nổi bật --}}
    @include('source.web.homepage.our-hosting')

    {{-- Vì sao chọn HOSTIST --}}
    <section class="home-section">
        <div class="container">
            <div class="home-section-head">
                <span class="section-badge">Vì sao chọn HOSTIST</span>
                <h2>Nền tảng ổn định, hỗ trợ tận tâm</h2>
                <p>Hạ tầng đạt chuẩn quốc tế cùng đội ngũ kỹ thuật luôn sẵn sàng đồng hành với bạn.</p>
            </div>

            @php
                $whyItems = [
                    ['icon' => 'fas fa-bolt',         'title' => 'Kích hoạt trong 5 phút',   'desc' => 'Dịch vụ được kích hoạt tự động ngay sau khi thanh toán, kèm template cài sẵn để lên trang ngay.'],
                    ['icon' => 'fas fa-database',     'title' => 'Hạ tầng chuẩn TIER 3',     'desc' => 'Máy chủ Dell, IBM, HP đặt tại các datacenter Viettel, FPT, CMC — độ trễ thấp, truy cập nhanh.'],
                    ['icon' => 'fas fa-tachometer-alt','title' => 'SSD Enterprise hiệu năng cao', 'desc' => 'Toàn bộ hệ thống dùng ổ SSD Enterprise kết hợp băng thông tốc độ cao, vận hành ổn định 24/7.'],
                    ['icon' => 'fas fa-shield-alt',   'title' => 'Bảo mật nhiều lớp',        'desc' => 'Hai lớp firewall cùng hệ thống chống DDoS giúp chặn đứng hầu hết các mối đe dọa từ bên ngoài.'],
                    ['icon' => 'fas fa-sliders-h',    'title' => 'Quản trị dễ dàng',          'desc' => 'Giao diện quản lý trực quan — nâng/hạ cấu hình, cài lại hệ điều hành chỉ với vài cú nhấp chuột.'],
                    ['icon' => 'fas fa-headset',      'title' => 'Hỗ trợ 24/7/365',           'desc' => 'Đội ngũ kỹ thuật trực liên tục qua live chat, email và hotline. Phản hồi trung bình dưới 15 phút.'],
                ];
            @endphp

            <div class="row why-grid">
                @foreach ($whyItems as $item)
                    <div class="col-md-6 col-lg-4 d-flex">
                        <div class="why-card w-100">
                            <div class="why-card__icon"><i class="{{ $item['icon'] }}"></i></div>
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Câu hỏi thường gặp --}}
    <section class="home-section home-section--alt">
        <div class="container">
            <div class="home-section-head">
                <span class="section-badge">FAQ</span>
                <h2>Câu hỏi thường gặp</h2>
                <p>Giải đáp các thắc mắc phổ biến về dịch vụ của chúng tôi.</p>
            </div>

            @php
                $faqs = [
                    ['q' => 'Tôi cần bao lâu để kích hoạt dịch vụ hosting?', 'a' => 'Dịch vụ hosting được kích hoạt tự động ngay sau khi thanh toán thành công. VPS Cloud có thể setup trong vòng 5 phút. Bạn sẽ nhận email hướng dẫn chi tiết để bắt đầu sử dụng.'],
                    ['q' => 'Có hỗ trợ chuyển hosting từ nhà cung cấp khác không?', 'a' => 'Có, chúng tôi hỗ trợ miễn phí việc chuyển dữ liệu website, database và email từ nhà cung cấp cũ. Đội ngũ kỹ thuật sẽ đảm bảo quá trình chuyển đổi diễn ra suôn sẻ, không ảnh hưởng đến hoạt động website.'],
                    ['q' => 'Tôi có thể nâng cấp gói hosting bất cứ lúc nào không?', 'a' => 'Hoàn toàn có thể! Bạn có thể nâng cấp hoặc hạ cấp gói hosting bất cứ lúc nào thông qua control panel. Chúng tôi tính phí theo tỷ lệ thời gian sử dụng và không mất phí chuyển đổi.'],
                    ['q' => 'Chính sách hoàn tiền như thế nào?', 'a' => 'Chúng tôi cam kết hoàn tiền 100% trong vòng 30 ngày đầu tiên nếu bạn không hài lòng với dịch vụ (áp dụng cho gói shared hosting và VPS). Không cần lý do, không đặt câu hỏi.'],
                    ['q' => 'Có hỗ trợ kỹ thuật 24/7 không?', 'a' => 'Đội ngũ kỹ thuật của HOSTIST làm việc 24/7/365. Bạn có thể liên hệ qua live chat, email hoặc hotline bất cứ lúc nào. Thời gian phản hồi trung bình dưới 15 phút.'],
                    ['q' => 'Dữ liệu có được backup tự động không?', 'a' => 'Tất cả gói hosting đều có backup tự động hàng ngày và lưu trữ trong 7–30 ngày tùy gói. Bạn có thể restore dữ liệu bất cứ lúc nào thông qua control panel hoặc yêu cầu hỗ trợ từ đội ngũ kỹ thuật.'],
                ];
            @endphp

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="faqAccordion">
                        @foreach ($faqs as $i => $faq)
                            <div class="faq-item">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#faq{{ $i }}" aria-expanded="false">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>{{ $faq['q'] }}</span>
                                </button>
                                <div id="faq{{ $i }}" class="collapse" data-parent="#faqAccordion">
                                    <div class="faq-body">{{ $faq['a'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-4">
                        <p class="faq-footer-text">
                            Không tìm thấy câu trả lời?
                            <a href="{{ route('contact.index') }}" class="faq-contact-link">Liên hệ với chúng tôi</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA cuối trang --}}
    <section class="cta-band">
        <div class="container">
            <h2>Sẵn sàng đưa website của bạn lên tầm cao mới?</h2>
            <p>Đăng ký ngay hôm nay — kích hoạt trong 5 phút, hoàn tiền trong 30 ngày nếu không hài lòng.</p>
            <div class="hero-actions">
                <a href="{{ route('pricing.index') }}" class="hero-btn hero-btn--primary">
                    <i class="fas fa-tags"></i> Xem bảng giá
                </a>
                <a href="{{ route('contact.index') }}" class="hero-btn hero-btn--ghost">
                    <i class="fas fa-headset"></i> Liên hệ tư vấn
                </a>
            </div>
        </div>
    </section>

    @include('source.web.homepage.translate')
@endsection
