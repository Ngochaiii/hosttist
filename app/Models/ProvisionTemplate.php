<?php
// app/Models/ProvisionTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\EncryptsData;

class ProvisionTemplate extends Model  // Chú ý: tên class phải viết hoa
{
    use EncryptsData; // Bỏ SoftDeletes

    protected $fillable = [
        'name',
        'description',
        'provision_type',
        'product_id',
        'template_data',
        'default_settings',
        'validation_rules',
        'estimated_duration',
        'priority',
        'is_active',
        'created_by',
        'tags',
        'version',
        'changelog'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_duration' => 'integer',
        'priority' => 'integer',
        'version' => 'float',
        'tags' => 'array'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }
}