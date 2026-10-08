<section class="info_section layout_padding2">
    <div class="container">
      <div class="row">
        <div class="col-md-3">
          <div class="info_contact">
            <h4>Liên hệ Hosttist</h4>
            <div class="contact_link_box">
              <a href="mailto:admin@hosttist.com">
                <i class="fa fa-envelope" aria-hidden="true"></i>
                <span>admin@hosttist.com</span>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info_link_box">
            <h4>Liên kết</h4>
            <div class="info_links">
              <a href="{{ route('homepage') }}">Trang chủ</a>
              <a href="{{ route('about.index') }}">Giới thiệu</a>
              <a href="{{ route('services.index') }}">Dịch vụ</a>
              <a href="{{ route('pricing.index') }}">Bảng giá</a>
              <a href="{{ route('contact.index') }}">Liên hệ</a>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info_detail">
            <h4>Về Hosttist</h4>
            <p>Hosttist cung cấp dịch vụ hosting, VPS, tên miền và chứng chỉ SSL cho website và ứng dụng của bạn.</p>
          </div>
        </div>
        <div class="col-md-3 mb-0">
          <h4>Tư vấn dịch vụ</h4>
          <p>Liên hệ để được tư vấn lựa chọn dịch vụ phù hợp.</p>
          <a href="mailto:admin@hosttist.com">Gửi email cho Hosttist</a>
        </div>
      </div>
    </div>
</section>
<footer class="footer_section">
    <div class="container">
      <p>&copy; <span id="displayYear"></span> <a href="{{ route('homepage') }}">Hosttist</a>. Bảo lưu mọi quyền.</p>
    </div>
</footer>
