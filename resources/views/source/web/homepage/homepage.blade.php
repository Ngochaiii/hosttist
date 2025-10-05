@extends('layouts.web.index')

@section('content')
    <section class="service_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Our Services
                </h2>
            </div>
        </div>
        <div class="container">
            <div class="row">
                @forelse($services as $category)
                    <div class="col-md-6 col-lg-4">
                        <div class="box">
                            <div class="img-box">
                            </div>
                            <div class="detail-box">
                                <h4>{{ $category->name }}</h4>
                                <p>
                                    @if ($category->description)
                                        {!! Str::limit(strip_tags($category->description), 150) !!}
                                    @else
                                        Dịch vụ {{ $category->name }}
                                    @endif
                                </p>
                                <a href="{{ route('pricing.index') }}">
                                    Xem thêm
                                    <i class="fa fa-long-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-md-6 col-lg-4">
                        <div class="box">
                            <div class="img-box"></div>
                            <div class="detail-box">
                                <h4>Web Hosting</h4>
                                <p>Dịch vụ web hosting chuyên nghiệp</p>
                                <a href="#">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- end service section -->
    <!-- Premium Web Design Section -->
    <section class="web_design_section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="design_content">
                        <span class="premium_badge">Premium Service</span>
                        <h2 class="section_title">
                            Thiết Kế Website<br>
                            <span class="highlight_text">Chuyên Nghiệp Theo Yêu Cầu</span>
                        </h2>
                        <p class="section_desc">
                            Từ ý tưởng đến website hoàn chỉnh. Chúng tôi xây dựng giải pháp web
                            tùy chỉnh hoàn toàn phù hợp với nhu cầu kinh doanh của bạn.
                        </p>

                        <div class="tech_stack">
                            <h4>Công Nghệ Sử Dụng:</h4>
                            <div class="tech_items">
                                <div class="tech_item">
                                    <i class="fab fa-wordpress"></i>
                                    <span>WordPress</span>
                                </div>
                                <div class="tech_item">
                                    <i class="fab fa-laravel"></i>
                                    <span>Laravel</span>
                                </div>
                                <div class="tech_item">
                                    <i class="fab fa-html5"></i>
                                    <span>HTML/CSS/JS</span>
                                </div>
                                <div class="tech_item">
                                    <i class="fas fa-server"></i>
                                    <span>aaPanel</span>
                                </div>
                            </div>
                        </div>

                        <div class="service_features">
                            <div class="feature_item">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Thiết Kế Responsive</strong>
                                    <p>Hoạt động mượt mà trên mọi thiết bị</p>
                                </div>
                            </div>
                            <div class="feature_item">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Tối Ưu SEO</strong>
                                    <p>Chuẩn SEO, tốc độ load nhanh</p>
                                </div>
                            </div>
                            <div class="feature_item">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Bảo Mật Cao</strong>
                                    <p>SSL, firewall, backup tự động</p>
                                </div>
                            </div>
                            <div class="feature_item">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Deploy Lên Server</strong>
                                    <p>Cấu hình hosting, domain, email đầy đủ</p>
                                </div>
                            </div>
                        </div>

                        <div class="cta_buttons">
                            <a href="#" class="btn_primary">
                                <i class="fas fa-rocket"></i>
                                Bắt Đầu Dự Án
                            </a>
                            <a href="#" class="btn_secondary">
                                <i class="fas fa-phone-alt"></i>
                                Tư Vấn Miễn Phí
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="design_showcase">
                        <div class="showcase_card main_card">
                            <div class="card_header">
                                <div class="browser_dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                            <div class="card_body">
                                <div class="code_preview">
                                    <div class="code_line"><span class="tag">&lt;html&gt;</span></div>
                                    <div class="code_line indent"><span class="tag">&lt;body&gt;</span></div>
                                    <div class="code_line indent2"><span class="comment">// Your Dream Website</span></div>
                                    <div class="code_line indent2"><span class="function">buildWebsite</span><span
                                            class="bracket">()</span></div>
                                    <div class="code_line indent"><span class="tag">&lt;/body&gt;</span></div>
                                    <div class="code_line"><span class="tag">&lt;/html&gt;</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="floating_badge badge_1">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Responsive</span>
                        </div>
                        <div class="floating_badge badge_2">
                            <i class="fas fa-rocket"></i>
                            <span>Fast Loading</span>
                        </div>
                        <div class="floating_badge badge_3">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Process Timeline -->
            <div class="process_timeline">
                <h3 class="text-center mb-5">Quy Trình Làm Việc</h3>
                <div class="timeline_container">
                    <div class="timeline_item">
                        <div class="timeline_number">1</div>
                        <div class="timeline_content">
                            <h4>Tư Vấn & Báo Giá</h4>
                            <p>Trao đổi ý tưởng, phân tích yêu cầu</p>
                        </div>
                    </div>
                    <div class="timeline_item">
                        <div class="timeline_number">2</div>
                        <div class="timeline_content">
                            <h4>Thiết Kế Giao Diện</h4>
                            <p>Mockup, wireframe, UI/UX design</p>
                        </div>
                    </div>
                    <div class="timeline_item">
                        <div class="timeline_number">3</div>
                        <div class="timeline_content">
                            <h4>Lập Trình & Test</h4>
                            <p>Code, kiểm thử, tối ưu hiệu năng</p>
                        </div>
                    </div>
                    <div class="timeline_item">
                        <div class="timeline_number">4</div>
                        <div class="timeline_content">
                            <h4>Deploy & Bàn Giao</h4>
                            <p>Đưa lên server, hướng dẫn sử dụng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- about section -->

    <section class="about_section layout_padding-bottom">
        <div class="container  ">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-box">
                        <div class="heading_container">
                            <h2>
                                About Our Hosting Company
                            </h2>
                        </div>
                        <p>
                            Founded with a passion for web technology and customer service, our hosting company has grown to
                            become a
                            trusted provider of digital infrastructure solutions. We pride ourselves on delivering reliable,
                            high-performance hosting services that empower businesses of all sizes to establish and expand
                            their
                            online presence.<br>
                            Our team consists of experienced IT professionals dedicated to ensuring your websites and
                            applications run
                            smoothly 24/7. We've invested in state-of-the-art data centers, cutting-edge technologies, and
                            robust
                            security systems to provide you with hosting solutions that meet the highest industry standards.
                            What sets us apart is our commitment to personalized support. We understand that every client
                            has unique
                            needs, which is why we offer customized hosting packages ranging from shared hosting for
                            startups to
                            dedicated servers for enterprise-level operations.<br>
                            As we continue to evolve with the digital landscape, we remain focused on our core mission:
                            providing you
                            with the technological foundation you need to succeed online while delivering exceptional value
                            and
                            service. </p>
                        <a href="">
                            Read More
                        </a>
                    </div>
                </div>
                <div class="col-md-6 ">
                    <div class="img-box">
                        <img src="images/about-img.png" alt="">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- end about section -->


    <!-- server section -->

    <section class="server_section">
        <div class="container ">
            <div class="row">
                <div class="col-md-6">
                    <div class="img-box">
                        <img src="{{ asset('assets/web/hostit/images/server-img.jpg') }}" alt="">
                        <div class="play_btn">
                            <button>
                                <i class="fa fa-play" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-box">
                        <div class="heading_container">
                            <h2>
                                Website Services Overview
                            </h2>
                            <p>
                                Our website offers comprehensive hosting solutions to power your online presence. From
                                shared hosting
                                for small websites to powerful dedicated servers for business applications, we provide a
                                range of
                                options tailored to your needs. Our services include shared hosting, dedicated hosting,
                                cloud hosting,
                                VPS solutions, WordPress-optimized environments, and domain registration services. Each
                                package comes
                                with reliable support, security features, and the performance you need to succeed online.
                                Let us handle
                                the technical details while you focus on growing your business.
                            </p>
                        </div>
                        <a href="">
                            Read More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- end server section -->
    @include('source.web.homepage.our-hosting')
    {{-- tính năng nổi bật  --}}
    <div class="section-internal-feature-sever">
        <div class="container">
            <h2 class="title-section text-center">
                <span class="slogan-section text-center d-block">Outstanding Features</span>
                When Buying Affordable VPS Vietnam at HOSTIST
            </h2>

            <!-- OpenStack Cloud VPS Technology -->
            <div class="content-internal-feature-sever">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="img position-relative">
                            <img class="rtbs" src="https://nhanhoa.com/templates/images/v2/vps/vps-cau-hinh-manh-me.png"
                                alt="Superior Technology">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="content">
                            <h3 class="title-section text-left">Superior OpenStack Cloud VPS Technology</h3>
                            <div class="info">
                                <p>OpenStack, combined with CEPH – advanced storage solution. This is a core technology
                                    trusted by major global technology corporations such as IBM, Cisco, Dell, HP, Red Hat,
                                    OVH and Rackspace. Therefore, Vietnam VPS rental service at HOSTIST always ensures high
                                    performance, stability and reliability, meeting storage needs and operating system of
                                    every business of all sizes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Powerful VPS Cloud Infrastructure -->
            <div class="content-internal-feature-sever">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="content content-text-right">
                            <h3 class="title-section text-left">Powerful VPS Cloud Infrastructure</h3>
                            <div class="info">
                                <p>With powerful server infrastructure from Dell, IBM, Cisco and HP combined with Enterprise
                                    SSD storage solutions, HOSTIST is committed to delivering professional virtual server
                                    services that meet international standards. HOSTIST system operates 24/7 stably,
                                    ensuring customer data is always secure and protected.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="img position-relative">
                            <img class="rtbs" src="https://nhanhoa.com/templates/images/v2/vps/vps-thiet-ke-toi-uu.png"
                                alt="Powerful Infrastructure">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TIER 3 Standard Datacenter -->
            <div class="content-internal-feature-sever">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="img position-relative">
                            <img class="rtbs" src="https://nhanhoa.com/templates/images/v2/vps/vps-backup-hang-tuan.png"
                                alt="TIER 3 Standard Datacenter">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="content">
                            <h3 class="title-section text-left">TIER 3 Standard Virtual Server Datacenter</h3>
                            <div class="info">
                                <p>With server systems located at leading Datacenters such as Viettel, FPT, CMC,... When
                                    renting affordable VPS from HOSTIST, customers can easily choose the location for their
                                    Cloud VPS server, ensuring proximity to their target customers, thereby reducing latency
                                    and increasing access speed, enhancing customer experience.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maximum Uptime & Safe Redundancy -->
            <div class="content-internal-feature-sever">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="content content-text-right">
                            <h3 class="title-section text-left">Maximum Uptime & Safe Redundancy</h3>
                            <div class="info">
                                <p>VPS Cloud system applies N+1 redundant architecture for all network equipment and
                                    servers, with high availability and quick recovery capability in case of incidents.
                                    Proactive monitoring mechanisms help detect and quickly resolve issues, minimizing Cloud
                                    VPS service downtime to the maximum extent.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="img position-relative">
                            <img class="rtbs" src="https://nhanhoa.com/templates/images/v2/vps/vps-ipv4-ipv6.png"
                                alt="Safe Redundancy - Maximum Uptime">
                        </div>
                    </div>
                </div>
            </div>

            <!-- High Flexibility -->
            <div class="content-internal-feature-sever">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="img position-relative">
                            <img class="rtbs" src="https://nhanhoa.com/images/vps/tinh-linh-hoat-cao.png"
                                alt="High Availability">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="content">
                            <h3 class="title-section text-left">High Flexibility</h3>
                            <div class="info">
                                <p>With an intuitive management interface, you can easily customize Cloud VPS virtual server
                                    configurations even without extensive technical expertise. Scaling up or down resources
                                    becomes simpler, helping you flexibly meet changing business needs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Kết thúc tính năng nổi bật  --}}
    {{-- ly do chọn  --}}

    <section class="section-why-cloud">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="main-title">Why You Should Rent Affordable Cloud VPS in HOSTIST</h1>
                </div>
            </div>

            <div class="row g-4">
                <!-- Quick Setup -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h3 class="feature-title text-center">Quick Setup in 5 Minutes</h3>
                        <p class="feature-text">
                            With pre-installed templates and applications, you can activate and set up Cloud VPS in just 5
                            minutes. Based on a website template, you can go live immediately.
                        </p>
                    </div>
                </div>

                <!-- High Performance -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h3 class="feature-title text-center">High-Performance Host Infrastructure</h3>
                        <p class="feature-text">
                            HOSTIST develops VPS Cloud on high-performance host clusters, using equipment from leading
                            brands like IBM, DELL, CISCO, HP and located in Vietnam's leading TIER3 standard data centers.
                        </p>
                    </div>
                </div>

                <!-- High Performance Specs -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h3 class="feature-title text-center">High Performance</h3>
                        <p class="feature-text">
                            Servers equipped with the same SSD Enterprise storage, combined with high-speed internet
                            connection, ensuring stable service operation and bringing optimal experience to users.
                        </p>
                    </div>
                </div>

                <!-- Multi-layer Security -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="feature-title text-center">Multi-layer Security</h3>
                        <p class="feature-text">
                            Servers are protected by two layers of firewall: Datacenter firewall and integrated Cloud
                            platform firewall, preventing most external threats from DDoS attacks.
                        </p>
                    </div>
                </div>

                <!-- Smart Management Software -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card highlight-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-toggle-on"></i>
                        </div>
                        <h3 class="feature-title text-center">Smart Management Software</h3>
                        <p class="feature-text">
                            Direct Cloud VPS management interface allows you to easily perform operations and manage your
                            server efficiently through an intuitive process.
                        </p>
                    </div>
                </div>

                <!-- 24/7/365 Support -->
                <div class="col-lg-4 col-md-4 col-6 mb-3">
                    <div class="feature-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="feature-title text-center">24/7/365 Support</h3>
                        <p class="feature-text">
                            HOSTIST technical team operates continuously and is always ready to support customers
                            24/7/365. We solve every issue to ensure the best experience with Cloud VPS service.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- kết thúc section  --}}
    <!-- client section -->
    <section class="client_section ">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Testimonial
                </h2>
                <p>
                    Even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to
                </p>
            </div>
        </div>
        <div class="container px-0">
            <div id="customCarousel2" class="carousel  slide" data-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-10 mx-auto">
                                    <div class="box">
                                        <div class="img-box">
                                            <img src="{{ asset('assets/web/hostit/images/client.jpg') }}" alt="">
                                        </div>
                                        <div class="detail-box">
                                            <div class="client_info">
                                                <div class="client_name">
                                                    <h5>
                                                        Morojink
                                                    </h5>
                                                    <h6>
                                                        Customer
                                                    </h6>
                                                </div>
                                                <i class="fa fa-quote-left" aria-hidden="true"></i>
                                            </div>
                                            <p>
                                                I've been using this hosting service for my e-commerce business for over two
                                                years now, and I
                                                couldn't be happier with my decision. The performance is outstanding - my
                                                website loads quickly
                                                even during peak traffic times, which has significantly improved my
                                                conversion rates. Their
                                                technical support team deserves special praise for their prompt responses
                                                and expert solutions
                                                whenever I've needed assistance.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-10 mx-auto">
                                    <div class="box">
                                        <div class="img-box">
                                            <img src="{{ asset('assets/web/hostit/images/client.jpg') }}" alt="">
                                        </div>
                                        <div class="detail-box">
                                            <div class="client_info">
                                                <div class="client_name">
                                                    <h5>
                                                        Morojink
                                                    </h5>
                                                    <h6>
                                                        Customer
                                                    </h6>
                                                </div>
                                                <i class="fa fa-quote-left" aria-hidden="true"></i>
                                            </div>
                                            <p>
                                                Their WordPress hosting is top-notch. I’ve been using it for over 6 months
                                                now and haven’t had
                                                any downtime. The dashboard is easy to navigate, and I love the automatic
                                                backups. Very
                                                satisfied!
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-10 mx-auto">
                                    <div class="box">
                                        <div class="img-box">
                                            <img src="{{ asset('assets/web/hostit/images/client.jpg') }}" alt="">
                                        </div>
                                        <div class="detail-box">
                                            <div class="client_info">
                                                <div class="client_name">
                                                    <h5>
                                                        Morojink
                                                    </h5>
                                                    <h6>
                                                        Customer
                                                    </h6>
                                                </div>
                                                <i class="fa fa-quote-left" aria-hidden="true"></i>
                                            </div>
                                            <p>
                                                “We use their VPS hosting for our internal CRM system—super stable and fast.
                                                The tech support
                                                responds quickly and speaks both English and Vietnamese. Highly recommended
                                                for developers or
                                                startups.”
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel_btn-box">
                    <a class="carousel-control-prev" href="#customCarousel2" role="button" data-slide="prev">
                        <i class="fa fa-angle-left" aria-hidden="true"></i>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#customCarousel2" role="button" data-slide="next">
                        <i class="fa fa-angle-right" aria-hidden="true"></i>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- end client section -->
    <!-- FAQ Section -->
    <section class="faq_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>Câu Hỏi Thường Gặp</h2>
                <p>Giải đáp các thắc mắc phổ biến về dịch vụ hosting</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">

                        <!-- Question 1 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading1">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse1" aria-expanded="false">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Tôi cần bao lâu để kích hoạt dịch vụ hosting?</span>
                                </button>
                            </div>
                            <div id="collapse1" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Dịch vụ hosting được kích hoạt tự động ngay sau khi thanh toán thành công.
                                    VPS Cloud có thể setup trong vòng 5 phút. Bạn sẽ nhận email hướng dẫn chi tiết
                                    để bắt đầu sử dụng.
                                </div>
                            </div>
                        </div>

                        <!-- Question 2 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading2">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse2">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Có hỗ trợ chuyển hosting từ nhà cung cấp khác không?</span>
                                </button>
                            </div>
                            <div id="collapse2" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Có, chúng tôi hỗ trợ miễn phí việc chuyển dữ liệu website, database và email
                                    từ nhà cung cấp cũ. Đội ngũ kỹ thuật sẽ đảm bảo quá trình chuyển đổi diễn ra
                                    suôn sẻ không ảnh hưởng đến hoạt động website.
                                </div>
                            </div>
                        </div>

                        <!-- Question 3 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading3">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse3">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Tôi có thể nâng cấp gói hosting bất cứ lúc nào không?</span>
                                </button>
                            </div>
                            <div id="collapse3" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Hoàn toàn có thể! Bạn có thể nâng cấp hoặc hạ cấp gói hosting bất cứ lúc nào
                                    thông qua control panel. Chúng tôi sẽ tính phí theo tỷ lệ thời gian sử dụng
                                    và không mất phí chuyển đổi.
                                </div>
                            </div>
                        </div>

                        <!-- Question 4 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading4">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse4">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Chính sách hoàn tiền như thế nào?</span>
                                </button>
                            </div>
                            <div id="collapse4" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Chúng tôi cam kết hoàn tiền 100% trong vòng 30 ngày đầu tiên nếu bạn không
                                    hài lòng với dịch vụ (áp dụng cho gói shared hosting và VPS). Không cần lý do,
                                    không đặt câu hỏi.
                                </div>
                            </div>
                        </div>

                        <!-- Question 5 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading5">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse5">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Có hỗ trợ kỹ thuật 24/7 không?</span>
                                </button>
                            </div>
                            <div id="collapse5" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Đội ngũ kỹ thuật của HOSTIST làm việc 24/7/365. Bạn có thể liên hệ qua live chat,
                                    email hoặc hotline bất cứ lúc nào. Thời gian phản hồi trung bình dưới 15 phút.
                                </div>
                            </div>
                        </div>

                        <!-- Question 6 -->
                        <div class="faq-item">
                            <div class="faq-header" id="heading6">
                                <button class="faq-button collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapse6">
                                    <i class="fa fa-chevron-down"></i>
                                    <span>Dữ liệu có được backup tự động không?</span>
                                </button>
                            </div>
                            <div id="collapse6" class="collapse" data-parent="#faqAccordion">
                                <div class="faq-body">
                                    Tất cả gói hosting đều có backup tự động hàng ngày và lưu trữ trong 7-30 ngày
                                    tùy gói. Bạn có thể restore dữ liệu bất cứ lúc nào thông qua control panel
                                    hoặc yêu cầu hỗ trợ từ đội ngũ kỹ thuật.
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="text-center mt-5">
                        <p class="faq-footer-text">
                            Không tìm thấy câu trả lời?
                            <a href="#contact_section" class="faq-contact-link">Liên hệ với chúng tôi</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- contact section -->
    <section class="contact_section layout_padding-bottom">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Get In Touch
                </h2>
            </div>
            <div class="row">
                <div class="col-md-8 col-lg-6 mx-auto">
                    <div class="form_container">
                        <form action="">
                            <div>
                                <input type="text" placeholder="Your Name" />
                            </div>
                            <div>
                                <input type="email" placeholder="Your Email" />
                            </div>
                            <div>
                                <input type="text" placeholder="Your Phone" />
                            </div>
                            <div>
                                <input type="text" class="message-box" placeholder="Message" />
                            </div>
                            <div class="btn_box ">
                                <button>
                                    SEND
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end contact section -->

    <!-- info section -->
    @include('source.web.homepage.translate')
@endsection

@push('header_css')
    <style>
        .price_section .price_container .box .detail-box {
            width: 100% !important;
        }

        .price_container .box {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            margin-bottom: 30px;
        }

        .price_container .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .price_container .box.featured {
            border: 2px solid #4154f1;
            transform: scale(1.03);
            z-index: 1;
        }

        .price_container .ribbon {
            position: absolute;
            top: 20px;
            right: -30px;
            transform: rotate(45deg);
            width: 150px;
            background: #4154f1;
            text-align: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .price_container .ribbon span {
            display: block;
            padding: 5px 0;
        }

        .price-badge {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 0 0 50% 50% / 20px;
            margin-bottom: 15px;
        }

        .price-badge h2 {
            font-size: 32px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .price-badge h2 span {
            font-size: 20px;
            font-weight: 600;
        }

        .period-tag {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .original-price {
            font-size: 16px;
            opacity: 0.7;
            margin-top: 5px;
        }

        .package-title {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            margin: 15px 0;
            color: #333;
        }

        .price_features {
            padding: 0 20px;
            margin-bottom: 20px;
        }

        .price_features li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            color: #555;
            display: flex;
            align-items: center;
        }

        .price_features li:last-child {
            border-bottom: none;
        }

        .price_features i {
            color: #10b981;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .btn-box 2 {
            display: flex;
            flex-direction: column;
            padding: 15px 20px;
            background: #f8f9fa;
            gap: 10px;
        }

        .btn-detail,
        .btn-buy {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-detail {
            background: transparent;
            color: #4154f1;
            border: 1px solid #4154f1;
        }

        .btn-buy {
            background: #4154f1;
            color: white;
        }

        .btn-detail:hover {
            background: rgba(65, 84, 241, 0.1);
        }

        .btn-buy:hover {
            background: #2a3bf1;
        }

        @media (max-width: 768px) {
            .price_container .box.featured {
                transform: none;
            }
        }
    </style>
    <!-- CSS Styles for Cloud VPS Features -->
    <style>
        .section-why-cloud {
            background: #f1fafa;
        }

        .feature-card {
            border: 1px solid #4abab9;
            padding: 20px 10px;
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            height: 100%;
            transition: transform 0.3s ease;

        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4abab9, #5ca0b4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-wrapper i {
            font-size: 2rem;
            color: white;
        }

        .highlight-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #4a90a4;
        }

        .main-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 3rem;
        }

        .feature-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-text {
            color: #555;
            line-height: 1.6;
        }
    </style>
    <!-- CSS Styles for VPS Vietnam Features -->
    <style>
        .section-internal-feature-sever {
            background: #F7FCFC;
            padding: 60px 0;
        }

        .title-section {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 3rem;
            line-height: 1.2;
        }

        .slogan-section {
            color: #17a2b8;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-internal-feature-sever {
            margin-bottom: 4rem;
        }

        .content-internal-feature-sever:last-child {
            margin-bottom: 0;
        }

        .content {
            padding: 2rem;
        }

        .content-text-right {
            text-align: left;
        }

        .content .title-section {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: left !important;
        }

        .info p {
            color: #555;
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        .img {
            text-align: center;
            padding: 1rem;
        }

        .img img,
        .rtbs {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .title-section {
                font-size: 1.8rem;
            }

            .content .title-section {
                font-size: 1.5rem;
            }

            .info p {
                font-size: 1rem;
            }

            .content {
                padding: 1.5rem 1rem;
            }
        }

        /* FAQ Section Styles */
        .faq_section {
            background: #ffffff;
        }

        .faq-item {
            margin-bottom: 15px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .faq-header {
            background: #f8f9fa;
        }

        .faq-button {
            width: 100%;
            padding: 20px 25px;
            background: none;
            border: none;
            text-align: left;
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .faq-button:hover {
            color: #4154f1;
        }

        .faq-button i {
            font-size: 14px;
            transition: transform 0.3s ease;
            color: #4154f1;
        }

        .faq-button:not(.collapsed) i {
            transform: rotate(180deg);
        }

        .faq-button span {
            flex: 1;
        }

        .faq-body {
            padding: 20px 25px 20px 54px;
            background: #ffffff;
            color: #666;
            line-height: 1.7;
            font-size: 15px;
        }

        .faq-footer-text {
            font-size: 16px;
            color: #666;
        }

        .faq-contact-link {
            color: #4154f1;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .faq-contact-link:hover {
            color: #2a3bf1;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .faq-button {
                padding: 15px 20px;
                font-size: 15px;
            }

            .faq-body {
                padding: 15px 20px 15px 45px;
                font-size: 14px;
            }
        }

        /* Premium Web Design Section */
        .web_design_section {
            padding: 80px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .web_design_section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            opacity: 0.5;
        }

        .design_content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .premium_badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }

        .section_title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .highlight_text {
            color: #ffd700;
            display: block;
        }

        .section_desc {
            font-size: 18px;
            line-height: 1.7;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .tech_stack {
            margin: 30px 0;
        }

        .tech_stack h4 {
            font-size: 16px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .tech_items {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .tech_item {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .tech_item i {
            font-size: 20px;
        }

        .service_features {
            margin: 30px 0;
        }

        .feature_item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .feature_item i {
            font-size: 24px;
            color: #ffd700;
            flex-shrink: 0;
        }

        .feature_item strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .feature_item p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .cta_buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn_primary,
        .btn_secondary {
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn_primary {
            background: #ffd700;
            color: #667eea;
        }

        .btn_primary:hover {
            background: #ffed4e;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.3);
        }

        .btn_secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn_secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Showcase Card */
        .design_showcase {
            position: relative;
            padding: 40px;
        }

        .showcase_card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }

        .showcase_card:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .card_header {
            background: #2d3748;
            padding: 12px 15px;
        }

        .browser_dots {
            display: flex;
            gap: 8px;
        }

        .browser_dots span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e53e3e;
        }

        .browser_dots span:nth-child(2) {
            background: #ecc94b;
        }

        .browser_dots span:nth-child(3) {
            background: #48bb78;
        }

        .card_body {
            padding: 30px;
            background: #1a202c;
        }

        .code_preview {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }

        .code_line {
            margin-bottom: 8px;
            color: #a0aec0;
        }

        .indent {
            padding-left: 20px;
        }

        .indent2 {
            padding-left: 40px;
        }

        .tag {
            color: #f687b3;
        }

        .comment {
            color: #68d391;
        }

        .function {
            color: #63b3ed;
        }

        .bracket {
            color: #fbd38d;
        }

        /* Floating Badges */
        .floating_badge {
            position: absolute;
            background: white;
            padding: 12px 20px;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #667eea;
            animation: float 3s ease-in-out infinite;
        }

        .badge_1 {
            top: 10%;
            right: 5%;
            animation-delay: 0s;
        }

        .badge_2 {
            top: 50%;
            right: 0;
            animation-delay: 1s;
        }

        .badge_3 {
            bottom: 10%;
            right: 10%;
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        /* Process Timeline */
        .process_timeline {
            margin-top: 80px;
            padding-top: 60px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }

        .process_timeline h3 {
            color: white;
            font-size: 32px;
            font-weight: 700;
        }

        .timeline_container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .timeline_item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .timeline_item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .timeline_number {
            width: 50px;
            height: 50px;
            background: #ffd700;
            color: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        .timeline_content h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .timeline_content p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin: 0;
        }

        @media (max-width: 991px) {
            .section_title {
                font-size: 32px;
            }

            .design_showcase {
                margin-top: 50px;
            }

            .showcase_card {
                transform: none;
            }

            .timeline_container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .timeline_container {
                grid-template-columns: 1fr;
            }

            .cta_buttons {
                flex-direction: column;
            }

            .btn_primary,
            .btn_secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush
