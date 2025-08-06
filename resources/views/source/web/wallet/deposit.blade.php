@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                @include('source.web.wallet.partials.language_switcher')

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-wallet"></i> 
                            {{ $locale === 'vi' ? 'Nạp tiền vào tài khoản' : 'Deposit to Account' }}
                        </h4>
                    </div>
                    <div class="card-body">
                        @include('source.web.wallet.partials.alerts')

                        <form method="POST" action="{{ route('deposit.process') }}" id="depositForm">
                            @csrf
                            <input type="hidden" name="locale" value="{{ $locale }}">

                            @include('source.web.wallet.partials.account_info')
                            @include('source.web.wallet.partials.promotion_banner')
                            @include('source.web.wallet.partials.amount_selection')
                            @include('source.web.wallet.partials.payment_methods')
                            @include('source.web.wallet.partials.terms_submit')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@include('source.web.wallet.partials.deposit_scripts')
@include('source.web.wallet.partials.deposit_styles')