<?php

namespace App\Services;

use App\Models\{Payments, Config, Order_items, Products};
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailService extends BaseService
{
    /**
     * Send payment approved email to customer
     *
     * @param Payments $payment
     * @return bool
     */
    public function sendPaymentApprovedEmail(Payments $payment): bool
    {
        try {
            $user = $payment->order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                throw new Exception('Customer email not found');
            }

            $emailContent = $this->buildPaymentApprovedEmailContent($payment, $user, $config);

            Mail::html($emailContent, function ($mail) use ($user, $payment) {
                $mail->to($user->email)
                    ->subject('Xác nhận thanh toán hóa đơn #' . $payment->invoice->invoice_number);
            });

            $this->logActivity('Payment approved email sent', [
                'payment_id' => $payment->id,
                'customer_email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending payment approved email');
            return false;
        }
    }

    /**
     * Send payment rejected email to customer
     *
     * @param Payments $payment
     * @param string $reason
     * @return bool
     */
    public function sendPaymentRejectedEmail(Payments $payment, string $reason): bool
    {
        try {
            $user = $payment->order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                throw new Exception('Customer email not found');
            }

            $emailContent = $this->buildPaymentRejectedEmailContent($payment, $user, $config, $reason);

            Mail::html($emailContent, function ($mail) use ($user, $payment) {
                $mail->to($user->email)
                    ->subject('Thông báo từ chối thanh toán #' . $payment->transaction_id);
            });

            $this->logActivity('Payment rejected email sent', [
                'payment_id' => $payment->id,
                'customer_email' => $user->email,
                'reason' => $reason
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending payment rejected email');
            return false;
        }
    }

    /**
     * Send admin notification for new payment request
     *
     * @param Payments $payment
     * @param string $adminEmail
     * @return bool
     */
    public function sendAdminPaymentNotification(Payments $payment, string $adminEmail): bool
    {
        try {
            $user = $payment->order->customer->user;
            $config = Config::current();

            $emailContent = $this->buildAdminPaymentNotificationContent($payment, $user, $config);

            Mail::html($emailContent, function ($mail) use ($adminEmail, $payment) {
                $mail->to($adminEmail)
                    ->subject('Yêu cầu thanh toán mới #' . $payment->transaction_id);
            });

            $this->logActivity('Admin payment notification sent', [
                'payment_id' => $payment->id,
                'admin_email' => $adminEmail
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending admin payment notification');
            return false;
        }
    }

    /**
     * Send order confirmation email
     *
     * @param $order
     * @return bool
     */
    public function sendOrderConfirmationEmail($order): bool
    {
        try {
            $user = $order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                throw new Exception('Customer email not found');
            }

            $emailContent = $this->buildOrderConfirmationEmailContent($order, $user, $config);

            Mail::html($emailContent, function ($mail) use ($user, $order) {
                $mail->to($user->email)
                    ->subject('Xác nhận đơn hàng #' . $order->order_number);
            });

            $this->logActivity('Order confirmation email sent', [
                'order_id' => $order->id,
                'customer_email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending order confirmation email');
            return false;
        }
    }

    /**
     * Build payment approved email content
     *
     * @param Payments $payment
     * @param $user
     * @param Config|null $config
     * @return string
     */
    private function buildPaymentApprovedEmailContent(Payments $payment, $user, ?Config $config): string
    {
        $invoice = $payment->invoice;
        $order = $payment->order;
        $verifiedDate = $payment->verified_at ? $payment->verified_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s');

        // Build services table
        $servicesTable = $this->buildServicesTable($order->items);

        // Build SSL/Domain info if applicable
        $sslInfoHtml = $this->buildSSLDomainInfo($order->items);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .header h1 { margin: 0; color: #333; font-size: 24px; }
                .success-box { background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                th { font-weight: bold; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p>Xác nhận thanh toán</p>
                </div>
                
                <div class='success-box'>
                    <p><strong>Thanh toán của bạn đã được xác nhận!</strong> Cảm ơn bạn đã thanh toán.</p>
                </div>
                
                <p>Kính gửi {$user->name},</p>
                <p>Chúng tôi xác nhận đã nhận được thanh toán của bạn với thông tin như sau:</p>
                
                <table>
                    <tr><th>Mã hóa đơn:</th><td>{$invoice->invoice_number}</td></tr>
                    <tr><th>Mã đơn hàng:</th><td>{$order->order_number}</td></tr>
                    <tr><th>Số tiền:</th><td>" . number_format($payment->amount, 0, ',', '.') . " đ</td></tr>
                    <tr><th>Phương thức:</th><td>" . $this->getPaymentMethodName($payment->payment_method) . "</td></tr>
                    <tr><th>Ngày xác nhận:</th><td>{$verifiedDate}</td></tr>
                    <tr><th>Mã giao dịch:</th><td>{$payment->transaction_id}</td></tr>
                </table>
                
                {$servicesTable}
                {$sslInfoHtml}
                
                <p>Đơn hàng của bạn đang được xử lý. Bạn có thể theo dõi tình trạng đơn hàng tại trang quản lý của bạn.</p>
                
                <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email " .
                ($config->support_email ?? 'support@company.com') . " hoặc số điện thoại " .
                ($config->support_phone ?? 'N/A') . ".</p>
                
                <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist Company') . "</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist Company') . ". Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Build payment rejected email content
     *
     * @param Payments $payment
     * @param $user
     * @param Config|null $config
     * @param string $reason
     * @return string
     */
    private function buildPaymentRejectedEmailContent(Payments $payment, $user, ?Config $config, string $reason): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .error-box { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p>Thông báo thanh toán</p>
                </div>
                
                <div class='error-box'>
                    <p><strong>Thanh toán của bạn đã bị từ chối</strong></p>
                </div>
                
                <p>Kính gửi {$user->name},</p>
                <p>Chúng tôi rất tiếc phải thông báo rằng thanh toán của bạn đã bị từ chối với lý do:</p>
                
                <table>
                    <tr><th>Mã giao dịch:</th><td>{$payment->transaction_id}</td></tr>
                    <tr><th>Số tiền:</th><td>" . number_format($payment->amount, 0, ',', '.') . " đ</td></tr>
                    <tr><th>Lý do từ chối:</th><td>{$reason}</td></tr>
                    <tr><th>Ngày từ chối:</th><td>" . now()->format('d/m/Y H:i:s') . "</td></tr>
                </table>
                
                <p>Vui lòng kiểm tra lại thông tin thanh toán và thực hiện lại giao dịch, hoặc liên hệ với chúng tôi để được hỗ trợ.</p>
                
                <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist Company') . "</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist Company') . ". Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Build admin payment notification content
     *
     * @param Payments $payment
     * @param $user
     * @param Config|null $config
     * @return string
     */
    private function buildAdminPaymentNotificationContent(Payments $payment, $user, ?Config $config): string
    {
        $servicesTable = $this->buildServicesTable($payment->order->items, true);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .info-box { background-color: #cce5ff; border-color: #b8daff; color: #004085; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                th { font-weight: bold; width: 40%; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p>Thông báo yêu cầu thanh toán mới</p>
                </div>
                
                <div class='info-box'>
                    <p><strong>Có yêu cầu thanh toán mới!</strong> Khách hàng đã chọn phương thức " . $this->getPaymentMethodName($payment->payment_method) . ".</p>
                </div>
                
                <p>Thông tin khách hàng:</p>
                <table>
                    <tr><th>Tên khách hàng:</th><td>{$user->name}</td></tr>
                    <tr><th>Email:</th><td>{$user->email}</td></tr>
                    <tr><th>ID khách hàng:</th><td>{$payment->order->customer_id}</td></tr>
                </table>
                
                <p>Thông tin thanh toán:</p>
                <table>
                    <tr><th>Mã hóa đơn:</th><td>{$payment->invoice->invoice_number}</td></tr>
                    <tr><th>Mã đơn hàng:</th><td>{$payment->order->order_number}</td></tr>
                    <tr><th>Số tiền thanh toán:</th><td>" . number_format($payment->amount, 0, ',', '.') . " đ</td></tr>
                    <tr><th>Mã giao dịch:</th><td>{$payment->transaction_id}</td></tr>
                    <tr><th>Phương thức thanh toán:</th><td>" . $this->getPaymentMethodName($payment->payment_method) . "</td></tr>
                    <tr><th>Ngày tạo yêu cầu:</th><td>{$payment->payment_date->format('d/m/Y H:i:s')}</td></tr>
                </table>
                
                {$servicesTable}
                
                <p>Vui lòng truy cập trang quản trị để xác nhận thanh toán.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Build order confirmation email content
     *
     * @param $order
     * @param $user
     * @param Config|null $config
     * @return string
     */
    private function buildOrderConfirmationEmailContent($order, $user, ?Config $config): string
    {
        $servicesTable = $this->buildServicesTable($order->items);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .info-box { background-color: #e3f2fd; border-color: #bbdefb; color: #0277bd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p>Xác nhận đơn hàng</p>
                </div>
                
                <div class='info-box'>
                    <p><strong>Đơn hàng của bạn đã được tạo thành công!</strong></p>
                </div>
                
                <p>Kính gửi {$user->name},</p>
                <p>Cảm ơn bạn đã đặt hàng. Thông tin đơn hàng của bạn như sau:</p>
                
                <table>
                    <tr><th>Mã đơn hàng:</th><td>{$order->order_number}</td></tr>
                    <tr><th>Ngày đặt hàng:</th><td>{$order->created_at->format('d/m/Y H:i:s')}</td></tr>
                    <tr><th>Trạng thái:</th><td>" . $this->getOrderStatusName($order->status) . "</td></tr>
                    <tr><th>Tổng tiền:</th><td>" . number_format($order->total_amount, 0, ',', '.') . " đ</td></tr>
                </table>
                
                {$servicesTable}
                
                <p>Đơn hàng của bạn đang được xử lý. Chúng tôi sẽ thông báo cho bạn khi có cập nhật mới.</p>
                
                <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist Company') . "</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist Company') . ". Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Build services table HTML
     *
     * @param $orderItems
     * @param bool $isAdminEmail
     * @return string
     */
    private function buildServicesTable($orderItems, bool $isAdminEmail = false): string
    {
        if ($orderItems->isEmpty()) {
            return '';
        }

        $tableHtml = "
        <h3>Chi tiết dịch vụ:</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <thead>
                <tr>
                    <th style='text-align: left; padding: 8px; border-bottom: 1px solid #ddd;'>Dịch vụ</th>
                    <th style='text-align: left; padding: 8px; border-bottom: 1px solid #ddd;'>Thời hạn</th>
                    <th style='text-align: left; padding: 8px; border-bottom: 1px solid #ddd;'>Domain</th>" .
                    ($isAdminEmail ? "<th style='text-align: left; padding: 8px; border-bottom: 1px solid #ddd;'>Thành tiền</th>" : "") . "
                </tr>
            </thead>
            <tbody>";

        foreach ($orderItems as $item) {
            $options = json_decode($item->options, true) ?: [];
            $period = $options['period'] ?? $item->duration ?? 1;
            $domain = $item->domain ?? ($options['domain'] ?? '');

            $domainDisplay = '';
            if (!empty($domain)) {
                $domainDisplay = "<span style='display: inline-block; padding: 3px 6px; background-color: #17a2b8; color: white; border-radius: 3px;'>{$domain}</span>";
            } else {
                $domainDisplay = '-';
            }

            $tableHtml .= "
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$item->name}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$period} năm</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$domainDisplay}</td>";

            if ($isAdminEmail) {
                $tableHtml .= "<td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . number_format($item->subtotal, 0, ',', '.') . " đ</td>";
            }

            $tableHtml .= "</tr>";
        }

        $tableHtml .= "</tbody></table>";

        return $tableHtml;
    }

    /**
     * Build SSL/Domain additional info
     *
     * @param $orderItems
     * @return string
     */
    private function buildSSLDomainInfo($orderItems): string
    {
        $sslInfoHtml = '';

        foreach ($orderItems as $item) {
            if (!$item->product) continue;

            $productType = $item->product->type;
            $metaData = $item->product->meta_data ?: [];
            $options = json_decode($item->options, true) ?: [];
            $domain = $item->domain ?? ($options['domain'] ?? '');

            if (in_array($productType, ['ssl', 'domain']) && !empty($domain)) {
                $sslInfoHtml .= "
                <div style='margin-top: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;'>
                    <h3>Thông tin " . ($productType === 'ssl' ? 'SSL Certificate' : 'Domain') . " chi tiết</h3>";

                $sslInfoHtml .= "<p><strong>Domain:</strong> {$domain}</p>";

                if ($productType === 'ssl') {
                    $sslInfoHtml .= "
                    <p><strong>Loại chứng chỉ:</strong> Domain Validation (DV)</p>
                    <p><strong>Mã hóa:</strong> 256-bit SSL</p>
                    <p><strong>Tương thích:</strong> 99.9% trình duyệt</p>";
                }

                if (!empty($metaData['expiration_date'])) {
                    $sslInfoHtml .= "<p><strong>Ngày hết hạn:</strong> {$metaData['expiration_date']}</p>";
                }

                $sslInfoHtml .= "
                    <p><em>Thông tin chi tiết sẽ được gửi qua email riêng sau khi dịch vụ được kích hoạt.</em></p>
                </div>";
            }
        }

        return $sslInfoHtml;
    }

    /**
     * Get payment method display name
     *
     * @param string $method
     * @return string
     */
    private function getPaymentMethodName(string $method): string
    {
        return match($method) {
            'bank' => 'Chuyển khoản ngân hàng',
            'wallet' => 'Thanh toán từ số dư tài khoản',
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'paypal' => 'PayPal',
            'crypto' => 'Tiền điện tử',
            'credit_card' => 'Thẻ tín dụng',
            'cash' => 'Tiền mặt',
            default => ucfirst($method)
        };
    }

    /**
     * Get order status display name
     *
     * @param string $status
     * @return string
     */
    private function getOrderStatusName(string $status): string
    {
        return match($status) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'shipped' => 'Đã giao hàng',
            'delivered' => 'Đã nhận hàng',
            default => ucfirst($status)
        };
    }

    /**
     * Send service activation email
     *
     * @param Products $service
     * @return bool
     */
    public function sendServiceActivationEmail(Products $service): bool
    {
        try {
            $customer = $service->customer;
            $user = $customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                throw new Exception('Customer email not found');
            }

            $emailContent = $this->buildServiceActivationEmailContent($service, $user, $config);

            Mail::html($emailContent, function ($mail) use ($user, $service) {
                $mail->to($user->email)
                    ->subject('Dịch vụ đã được kích hoạt - ' . $service->name);
            });

            $this->logActivity('Service activation email sent', [
                'service_id' => $service->id,
                'customer_email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending service activation email');
            return false;
        }
    }

    /**
     * Build service activation email content
     *
     * @param Products $service
     * @param $user
     * @param Config|null $config
     * @return string
     */
    private function buildServiceActivationEmailContent(Products $service, $user, ?Config $config): string
    {
        $metaData = $service->meta_data ?: [];
        $serviceInfo = '';

        // Build service-specific information
        switch ($service->type) {
            case 'hosting':
                $hostingInfo = $metaData['hosting_account'] ?? [];
                if (!empty($hostingInfo)) {
                    $serviceInfo = "
                    <h3>Thông tin tài khoản Hosting:</h3>
                    <table>
                        <tr><th>Username:</th><td>{$hostingInfo['username']}</td></tr>
                        <tr><th>Password:</th><td>{$hostingInfo['password']}</td></tr>
                        <tr><th>Control Panel:</th><td><a href='{$hostingInfo['control_panel_url']}'>{$hostingInfo['control_panel_url']}</a></td></tr>
                        <tr><th>FTP Host:</th><td>{$hostingInfo['ftp_host']}</td></tr>
                        <tr><th>Nameservers:</th><td>" . implode('<br>', $hostingInfo['nameservers']) . "</td></tr>
                    </table>";
                }
                break;

            case 'ssl':
                $sslInfo = $metaData['ssl_certificate'] ?? [];
                if (!empty($sslInfo)) {
                    $serviceInfo = "
                    <h3>Thông tin SSL Certificate:</h3>
                    <table>
                        <tr><th>Domain:</th><td>{$sslInfo['common_name']}</td></tr>
                        <tr><th>Trạng thái:</th><td>{$sslInfo['status']}</td></tr>
                        <tr><th>Ngày hết hạn:</th><td>{$sslInfo['expires_at']}</td></tr>
                    </table>
                    <p><em>Chứng chỉ SSL sẽ được cấp phát trong vòng 24-48 giờ.</em></p>";
                }
                break;

            case 'domain':
                $domainInfo = $metaData['domain_registration'] ?? [];
                if (!empty($domainInfo)) {
                    $serviceInfo = "
                    <h3>Thông tin Domain:</h3>
                    <table>
                        <tr><th>Domain:</th><td>{$domainInfo['domain']}</td></tr>
                        <tr><th>Ngày đăng ký:</th><td>{$domainInfo['registration_date']}</td></tr>
                        <tr><th>Ngày hết hạn:</th><td>{$domainInfo['expiration_date']}</td></tr>
                        <tr><th>Nameservers:</th><td>" . implode('<br>', $domainInfo['nameservers']) . "</td></tr>
                    </table>";
                }
                break;
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .success-box { background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                th { font-weight: bold; width: 30%; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p>Thông báo kích hoạt dịch vụ</p>
                </div>
                
                <div class='success-box'>
                    <p><strong>Dịch vụ của bạn đã được kích hoạt!</strong></p>
                </div>
                
                <p>Kính gửi {$user->name},</p>
                <p>Chúng tôi thông báo dịch vụ <strong>{$service->name}</strong> của bạn đã được kích hoạt thành công.</p>
                
                <table>
                    <tr><th>Tên dịch vụ:</th><td>{$service->name}</td></tr>
                    <tr><th>Mã dịch vụ:</th><td>{$service->sku}</td></tr>
                    <tr><th>Ngày kích hoạt:</th><td>{$service->start_date}</td></tr>
                    <tr><th>Ngày hết hạn:</th><td>{$service->end_date}</td></tr>
                    <tr><th>Trạng thái:</th><td>Đang hoạt động</td></tr>
                </table>
                
                {$serviceInfo}
                
                <p>Vui lòng lưu trữ thông tin này một cách an toàn. Nếu bạn có bất kỳ câu hỏi nào, xin đừng ngần ngại liên hệ với chúng tôi.</p>
                
                <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist Company') . "</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist Company') . ". Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Send service expiration warning email
     *
     * @param Products $service
     * @param int $daysUntilExpiry
     * @return bool
     */
    public function sendServiceExpirationWarning(Products $service, int $daysUntilExpiry): bool
    {
        try {
            $customer = $service->customer;
            $user = $customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                throw new Exception('Customer email not found');
            }

            $emailContent = $this->buildServiceExpirationWarningContent($service, $user, $config, $daysUntilExpiry);

            Mail::html($emailContent, function ($mail) use ($user, $service, $daysUntilExpiry) {
                $mail->to($user->email)
                    ->subject("Thông báo gia hạn dịch vụ - {$service->name} (còn {$daysUntilExpiry} ngày)");
            });

            $this->logActivity('Service expiration warning sent', [
                'service_id' => $service->id,
                'customer_email' => $user->email,
                'days_until_expiry' => $daysUntilExpiry
            ]);

            return true;

        } catch (Exception $e) {
            $this->handleException($e, 'sending service expiration warning');
            return false;
        }
    }

    /**
     * Build service expiration warning content
     *
     * @param Products $service
     * @param $user
     * @param Config|null $config
     * @param int $daysUntilExpiry
     * @return string
     */
    private function buildServiceExpirationWarningContent(Products $service, $user, ?Config $config, int $daysUntilExpiry): string
    {
        $urgencyColor = $daysUntilExpiry <= 7 ? '#dc3545' : '#ffc107';
        $urgencyText = $daysUntilExpiry <= 7 ? 'KHẨN CẤP' : 'THÔNG BÁO';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .warning-box { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid {$urgencyColor}; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                th { font-weight: bold; width: 30%; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
                .btn { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist Company') . "</h1>
                    <p style='color: {$urgencyColor}; font-weight: bold;'>{$urgencyText} GIA HẠN DỊCH VỤ</p>
                </div>
                
                <div class='warning-box'>
                    <p><strong>Dịch vụ của bạn sắp hết hạn!</strong></p>
                    <p>Dịch vụ <strong>{$service->name}</strong> sẽ hết hạn trong <strong style='color: {$urgencyColor};'>{$daysUntilExpiry} ngày</strong>.</p>
                </div>
                
                <p>Kính gửi {$user->name},</p>
                <p>Chúng tôi thông báo dịch vụ của bạn sắp hết hạn:</p>
                
                <table>
                    <tr><th>Tên dịch vụ:</th><td>{$service->name}</td></tr>
                    <tr><th>Mã dịch vụ:</th><td>{$service->sku}</td></tr>
                    <tr><th>Ngày hết hạn:</th><td style='color: {$urgencyColor}; font-weight: bold;'>{$service->end_date}</td></tr>
                    <tr><th>Số ngày còn lại:</th><td style='color: {$urgencyColor}; font-weight: bold;'>{$daysUntilExpiry} ngày</td></tr>
                    <tr><th>Tự động gia hạn:</th><td>" . ($service->auto_renew ? 'Có' : 'Không') . "</td></tr>
                </table>
                
                <p>Để đảm bảo dịch vụ không bị gián đoạn, vui lòng gia hạn trước ngày hết hạn.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='#' class='btn'>GIA HẠN NGAY</a>
                </div>
                
                <p><strong>Lưu ý quan trọng:</strong></p>
                <ul>
                    <li>Nếu không gia hạn kịp thời, dịch vụ sẽ bị tạm ngưng</li>
                    <li>Dữ liệu có thể bị mất sau thời gian tạm ngưng</li>
                    <li>Việc khôi phục sau khi hết hạn có thể tốn thêm chi phí</li>
                </ul>
                
                <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist Company') . "</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist Company') . ". Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}