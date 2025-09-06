@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ ucfirst($provision->provision_type) }} Provision - #{{ $provision->id }}
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-{{ $provision->provision_status == 'pending' ? 'warning' : 'info' }}">
                                {{ $provision->getStatusLabel() }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('admin.provisions.update', $provision->id) }}" method="POST" id="provision-form">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Basic Info -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Customer</label>
                                    <input type="text" class="form-control" value="{{ $provision->customer->name }}"
                                        readonly>
                                </div>
                                <div class="col-md-6">
                                    <label>Product</label>
                                    <input type="text" class="form-control" value="{{ $provision->product->name }}"
                                        readonly>
                                </div>
                            </div>

                            @yield('form-fields')

                            <!-- Notes -->
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="provision_notes" class="form-control" rows="3">{{ old('provision_notes', $provision->provision_notes) }}</textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" name="action" value="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button type="submit" name="action" value="complete" class="btn btn-success">
                                <i class="fas fa-check"></i> Complete
                            </button>
                            <a href="{{ route('admin.provisions.show', $provision->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Order Info</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Order ID:</strong> {{ $provision->orderItem->order_id }}</p>
                        <p><strong>Domain:</strong> {{ $provision->orderItem->domain ?: 'N/A' }}</p>
                        <p><strong>Duration:</strong> {{ $provision->orderItem->duration ?: 'N/A' }}</p>
                        <p><strong>Created:</strong> {{ $provision->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
