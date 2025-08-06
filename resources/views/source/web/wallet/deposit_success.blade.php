@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow">
                    {{-- Header --}}
                    @include('source.web.wallet.components.success-header')
                    
                    <div class="card-body">
                        {{-- Success Message --}}
                        @include('source.web.wallet.components.success-message')

                        {{-- Transaction Info --}}
                        @include('source.web.wallet.components.transaction-info')

                        {{-- Payment Details --}}
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card"></i> Thông tin thanh toán
                                </h5>
                            </div>
                            <div class="card-body">
                                @includeWhen($depositData['payment_method'] === 'bank', 'source.web.wallet.components.payment.bank', ['paymentInfo' => $depositData['payment_info']])
                                @includeWhen($depositData['payment_method'] === 'momo', 'source.web.wallet.components.payment.momo', ['paymentInfo' => $depositData['payment_info']])
                                @includeWhen($depositData['payment_method'] === 'zalopay', 'source.web.wallet.components.payment.zalopay', ['paymentInfo' => $depositData['payment_info']])
                                @includeWhen($depositData['payment_method'] === 'paypal', 'source.web.wallet.components.payment.paypal', ['paymentInfo' => $depositData['payment_info']])
                                @includeWhen($depositData['payment_method'] === 'crypto', 'source.web.wallet.components.payment.crypto', ['paymentInfo' => $depositData['payment_info']])
                            </div>
                        </div>

                        {{-- Instructions --}}
                        @include('source.web.wallet.components.instructions')

                        {{-- Bonus Alert --}}
                        @if ($depositData['bonus_amount'] > 0)
                            @include('source.web.wallet.components.bonus-alert')
                        @endif

                        {{-- Countdown Timer --}}
                        @include('source.web.wallet.components.countdown-timer')

                        {{-- Status Update Alert --}}
                        <div class="alert alert-primary d-none" id="status-update-alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-sync-alt me-2"></i>
                                <span>Trạng thái giao dịch được cập nhật tự động mỗi 30 giây</span>
                                <button class="btn btn-sm btn-outline-primary ms-auto" onclick="checkStatus()">
                                    <i class="fas fa-refresh"></i> Kiểm tra ngay
                                </button>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        @include('source.web.wallet.components.action-buttons')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- Include Scripts --}}
@include('source.web.wallet.components.toast')
@include('source.web.wallet.components.clipboard') 
@include('source.web.wallet.components.status-check')
@include('source.web.wallet.components.countdown')
@include('source.web.wallet.components.force-display')

{{-- Include Styles --}}
@include('source.web.wallet.components.main')