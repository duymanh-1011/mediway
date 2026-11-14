<x-workspace-picker
    :account="$employee->full_name" role="Nhân viên" area="Khu vực nhân viên"
    :select-url="route('employee.workspace.select')" :logout-url="route('employee.logout')"
    :choices="[
        ['value' => 'reception', 'icon' => 'reception', 'title' => 'Nhân viên tiếp đón', 'description' => 'Tra cứu lịch hẹn và tiếp nhận bệnh nhân đến khám.'],
        ['value' => 'cashier', 'icon' => 'payment', 'title' => 'Nhân viên thu ngân', 'description' => 'Tra cứu khoản phải thu, ghi nhận thanh toán và in biên lai.'],
    ]"
/>
