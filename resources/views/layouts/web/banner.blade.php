{{-- Hero banner — dùng chung cho các trang extend layouts/web/index (trang chủ, tìm tên miền, chi tiết dịch vụ) --}}
<section class="hero-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="hero-badge">Cloud VPS · Hosting · SSL · Tên miền</span>
                <h1>
                    Hạ tầng cloud <span>tốc độ cao</span><br>
                    cho website của bạn
                </h1>
                <p class="hero-desc">
                    Kích hoạt trong 5 phút, uptime 99.9%, chống DDoS nhiều lớp và hỗ trợ kỹ thuật 24/7.
                    Tất cả gói dịch vụ đều đi kèm chứng chỉ SSL miễn phí.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('pricing.index') }}" class="hero-btn hero-btn--primary">
                        <i class="fas fa-tags"></i> Xem bảng giá
                    </a>
                    <a href="{{ route('contact.index') }}" class="hero-btn hero-btn--ghost">
                        <i class="fas fa-headset"></i> Tư vấn miễn phí
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>99.9%</strong>
                        <span>Uptime cam kết</span>
                    </div>
                    <div class="hero-stat">
                        <strong>5 phút</strong>
                        <span>Kích hoạt dịch vụ</span>
                    </div>
                    <div class="hero-stat">
                        <strong>24/7</strong>
                        <span>Hỗ trợ kỹ thuật</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-visual">
                    <img src="{{ asset('assets/web/hostit/images/slider-img.png') }}" alt="Hạ tầng cloud HOSTIST">
                </div>
            </div>
        </div>
    </div>
</section>
