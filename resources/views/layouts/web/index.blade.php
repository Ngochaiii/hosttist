<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!-- Site Metas -->
    <link rel="icon" href="{{ asset('assets/web/hostit/images/fevicon.png') }}" type="image/gif" />
    <title>Hostist</title>
    @include('layouts.web.header_css')
    <link href="{{ asset('assets/web/hostit/css/homepage.css') }}" rel="stylesheet" />
    @include('layouts.web.seo_meta')
    @stack('header_css')
</head>

<body>
    <!-- Theme Options -->
    <div class="hero_area">
        @include('layouts.web.header')
        @include('layouts.web.banner')
    </div>
    @yield('content')
    @include('layouts.web.footer')
    @include('layouts.web.footer_js')
    @stack('footer_js')
    <!-- Thêm đoạn này vào các trang của bạn (layouts/web/index.blade.php hoặc partial tương ứng) -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-triangle mr-2"></i> Vui lòng kiểm tra lại thông tin nhập vào
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
</body>

</html>
