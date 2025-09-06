@extends('source.admin.provisions.forms._base')

@section('form-fields')
<div class="form-group">
    <label>Domain Name <span class="text-danger">*</span></label>
    <input type="text" name="domain_name" class="form-control" required
           value="{{ old('domain_name', $provision->orderItem->domain) }}">
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Registrar</label>
            <select name="registrar" class="form-control">
                <option value="">Select Registrar</option>
                <option value="namecheap" {{ old('registrar', $provision->provision_data['registrar'] ?? '') == 'namecheap' ? 'selected' : '' }}>Namecheap</option>
                <option value="godaddy" {{ old('registrar', $provision->provision_data['registrar'] ?? '') == 'godaddy' ? 'selected' : '' }}>GoDaddy</option>
                <option value="cloudflare" {{ old('registrar', $provision->provision_data['registrar'] ?? '') == 'cloudflare' ? 'selected' : '' }}>Cloudflare</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" 
                   value="{{ old('expiry_date', $provision->provision_data['expiry_date'] ?? '') }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Name Servers (one per line)</label>
    <textarea name="nameservers" class="form-control" rows="4" 
              placeholder="ns1.example.com&#10;ns2.example.com">{{ old('nameservers', isset($provision->provision_data['nameservers']) ? implode("\n", $provision->provision_data['nameservers']) : '') }}</textarea>
</div>

<div class="form-group">
    <label>Admin Email</label>
    <input type="email" name="admin_email" class="form-control" 
           value="{{ old('admin_email', $provision->provision_data['admin_email'] ?? $provision->customer->email) }}">
</div>

<div class="form-group">
    <label>Auto Renewal</label>
    <select name="auto_renewal" class="form-control">
        <option value="1" {{ old('auto_renewal', $provision->provision_data['auto_renewal'] ?? '') == '1' ? 'selected' : '' }}>Yes</option>
        <option value="0" {{ old('auto_renewal', $provision->provision_data['auto_renewal'] ?? '') == '0' ? 'selected' : '' }}>No</option>
    </select>
</div>
@endsection