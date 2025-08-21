<?php

namespace App\Services;

use App\Models\{Orders, Order_items, Products, Customers};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class ProvisionService extends BaseService
{
    /**
     * Handle specific provisioning logic based on product type
     *
     * @param Products $service
     * @param Products $originalProduct
     * @param array $options
     */
    private function handleSpecificProvisioning(Products $service, Products $originalProduct, array $options): void
    {
        switch ($originalProduct->type) {
            case 'ssl':
                $this->provisionSSLCertificate($service, $options);
                break;
            case 'hosting':
                $this->provisionHostingAccount($service, $options);
                break;
            case 'domain':
                $this->provisionDomain($service, $options);
                break;
            case 'service':
                $this->provisionGenericService($service, $options);
                break;
        }
    }

    /**
     * Provision SSL Certificate
     *
     * @param Products $service
     * @param array $options
     */
    private function provisionSSLCertificate(Products $service, array $options): void
    {
        $metaData = $service->meta_data;
        
        // Generate SSL certificate data (in real implementation, this would integrate with CA)
        $metaData['ssl_certificate'] = [
            'status' => 'pending_validation',
            'validation_method' => 'dns',
            'validation_token' => \Illuminate\Support\Str::random(32),
            'created_at' => now()->toDateTimeString(),
            'expires_at' => $service->end_date->toDateTimeString(),
            'common_name' => $options['domain'] ?? '',
            'subject_alternative_names' => isset($options['domain']) ? ['*.' . $options['domain']] : []
        ];

        // Update service with certificate info
        $service->update([
            'meta_data' => $metaData,
            'service_status' => 'pending'
        ]);

        $this->logActivity('SSL Certificate provisioning initiated', [
            'service_id' => $service->id,
            'domain' => $options['domain'] ?? 'N/A'
        ]);
    }

    /**
     * Provision Hosting Account
     *
     * @param Products $service
     * @param array $options
     */
    private function provisionHostingAccount(Products $service, array $options): void
    {
        $metaData = $service->meta_data;
        
        // Generate hosting account details
        $metaData['hosting_account'] = [
            'username' => 'user' . $service->customer_id . time(),
            'password' => \Illuminate\Support\Str::random(12),
            'server_ip' => '192.168.1.100', // Would be assigned dynamically
            'control_panel_url' => 'https://cpanel.example.com',
            'ftp_host' => 'ftp.example.com',
            'nameservers' => [
                'ns1.example.com',
                'ns2.example.com'
            ],
            'disk_quota' => '10GB',
            'bandwidth_quota' => 'Unlimited',
            'created_at' => now()->toDateTimeString()
        ];

        // Update service
        $service->update([
            'meta_data' => $metaData,
            'service_status' => 'active'
        ]);

        $this->logActivity('Hosting account provisioned', [
            'service_id' => $service->id,
            'username' => $metaData['hosting_account']['username']
        ]);
    }

    /**
     * Provision Domain
     *
     * @param Products $service
     * @param array $options
     */
    private function provisionDomain(Products $service, array $options): void
    {
        $metaData = $service->meta_data;
        
        // Generate domain registration details
        $metaData['domain_registration'] = [
            'domain' => $options['domain'] ?? '',
            'registrar' => 'Example Registrar',
            'registration_date' => now()->toDateString(),
            'expiration_date' => $service->end_date->toDateString(),
            'auto_renew' => $options['auto_renew'] ?? false,
            'nameservers' => [
                'ns1.example.com',
                'ns2.example.com'
            ],
            'status' => 'active',
            'auth_code' => \Illuminate\Support\Str::random(16)
        ];

        // Update service
        $service->update([
            'meta_data' => $metaData,
            'service_status' => 'active'
        ]);

        $this->logActivity('Domain provisioned', [
            'service_id' => $service->id,
            'domain' => $options['domain'] ?? 'N/A'
        ]);
    }

    /**
     * Provision Generic Service
     *
     * @param Products $service
     * @param array $options
     */
    private function provisionGenericService(Products $service, array $options): void
    {
        $metaData = $service->meta_data;
        
        $metaData['service_details'] = [
            'activated_at' => now()->toDateTimeString(),
            'service_id' => 'SRV-' . $service->id,
            'status' => 'active',
            'configuration' => $options
        ];

        $service->update([
            'meta_data' => $metaData,
            'service_status' => 'active'
        ]);

        $this->logActivity('Generic service provisioned', [
            'service_id' => $service->id
        ]);
    }

    /**
     * Renew service for additional period
     *
     * @param Products $service
     * @param int $additionalYears
     * @return bool
     * @throws Exception
     */
    public function renewService(Products $service, int $additionalYears = 1): bool
    {
        return $this->transaction(function() use ($service, $additionalYears) {
            if ($service->service_status !== 'active') {
                throw new Exception('Only active services can be renewed');
            }

            // Extend service dates
            $newEndDate = Carbon::parse($service->end_date)->addYears($additionalYears);
            $newDueDate = $newEndDate->copy();

            $service->update([
                'end_date' => $newEndDate,
                'next_due_date' => $newDueDate,
                'recurring_period' => ($service->recurring_period ?? 12) + ($additionalYears * 12)
            ]);

            $this->logActivity('Service renewed', [
                'service_id' => $service->id,
                'additional_years' => $additionalYears,
                'new_end_date' => $newEndDate->toDateString()
            ]);

            return true;
        });
    }

    /**
     * Suspend service
     *
     * @param Products $service
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function suspendService(Products $service, string $reason = ''): bool
    {
        return $this->transaction(function() use ($service, $reason) {
            if (!in_array($service->service_status, ['active', 'pending'])) {
                throw new Exception('Service cannot be suspended from current status');
            }

            $metaData = $service->meta_data;
            $metaData['suspension'] = [
                'suspended_at' => now()->toDateTimeString(),
                'reason' => $reason,
                'suspended_by' => Auth::id()
            ];

            $service->update([
                'service_status' => 'suspended',
                'meta_data' => $metaData
            ]);

            $this->logActivity('Service suspended', [
                'service_id' => $service->id,
                'reason' => $reason
            ]);

            return true;
        });
    }

    /**
     * Reactivate suspended service
     *
     * @param Products $service
     * @return bool
     * @throws Exception
     */
    public function reactivateService(Products $service): bool
    {
        return $this->transaction(function() use ($service) {
            if ($service->service_status !== 'suspended') {
                throw new Exception('Only suspended services can be reactivated');
            }

            $metaData = $service->meta_data;
            if (isset($metaData['suspension'])) {
                $metaData['reactivation'] = [
                    'reactivated_at' => now()->toDateTimeString(),
                    'reactivated_by' => Auth::id()
                ];
            }

            $service->update([
                'service_status' => 'active',
                'meta_data' => $metaData
            ]);

            $this->logActivity('Service reactivated', [
                'service_id' => $service->id
            ]);

            return true;
        });
    }

    /**
     * Cancel service
     *
     * @param Products $service
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function cancelService(Products $service, string $reason = ''): bool
    {
        return $this->transaction(function() use ($service, $reason) {
            $metaData = $service->meta_data;
            $metaData['cancellation'] = [
                'cancelled_at' => now()->toDateTimeString(),
                'reason' => $reason,
                'cancelled_by' => Auth::id()
            ];

            $service->update([
                'service_status' => 'cancelled',
                'meta_data' => $metaData
            ]);

            $this->logActivity('Service cancelled', [
                'service_id' => $service->id,
                'reason' => $reason
            ]);

            return true;
        });
    }

    /**
     * Get services due for renewal
     *
     * @param int $daysAhead
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getServicesDueForRenewal(int $daysAhead = 30): \Illuminate\Database\Eloquent\Collection
    {
        $cutoffDate = now()->addDays($daysAhead);

        return Products::where('service_status', 'active')
                      ->where('is_recurring', true)
                      ->where('next_due_date', '<=', $cutoffDate)
                      ->with(['customer.user'])
                      ->get();
    }

    /**
     * Get customer services
     *
     * @param int $customerId
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerServices(int $customerId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Products::where('customer_id', $customerId)
                        ->whereNotNull('customer_id');

        if ($status) {
            $query->where('service_status', $status);
        }

        return $query->with(['parentProduct'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Create service provisions from completed order
     *
     * @param Orders $order
     * @return array
     * @throws Exception
     */
    public function createFromOrder(Orders $order): array
    {
        return $this->transaction(function() use ($order) {
            $provisions = [];

            foreach ($order->items as $item) {
                if ($this->shouldProvisionItem($item)) {
                    $service = $this->createServiceForItem($item, $order->customer);
                    $provisions[] = $service;
                }
            }

            $this->logActivity('Services provisioned from order', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'services_created' => count($provisions)
            ]);

            return $provisions;
        });
    }

    /**
     * Check if order item should be provisioned as a service
     *
     * @param Order_items $item
     * @return bool
     */
    private function shouldProvisionItem(Order_items $item): bool
    {
        if (!$item->product) {
            return false;
        }

        // Only provision certain product types
        $provisionableTypes = ['ssl', 'domain', 'hosting', 'service'];
        
        return in_array($item->product->type, $provisionableTypes);
    }

    /**
     * Create service product for order item
     *
     * @param Order_items $item
     * @param Customers $customer
     * @return Products
     * @throws Exception
     */
    private function createServiceForItem(Order_items $item, Customers $customer): Products
    {
        $originalProduct = $item->product;
        $options = json_decode($item->options, true) ?: [];
        
        // Calculate service dates
        $startDate = now();
        $duration = $item->duration ?? 1; // years
        $endDate = $startDate->copy()->addYears($duration);
        $nextDueDate = $endDate->copy();

        // Prepare service data
        $serviceData = [
            'category_id' => $originalProduct->category_id,
            'customer_id' => $customer->id,
            'parent_product_id' => $originalProduct->id,
            'name' => $this->generateServiceName($originalProduct, $options),
            'slug' => null, // Services don't need slugs
            'sku' => $this->generateServiceSku($originalProduct, $customer),
            'description' => $originalProduct->description,
            'short_description' => $originalProduct->short_description,
            'price' => $item->price,
            'type' => $originalProduct->type,
            'product_status' => 'inactive', // Services are not for sale
            'service_status' => 'active', // Service is active
            'stock' => -1, // Unlimited
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_due_date' => $nextDueDate,
            'is_recurring' => $originalProduct->is_recurring ?? true,
            'recurring_period' => $duration * 12, // Convert years to months
            'auto_renew' => $options['auto_renew'] ?? false,
            'meta_data' => $this->generateServiceMetaData($originalProduct, $options, $item),
            'options' => json_encode($options),
            'is_featured' => false,
            'sort_order' => 0
        ];

        // Create the service
        $service = Products::create($serviceData);

        // Update order item with service reference
        $item->update(['service_id' => $service->id]);

        // Handle specific provisioning based on product type
        $this->handleSpecificProvisioning($service, $originalProduct, $options);

        $this->logActivity('Service created', [
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'product_type' => $originalProduct->type,
            'original_product_id' => $originalProduct->id
        ]);

        return $service;
    }

    /**
     * Generate service name with additional info
     *
     * @param Products $product
     * @param array $options
     * @return string
     */
    private function generateServiceName(Products $product, array $options): string
    {
        $name = $product->name;
        
        if (isset($options['domain'])) {
            $name .= " for {$options['domain']}";
        }
        
        if (isset($options['period'])) {
            $name .= " ({$options['period']} year" . ($options['period'] > 1 ? 's' : '') . ")";
        }

        return $name;
    }

    /**
     * Generate unique service SKU
     *
     * @param Products $product
     * @param Customers $customer
     * @return string
     */
    private function generateServiceSku(Products $product, Customers $customer): string
    {
        $baseSku = $product->sku ?: $product->type;
        return strtoupper($baseSku . '-' . $customer->id . '-' . time());
    }

    /**
     * Generate service meta data
     *
     * @param Products $originalProduct
     * @param array $options
     * @param Order_items $item
     * @return array
     */
    private function generateServiceMetaData(Products $originalProduct, array $options, Order_items $item): array
    {
        $metaData = $originalProduct->meta_data ?: [];
        
        // Add service-specific metadata
        $metaData['service_info'] = [
            'created_from_order' => $item->order_id,
            'original_product_id' => $originalProduct->id,
            'provisioned_at' => now()->toDateTimeString(),
            'duration_years' => $item->duration ?? 1
        ];

        // Add domain info if available
        if (isset($options['domain'])) {
            $metaData['domain'] = $options['domain'];
        }

        // Add SSL-specific metadata
        if ($originalProduct->type === 'ssl') {
            $metaData = $this->addSSLMetaData($metaData, $options);
        }

        // Add hosting-specific metadata
        if ($originalProduct->type === 'hosting') {
            $metaData = $this->addHostingMetaData($metaData, $options);
        }

        return $metaData;
    }

    /**
     * Add SSL-specific metadata
     *
     * @param array $metaData
     * @param array $options
     * @return array
     */
    private function addSSLMetaData(array $metaData, array $options): array
    {
        if (isset($options['domain'])) {
            $metaData['ssl_info'] = [
                'domain' => $options['domain'],
                'verification_method' => 'domain_validation',
                'key_size' => '2048',
                'algorithm' => 'RSA',
                'status' => 'pending_installation'
            ];
        }

        return $metaData;
    }

    /**
     * Add hosting-specific metadata
     *
     * @param array $metaData
     * @param array $options
     * @return array
     */
    private function addHostingMetaData(array $metaData, array $options): array
    {
        $metaData['hosting_info'] = [
            'server_location' => 'auto_assign',
            'control_panel' => 'cpanel',
            'php_version' => '8.1',
            'mysql_version' => '5.7',
            'backup_enabled' => true,
            'ssl_enabled' => true,
            'status' => 'setting_up'
        ];

        if (isset($options['domain'])) {
            $metaData['hosting_info']['primary_domain'] = $options['domain'];
        }

        return $metaData;
    }
}