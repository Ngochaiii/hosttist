<!-- Viewport và Theme Color -->
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
<meta name="theme-color" content="{{ $config->theme_color ?? '#002ce6' }}">
<meta name="msapplication-navbutton-color" content="{{ $config->theme_color ?? '#002ce6' }}">

<!-- Meta tags cơ bản -->
<meta name="description"
    content="{{ $config->description ?? 'Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng.' }}">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

<!-- Open Graph meta tags -->
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $config->site_name ?? 'Hosttist' }}">
<meta property="og:url" content="{{ $config->url ?? url()->current() }}">
<meta property="og:description"
    content="{{ $config->description ?? 'Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng.' }}">
<meta property="og:site_name" content="{{ $config->site_name ?? 'Hosttist' }}">
<meta property="og:image" content="{{ $config->og_image ?? '/images/default-og-image.jpg' }}">

<!-- Alternate Language -->
<link rel="alternate" hreflang="vi" href="{{ $config->url ?? url()->current() }}">

<!-- Twitter Card meta tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $config->site_name ?? 'Hosttist' }}">
<meta name="twitter:domain" content="{{ $config->domain ?? parse_url(url()->current(), PHP_URL_HOST) }}">
<meta name="twitter:description"
    content="{{ $config->description ?? 'Hosttist cung cấp hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng.' }}">
<meta name="twitter:creator" content="{{ $config->twitter_creator ?? '' }}">

<!-- Social Media meta tags -->
<meta property="article:author" content="{{ $config->facebook_author ?? '' }}">
<meta property="article:publisher" content="{{ $config->facebook_page ?? '' }}">
<meta property="fb:app_id" content="{{ $config->fb_app_id ?? '' }}">
<meta property="fb:admins" content="{{ $config->fb_admin_id ?? '' }}">

<!-- Locale -->
<meta property="og:locale" content="vi_VN">

<!-- Schema.org cho SEO -->
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "WebSite",
    "name": "{{ $config->site_name ?? 'Hosttist' }}",
    "url": "{{ $config->url ?? url()->current() }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "{{ ($config->url ?? url()->current()) }}/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}
</script>
<script defer="defer" type="text/javascript">
    //<![CDATA[
    // Global variables with content
    var monthsName = {!! json_encode(
        $config->months_name ?? [
            'Tháng 1',
            'Tháng 2',
            'Tháng 3',
            'Tháng 4',
            'Tháng 5',
            'Tháng 6',
            'Tháng 7',
            'Tháng 8',
            'Tháng 9',
            'Tháng 10',
            'Tháng 11',
            'Tháng 12',
        ],
    ) !!},
        noThumb = "{{ $config->no_thumb_image ?? '/images/no-thumb.jpg' }}", // Ảnh mặc định khi không có thumbnail
        relatedPostsNum = {{ $config->related_posts_num ?? 3 }}, // Số bài viết liên quan hiển thị
        commentsSystem = "{{ $config->comments_system ?? 'blogger' }}", // Hệ thống comment (blogger/disqus/facebook)
        showMoreText = "{{ $config->show_more_text ?? 'Xem thêm' }}", // Text cho nút "Xem thêm"
        followByEmailText =
        "{{ $config->follow_by_email_text ?? 'Theo dõi qua Email' }}", // Text cho form đăng ký email
        relatedPostsText =
        "{{ $config->related_posts_text ?? 'Bài viết liên quan' }}", // Text cho phần bài viết liên quan
        loadMorePosts = "{{ $config->load_more_text ?? 'Tải thêm bài viết' }}", // Text cho nút tải thêm bài
        postPerPage = {{ $config->posts_per_page ?? 6 }}, // Số bài viết mỗi trang
        pageOfText = {!! json_encode($config->page_of_text ?? ['Trang', 'của']) !!}, // Text cho phân trang
        disqusShortname = "{{ $config->disqus_shortname ?? '' }}"; // Shortname Disqus nếu sử dụng
    //]]>
</script>
@if($config->enable_adsense ?? false)
    <!-- Google Adsense Configuration -->
    <meta name="google-adsense-platform-account" content="{{ $config->adsense_platform_account ?? '' }}">
    <meta name="google-adsense-platform-domain" content="{{ $config->adsense_platform_domain ?? '' }}">

    <script type="text/javascript">
        // Supply ads personalization default
        adsbygoogle = window.adsbygoogle || [];
        if (typeof adsbygoogle.requestNonPersonalizedAds === 'undefined') {
            adsbygoogle.requestNonPersonalizedAds = {{ $config->adsense_non_personalized ?? 1 }};
        }
    </script>
@endif
