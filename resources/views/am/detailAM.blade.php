@extends('layouts.app')

@section('title', 'Dashboard Account Manager')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Dashboard Account Manager</h1>
            @if($accountManager)
                <p class="text-muted">Welcome, {{ $accountManager->nama }}</p>
            @endif
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if($accountManager)
        <!-- Performance Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Rp {{ number_format($performanceSummary['total_revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Target</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Rp {{ number_format($performanceSummary['total_target'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-{{ $performanceSummary['achievement_color'] }}">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-{{ $performanceSummary['achievement_color'] }} text-uppercase mb-1">Achievement</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $performanceSummary['achievement_rate'] }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Customers</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $performanceSummary['total_customers'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Corporate Customers -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Corporate Customers</h6>
                    </div>
                    <div class="card-body">
                        @if($corporateCustomers && $corporateCustomers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Revenue</th>
                                            <th>Achievement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($corporateCustomers as $customer)
                                            <tr>
                                                <td>
                                                    <strong>{{ $customer->nama }}</strong><br>
                                                    <small>{{ $customer->segment_nama }}</small>
                                                </td>
                                                <td>Rp {{ number_format($customer->total_revenue, 0, ',', '.') }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $customer->achievement_color }}">
                                                        {{ $customer->achievement_rate }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-muted">Tidak ada data customer</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Monthly Performance -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Monthly Performance</h6>
                    </div>
                    <div class="card-body">
                        @if($monthlyPerformance && $monthlyPerformance->count() > 0)
                            @foreach($monthlyPerformance as $month)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ $month['month_name'] }}</span>
                                    <span class="badge badge-{{ $month['achievement_color'] }}">
                                        {{ $month['achievement_rate'] }}%
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <p class="text-center text-muted">Tidak ada data bulanan</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Manager Info -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Account Manager Info</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nama:</strong> {{ $accountManager->nama }}</p>
                                <p><strong>Email:</strong> {{ $accountManager->email }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Role:</strong> {{ $accountManager->role }}</p>
                                <p><strong>Status:</strong> <span class="badge badge-success">Active</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.border-left-danger { border-left: 0.25rem solid #e74a3b !important; }

.badge-success { background-color: #1cc88a; }
.badge-warning { background-color: #f6c23e; }
.badge-danger { background-color: #e74a3b; }

.text-gray-800 { color: #5a5c69 !important; }
.font-weight-bold { font-weight: 700 !important; }
.text-xs { font-size: .75rem; }
.text-uppercase { text-transform: uppercase !important; }

.card { border: 0; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important; }
</style>
@endpush