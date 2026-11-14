@php
    $doctor = request()->attributes->get('current_doctor') ?? ($doctor ?? null);

    $doctorName = $doctor?->full_name ?? 'Bác sĩ';

    $doctorInitial = mb_strtoupper(
        mb_substr(trim($doctorName), 0, 1)
    );
    $workspace = session('doctor_workspace_mode');
    $isServices = $workspace === 'services';
    $workspaceLabel = $isServices ? 'Bác sĩ thực hiện dịch vụ' : 'Bác sĩ khám bệnh';
@endphp

<header class="employee-header">

    <div class="employee-header__inner">

        {{-- LOGO --}}
        <a class="portal-brand" href="{{ route('doctor.home') }}" aria-label="Mediway - tổng quan bác sĩ">
            <span aria-hidden="true">✦</span>
            Mediway
        </a>

        {{-- TÀI KHOẢN VÀ ĐĂNG XUẤT --}}
        <div class="employee-header__right">

            <a class="employee-account" href="{{ route('doctor.profile') }}" aria-label="Hồ sơ của {{ $doctorName }}">

                <span class="employee-account__avatar" aria-hidden="true">
                    {{ $doctorInitial }}
                </span>

                <span class="employee-account__text">
                    <small>{{ $workspaceLabel }}</small>

                    <strong>
                        {{ $doctorName }}
                    </strong>
                </span>

            </a>

            @if (in_array($workspace, ['examination', 'services'], true))
                <x-workspace-switch :action="route('doctor.workspace.select')"
                    :workspace="$isServices ? 'examination' : 'services'"
                    :label="$isServices ? 'Bác sĩ khám bệnh' : 'Bác sĩ thực hiện dịch vụ'" />
            @endif

            <form method="post" action="{{ route('doctor.logout') }}">
                @csrf

                <button type="submit" class="portal-btn portal-btn--secondary employee-logout">
                    Đăng xuất
                </button>

            </form>

        </div>

    </div>

</header>
