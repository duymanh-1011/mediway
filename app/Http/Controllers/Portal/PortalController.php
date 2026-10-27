<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function placeholder()
    {
        return view('portal.home');
    }

    public function notifications(Request $request)
    {
        $categories = ['tat-ca' => 'Tất cả', 'he-thong' => 'Hệ thống', 'lich-hen' => 'Lịch hẹn', 'kham-benh' => 'Khám bệnh', 'ket-qua' => 'Kết quả', 'thanh-toan' => 'Thanh toán'];
        $category = $request->query('danh-muc', 'tat-ca');
        $category = is_string($category) && array_key_exists($category, $categories) ? $category : 'tat-ca';

        return view('portal.notifications', compact('categories', 'category'));
    }
}
