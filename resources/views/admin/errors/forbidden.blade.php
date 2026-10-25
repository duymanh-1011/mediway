@extends('admin.layouts.app')
@section('admin-page', 'errors-forbidden')
@section('title', 'Không thể thực hiện thao tác - Mediway')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Không thể thực hiện thao tác</strong>
@endsection
@section('content')
    <div class="page-header">
        <div><h1 class="page-title">Không thể thực hiện thao tác</h1></div>
        <a href="{{ $backUrl }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>
    <section class="admin-card" aria-label="Thông báo">
        <p>{{ \App\Models\AdminDisplay::text($message) }}</p>
    </section>
@endsection
