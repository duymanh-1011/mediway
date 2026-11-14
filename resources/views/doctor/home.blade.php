<x-workspace-picker
    :account="$employee->full_name" role="Bác sĩ" area="Khu vực bác sĩ"
    :select-url="route('doctor.workspace.select')" :logout-url="route('doctor.logout')"
    :choices="[
        ['value' => 'examination', 'icon' => 'reception', 'title' => 'Bác sĩ khám bệnh', 'description' => 'Theo dõi hàng đợi, gọi bệnh nhân và cập nhật hồ sơ lượt khám.'],
        ['value' => 'services', 'icon' => 'services', 'title' => 'Bác sĩ thực hiện dịch vụ', 'description' => 'Nhận chỉ định, ghi kết quả và xác nhận hoàn tất dịch vụ.'],
    ]"
/>
