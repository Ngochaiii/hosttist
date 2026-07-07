@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row">
            @include('source.web.profile.partials.sidebar')
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Hóa đơn chưa thanh toán</h4>
                    </div>
                    <div class="card-body">
                        @if (session('message'))
                            <div class="alert alert-info">
                                {{ session('message') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if ($invoices->isEmpty())
                            <div class="alert alert-info">
                                Bạn không có hóa đơn chưa thanh toán nào.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã hóa đơn</th>
                                            <th>Ngày tạo</th>
                                            <th>Hạn thanh toán</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if ($invoice->due_date)
                                                        {{ is_string($invoice->due_date) ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : $invoice->due_date->format('d/m/Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} đ</td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('order.show', $invoice->order_id) }}"
                                                            class="btn btn-info">
                                                            <i class="fa fa-eye"></i> Xem
                                                        </a>
                                                        <a href="{{ route('invoice.download', $invoice->id) }}"
                                                            class="btn btn-secondary">
                                                            <i class="fa fa-download"></i> PDF
                                                        </a>
                                                        <a href="{{ route('invoice.payment', $invoice->id) }}"
                                                            class="btn btn-success">
                                                            <i class="fa fa-credit-card"></i> Thanh toán
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                {{ $invoices->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script cho tính năng ẩn hiện số dư -->
@endsection
