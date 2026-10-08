@extends('layouts.web.default')

@section('content')
    <section class="about_section layout_padding">
        <div class="container  ">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-box">
                        <div class="heading_container">
                            <h2>
                                Về Hosttist
                            </h2>
                        </div>
                        <p>
                            Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL, giúp bạn xây dựng và vận hành website, ứng dụng trên Internet. Liên hệ admin@hosttist.com để được tư vấn dịch vụ phù hợp. </p>
                        <a href="{{ route('services.index') }}">
                            Khám phá dịch vụ
                        </a>
                    </div>
                </div>
                <div class="col-md-6 ">
                    <div class="img-box">
                        <img src="{{ asset('assets/web/hostit/images/about-img.png') }}" alt="Dịch vụ hosting và VPS Hosttist">
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection
