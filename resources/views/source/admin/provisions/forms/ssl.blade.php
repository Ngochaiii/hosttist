@extends('source.admin.provisions.forms._base')

@section('form-fields')
    <div class="form-group">
        <label>Domain <span class="text-muted">(from order)</span></label>
        <input type="text" class="form-control" value="{{ $provision->orderItem->domain }}" readonly>
    </div>

    <div class="form-group">
        <label>Certificate <span class="text-danger">*</span></label>
        <textarea name="certificate" class="form-control" rows="8" required placeholder="-----BEGIN CERTIFICATE-----">{{ old('certificate', $provision->provision_data['certificate'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label>Private Key <span class="text-danger">*</span></label>
        <textarea name="private_key" class="form-control" rows="8" required placeholder="-----BEGIN PRIVATE KEY-----">{{ old('private_key') }}</textarea>
        <small class="text-muted">Will be encrypted before saving</small>
    </div>

    <div class="form-group">
        <label>CA Bundle</label>
        <textarea name="ca_bundle" class="form-control" rows="5" placeholder="-----BEGIN CERTIFICATE-----">{{ old('ca_bundle', $provision->provision_data['ca_bundle'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label>SSL Provider</label>
        <select name="ssl_provider" class="form-control">
            <option value="">Select Provider</option>
            <option value="letsencrypt"
                {{ old('ssl_provider', $provision->provision_data['ssl_provider'] ?? '') == 'letsencrypt' ? 'selected' : '' }}>
                Let's Encrypt</option>
            <option value="comodo"
                {{ old('ssl_provider', $provision->provision_data['ssl_provider'] ?? '') == 'comodo' ? 'selected' : '' }}>
                Comodo</option>
            <option value="digicert"
                {{ old('ssl_provider', $provision->provision_data['ssl_provider'] ?? '') == 'digicert' ? 'selected' : '' }}>
                DigiCert</option>
        </select>
    </div>
@endsection
