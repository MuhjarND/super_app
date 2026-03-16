@extends('layouts.app')

@section('title', $module)

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center;">
                            <i class="{{ $icon }}" style="font-size: 0.9rem; color: #3b82f6;"></i>
                        </div>
                        {{ $module }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ $module }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card" style="border: 1px solid #e5e7eb; text-align: center;">
                <div class="card-body py-5 px-4">
                    <div
                        style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                        <i class="{{ $icon }}" style="font-size: 2rem; color: #3b82f6;"></i>
                    </div>
                    <h4 style="font-weight: 700; color: #111827; margin-bottom: 8px;">Modul {{ $module }}</h4>
                    <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 24px;">{{ $description }}</p>
                    <div
                        style="display: inline-flex; align-items: center; gap: 8px; background: #fef3c7; padding: 8px 20px; border-radius: 50px;">
                        <i class="fas fa-hard-hat" style="color: #d97706; font-size: 0.85rem;"></i>
                        <span style="color: #92400e; font-weight: 600; font-size: 0.85rem;">Sedang Dalam Pengembangan</span>
                    </div>
                    <div class="mt-4">
                        <div class="progress" style="height: 6px; border-radius: 10px; background: #f3f4f6;">
                            <div class="progress-bar" role="progressbar"
                                style="width: 15%; background: linear-gradient(90deg, #3b82f6, #60a5fa); border-radius: 10px;"
                                aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small style="color: #9ca3af; margin-top: 8px; display: block;">Progress: 15%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection