{{-- Render 1 field theo schema. Biến: $field (array), $values (mảng giá trị hiện tại) --}}
@php
    $name = $field['name'];
    $type = $field['type'];
    $cur  = $values[$name] ?? '';
    $isSecret = !empty($field['encrypted']);
    // Field nhạy cảm: không prefill (để trống = giữ nguyên).
    $val = old($name, $isSecret ? '' : $cur);
@endphp

<div class="form-group">
    <label>
        {{ $field['label'] }}
        @if (!empty($field['required'])) <span class="text-danger">*</span> @endif
        @if ($isSecret) <span class="badge badge-warning">mã hóa</span> @endif
    </label>

    @switch($type)
        @case('textarea')
            <textarea name="{{ $name }}" rows="{{ !empty($field['file']) ? 6 : 3 }}" class="form-control"
                      placeholder="{{ $isSecret && $cur !== '' ? '•••••• (đã thiết lập — để trống nếu giữ nguyên)' : '' }}">{{ $val }}</textarea>
            @break

        @case('select')
            <select name="{{ $name }}" class="form-control">
                <option value="">-- Chọn --</option>
                @foreach ($field['options'] ?? [] as $k => $label)
                    <option value="{{ $k }}" {{ (string) $val === (string) $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <div class="custom-control custom-switch">
                <input type="checkbox" name="{{ $name }}" value="1" id="f_{{ $name }}"
                       class="custom-control-input" {{ $val ? 'checked' : '' }}>
                <label class="custom-control-label" for="f_{{ $name }}">Bật</label>
            </div>
            @break

        @case('password')
            <input type="password" name="{{ $name }}" class="form-control" autocomplete="new-password"
                   placeholder="{{ $cur !== '' ? '•••••• (đã thiết lập — để trống nếu giữ nguyên)' : '' }}">
            @break

        @default
            <input type="{{ in_array($type, ['email','url','number','date']) ? $type : 'text' }}"
                   name="{{ $name }}" class="form-control" value="{{ $val }}"
                   {{ !empty($field['readonly']) ? 'readonly' : '' }}>
    @endswitch

    @if (!empty($field['file']))
        <input type="file" name="{{ $name }}_file" class="form-control-file mt-2"
               accept="{{ $field['accept'] ?? '' }}">
        <small class="text-muted d-block">Hoặc tải file lên ({{ $field['accept'] ?? 'mọi định dạng' }}). File ưu tiên hơn nội dung dán.</small>
    @endif

    @if (!empty($field['help']))
        <small class="form-text text-muted">{{ $field['help'] }}</small>
    @endif
</div>
