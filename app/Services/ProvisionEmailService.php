<?php

// app/Services/ProvisionEmailService.php
namespace App\Services;

// Import đầy đủ để tránh xung đột
use App\Models\ServiceProvision;
use App\Models\User;
use App\Mail\ProvisionCreated as ProvisionCreatedMail;
use App\Mail\ProvisionCompleted as ProvisionCompletedMail;
use App\Mail\ProvisionFailed as ProvisionFailedMail;
use App\Notifications\AdminProvisionAlert;
use App\Notifications\CustomerServiceReady;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class ProvisionEmailService
{
    /**
     * Send provision created notification
     */
    public function sendProvisionCreated(ServiceProvision $provision): bool
    {
        try {
            // Send email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionCreatedMail($provision));

            // Notify admins
            $this->notifyAdmins($provision, 'created');

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::info('Provision created email sent', [
                'provision_id' => $provision->id,
                'customer_email' => $provision->customer->user->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send provision created email', [
                'provision_id' => $provision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send provision completed notification
     */
    public function sendProvisionCompleted(ServiceProvision $provision): bool
    {
        try {
            // Send completion email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionCompletedMail($provision));

            // Send notification to customer user
            $provision->customer->user->notify(new CustomerServiceReady($provision));

            // Notify admins
            $this->notifyAdmins($provision, 'completed');

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::info('Provision completed email sent', [
                'provision_id' => $provision->id,
                'customer_email' => $provision->customer->user->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send provision completed email', [
                'provision_id' => $provision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send provision failed notification
     */
    public function sendProvisionFailed(ServiceProvision $provision): bool
    {
        try {
            // Send failure email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionFailedMail($provision));

            // Notify admins with high priority
            $this->notifyAdmins($provision, 'failed');

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::warning('Provision failed email sent', [
                'provision_id' => $provision->id,
                'customer_email' => $provision->customer->user->email,
                'failure_reason' => $provision->failure_reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send provision failed email', [
                'provision_id' => $provision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notify admin users
     */
    private function notifyAdmins(ServiceProvision $provision, string $type): void
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AdminProvisionAlert($provision, $type));
        }
    }

    /**
     * Resend provision notification
     */
    public function resendNotification(ServiceProvision $provision): bool
    {
        return match($provision->provision_status) {
            'pending' => $this->sendProvisionCreated($provision),
            'processing' => $this->sendProvisionCreated($provision),
            'completed' => $this->sendProvisionCompleted($provision),
            'failed' => $this->sendProvisionFailed($provision),
            default => false
        };
    }

    /**
     * Get email delivery statistics
     */
    public function getDeliveryStats(): array
    {
        return [
            'total_sent' => ServiceProvision::where('delivery_status', 'sent')->count(),
            'pending_delivery' => ServiceProvision::where('delivery_status', 'pending')->count(),
            'recently_delivered' => ServiceProvision::where('delivered_at', '>=', now()->subDays(7))->count(),
        ];
    }
}