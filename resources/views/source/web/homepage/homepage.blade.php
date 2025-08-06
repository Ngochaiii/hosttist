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
    </style>
@endpush
