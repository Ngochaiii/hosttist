@extends('layouts.admin.index')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Quản lý Users</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item active">Users</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Danh sách tất cả Users</h3>
          </div>
          
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>SĐT</th>
                    <th>Avatar</th>
                    <th>Địa chỉ</th>
                    <th>Role</th>
                    <th>Trạng thái</th>
                    <th>Email verified</th>
                    <th>Đăng nhập cuối</th>
                    <th>Tạo lúc</th>
                    <th>Cập nhật</th>
                    <th>Xóa lúc</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($users as $user)
                  <tr class="{{ $user->deleted_at ? 'table-danger' : '' }}">
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->username ?? 'N/A' }}</td>
                    <td>
                      <div class="password-cell">
                        <div id="pass-display-{{ $user->id }}">
                          <!-- Hiển thị hash hiện tại -->
                          <small class="text-muted">
                            <i class="fas fa-lock"></i> Hash: {{ substr($user->password, 0, 15) }}...
                          </small>
                          <br>
                          <div class="default-pass-info">
                            <small class="text-info">
                              <i class="fas fa-info-circle"></i> Pass mặc định: 
                              <code class="copy-default" style="cursor: pointer; background: #e9ecef; padding: 2px 4px; border-radius: 3px;">
                                hosttist.123
                              </code>
                            </small>
                          </div>
                        </div>
                        
                        <div class="mt-2">
                          <button class="btn btn-sm btn-warning" onclick="resetToDefault({{ $user->id }}, '{{ $user->name }}')">
                            <i class="fas fa-undo"></i> Reset Pass
                          </button>
                        </div>
                      </div>
                    </td>
                    <td>{{ $user->phone ?? 'N/A' }}</td>
                    <td>
                      @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%;">
                      @else
                        N/A
                      @endif
                    </td>
                    <td>{{ $user->address ?? 'N/A' }}</td>
                    <td>
                      @if($user->role == 'super_admin')
                        <span class="badge badge-danger">Super Admin</span>
                      @elseif($user->role == 'admin')
                        <span class="badge badge-warning">Admin</span>
                      @else
                        <span class="badge badge-info">User</span>
                      @endif
                    </td>
                    <td>
                      @if($user->is_active)
                        <span class="badge badge-success">Active</span>
                      @else
                        <span class="badge badge-secondary">Inactive</span>
                      @endif
                    </td>
                    <td>
                      @if($user->email_verified_at)
                        <span class="badge badge-success">Verified</span>
                        <br><small>{{ $user->email_verified_at->format('d/m/Y H:i') }}</small>
                      @else
                        <span class="badge badge-warning">Chưa xác thực</span>
                      @endif
                    </td>
                    <td>
                      {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Chưa đăng nhập' }}
                    </td>
                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                    <td>
                      @if($user->deleted_at)
                        <span class="text-danger">{{ $user->deleted_at->format('d/m/Y H:i') }}</span>
                      @else
                        <span class="text-success">Chưa xóa</span>
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="15" class="text-center">Không có dữ liệu</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            
            <div class="mt-3">
              <p><strong>Tổng số users:</strong> {{ $users->count() }}</p>
              <p><strong>Users đang hoạt động:</strong> {{ $users->where('is_active', true)->where('deleted_at', null)->count() }}</p>
              <p><strong>Users đã bị xóa:</strong> {{ $users->whereNotNull('deleted_at')->count() }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.table td {
  vertical-align: middle;
  font-size: 14px;
}
.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
.table-responsive {
  max-height: 600px;
}
.password-cell {
  min-width: 180px;
}
.copy-default {
  transition: all 0.2s ease;
}
.copy-default:hover {
  background: #007bff !important;
  color: white !important;
}
.default-pass-info {
  background: #f8f9fa;
  padding: 4px;
  border-radius: 4px;
  border-left: 3px solid #17a2b8;
}
.reset-success {
  background: #d4edda;
  border-left-color: #28a745 !important;
}
</style>

<script>
function resetToDefault(userId, userName) {
  if (!confirm(`🔄 Reset password về mặc định cho: ${userName}?\n\n🔑 Password sẽ được đặt lại thành: hosttist.123`)) {
    return;
  }
  
  // Hiển thị loading
  const passDisplay = $(`#pass-display-${userId}`);
  const originalContent = passDisplay.html();
  passDisplay.html(`
    <div class="text-center">
      <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
      <br><small>Đang reset password...</small>
    </div>
  `);
  
  // Gửi request reset password - Sửa URL cho đúng với route thực tế
  $.ajax({
    url: `/admin/users/${userId}/reset-password`,  // Đã sửa lại đúng với route thực tế
    type: 'POST',
    data: {
      _token: '{{ csrf_token() }}'
    },
    success: function(response) {
      console.log('Success:', response);
      
      if (response.success) {
        // Hiển thị kết quả thành công
        passDisplay.html(`
          <small class="text-muted">
            <i class="fas fa-lock"></i> Hash: Đã cập nhật mới...
          </small>
          <br>
          <div class="default-pass-info reset-success">
            <small class="text-success">
              <i class="fas fa-check-circle"></i> Reset thành công! Pass: 
              <code class="copy-default" style="cursor: pointer; background: #28a745; color: white; padding: 2px 4px; border-radius: 3px;">
                hosttist.123
              </code>
            </small>
            <br>
            <small class="text-muted">
              <i class="fas fa-clock"></i> ${response.reset_time}
            </small>
          </div>
        `);
        
        // Auto copy password mặc định
        copyToClipboard('hosttist.123', userId);
        
        // Sau 5 giây thì đổi về trạng thái bình thường
        setTimeout(() => {
          passDisplay.html(`
            <small class="text-muted">
              <i class="fas fa-lock"></i> Hash: Đã cập nhật...
            </small>
            <br>
            <div class="default-pass-info">
              <small class="text-info">
                <i class="fas fa-info-circle"></i> Pass mặc định: 
                <code class="copy-default" style="cursor: pointer; background: #e9ecef; padding: 2px 4px; border-radius: 3px;">
                  hosttist.123
                </code>
              </small>
            </div>
          `);
        }, 5000);
        
      } else {
        passDisplay.html(originalContent);
        alert('❌ Lỗi: ' + response.message);
      }
    },
    error: function(xhr, status, error) {
      console.error('Error details:', {
        status: xhr.status,
        statusText: xhr.statusText,
        responseText: xhr.responseText,
        error: error
      });
      
      passDisplay.html(originalContent);
      
      let errorMessage = 'Không thể reset password';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.status === 404) {
        errorMessage = 'Không tìm thấy user';
      } else if (xhr.status === 500) {
        errorMessage = 'Lỗi server';
      } else if (xhr.status === 419) {
        errorMessage = 'CSRF token hết hạn, vui lòng refresh trang';
      }
      
      alert('❌ Lỗi: ' + errorMessage);
    }
  });
}

function copyToClipboard(text, userId) {
  navigator.clipboard.writeText(text).then(function() {
    // Hiển thị thông báo copy thành công
    const notice = $(`
      <div class="alert alert-success alert-dismissible fade show mt-2" role="alert" style="font-size: 12px;">
        <i class="fas fa-copy"></i> Password "hosttist.123" đã copy vào clipboard!
        <button type="button" class="close" data-dismiss="alert" style="font-size: 12px;">
          <span>&times;</span>
        </button>
      </div>
    `);
    
    $(`#pass-display-${userId}`).append(notice);
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => notice.alert('close'), 3000);
  }).catch(function() {
    // Fallback cho browser không support clipboard API
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    alert('Password đã được copy: ' + text);
  });
}

// Click vào code để copy password mặc định
$(document).on('click', '.copy-default', function() {
  const password = 'hosttist.123';
  const $this = $(this);
  const originalStyle = $this.attr('style');
  
  navigator.clipboard.writeText(password).then(() => {
    $this.css('background', '#28a745').css('color', 'white').text('Copied!');
    setTimeout(() => {
      $this.attr('style', originalStyle).text('hosttist.123');
    }, 1500);
  }).catch(() => {
    // Fallback
    const textArea = document.createElement('textarea');
    textArea.value = password;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    $this.css('background', '#28a745').css('color', 'white').text('Copied!');
    setTimeout(() => {
      $this.attr('style', originalStyle).text('hosttist.123');
    }, 1500);
  });
});

// Debug: Kiểm tra CSRF token
console.log('CSRF Token:', '{{ csrf_token() }}');
</script>
@endsection