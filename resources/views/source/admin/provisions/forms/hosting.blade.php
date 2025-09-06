@extends('source.admin.provisions.forms._base')

@section('form-fields')
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Server Name</label>
            <input type="text" name="server_name" class="form-control" 
                   value="{{ old('server_name', $provision->provision_data['server_name'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Server IP</label>
            <input type="text" name="server_ip" class="form-control" 
                   value="{{ old('server_ip', $provision->provision_data['server_ip'] ?? '') }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Control Panel</label>
    <select name="control_panel" class="form-control">
        <option value="">Select Panel</option>
        <option value="cpanel" {{ old('control_panel', $provision->provision_data['control_panel'] ?? '') == 'cpanel' ? 'selected' : '' }}>cPanel</option>
        <option value="plesk" {{ old('control_panel', $provision->provision_data['control_panel'] ?? '') == 'plesk' ? 'selected' : '' }}>Plesk</option>
        <option value="directadmin" {{ old('control_panel', $provision->provision_data['control_panel'] ?? '') == 'directadmin' ? 'selected' : '' }}>DirectAdmin</option>
    </select>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required
                   value="{{ old('username', $provision->provision_data['username'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
            <small class="text-muted">Will be encrypted before saving</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label>FTP Details</label>
    <textarea name="ftp_details" class="form-control" rows="3" 
              placeholder="Host: ftp.example.com&#10;Port: 21">{{ old('ftp_details', $provision->provision_data['ftp_details'] ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Disk Space (GB)</label>
            <input type="number" name="disk_space" class="form-control" 
                   value="{{ old('disk_space', $provision->provision_data['disk_space'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Bandwidth (GB)</label>
            <input type="number" name="bandwidth" class="form-control" 
                   value="{{ old('bandwidth', $provision->provision_data['bandwidth'] ?? '') }}">
        </div>
    </div>
</div>
@endsection