<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeePaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_headers_show_the_correct_identifiers_without_collection_date_or_time(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        $this->actingAs($cashier);
        $checkHeader = function ($response, array $expected): void {
            $response->assertOk()->assertSee('data-print-document', false)->assertDontSee('data-print-preview', false);
            $document = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $header = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' document-meta ')]")->item(0)->textContent;
            foreach ($expected as $value) $this->assertStringContainsString($value, $header);
            $this->assertStringNotContainsString('Ngày thu:', $header);
            $this->assertStringNotContainsString('Giờ thu:', $header);
            $this->assertStringContainsString('Ngày ', $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' sign-date ')]")->item(0)->textContent);
        };
        $payable = $transaction->payable;
        DB::table('payables')->where('id', $payable->id)->update(['payment_type' => 'EXAMINATION_FEE', 'amount' => $transaction->amount]);
        $checkHeader($this->get(route('employee.payments.receipt', $transaction)), [$transaction->transaction_code, $payable->medicalVisit->patient->patient_code]);
        $service = \App\Models\ServiceOrderItem::whereHas('payable')->firstOrFail();
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['payable_id' => $service->payable->id]);
        $checkHeader($this->get(route('employee.payments.receipt', $transaction)), [$transaction->transaction_code]);
        $prescription = \App\Models\Prescription::with('items')->whereHas('items')->firstOrFail();
        $total = 0;
        foreach ($prescription->items as $item) {
            $line = (float) $item->quantity * 2000;
            $total += $line;
            DB::table('prescription_items')->where('id', $item->id)->update(['unit_price' => 2000, 'line_total' => $line]);
        }
        DB::table('payables')->where('id', $payable->id)->update(['payment_type' => 'MEDICINE_FEE', 'prescription_id' => $prescription->id, 'medical_visit_id' => $prescription->medical_visit_id]);
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['payable_id' => $payable->id, 'amount' => $total]);
        $checkHeader($this->get(route('employee.payments.receipt', $transaction)), [$transaction->transaction_code, $prescription->prescription_code]);
    }

    public function test_exam_receipt_uses_the_patient_and_diagnoses_of_the_paid_visit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')
            ->whereHas('payable.medicalVisit.patient.user')->with('payable.medicalVisit.patient.user')->firstOrFail();
        $visit = $transaction->payable->medicalVisit;
        $patient = $visit->patient;
        DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => 'EXAMINATION_FEE', 'amount' => $transaction->amount]);
        DB::table('medical_visits')->where('id', $visit->id)->update(['checked_in_at' => '2026-10-15 09:00:00', 'examination_started_at' => null]);
        DB::table('patients')->where('id', $patient->id)->update(['date_of_birth' => '1990-10-16', 'gender' => 'FEMALE']);
        $patient->detail()->updateOrCreate([], ['current_address' => 'Địa chỉ kiểm thử riêng']);
        DB::table('users')->where('id', $patient->user_id)->update(['phone' => '0999999432']);
        $disease = \App\Models\Disease::firstOrFail();
        \App\Models\VisitDiagnosis::updateOrCreate(
            ['medical_visit_id' => $visit->id, 'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY'],
            ['diagnosis_description' => 'Chẩn đoán đúng lượt khám', 'diagnosed_at' => '2026-10-15 10:00:00']
        );
        $other = \App\Models\MedicalVisit::whereKeyNot($visit->id)->firstOrFail();
        \App\Models\VisitDiagnosis::updateOrCreate(
            ['medical_visit_id' => $other->id, 'disease_id' => $disease->id, 'diagnosis_type' => 'PRIMARY'],
            ['diagnosis_description' => 'Chẩn đoán của lượt khác', 'diagnosed_at' => '2026-10-15 10:00:00']
        );
        $before = DB::table('payment_transactions')->where('id', $transaction->id)->first();
        $response = $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())
            ->get(route('employee.payments.receipt', $transaction))->assertOk()
            ->assertSee('Địa chỉ kiểm thử riêng')->assertSee('0999999432')->assertSee('Chẩn đoán đúng lượt khám')
            ->assertDontSee('Chẩn đoán của lượt khác')->assertSeeText('Nữ');
        $this->assertMatchesRegularExpression('/Tuổi:\s*35/u', strip_tags($response->getContent()));
        $this->assertEquals($before, DB::table('payment_transactions')->where('id', $transaction->id)->first());
    }

    public function test_exam_receipt_displays_dash_for_missing_patient_data_and_diagnosis(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')
            ->whereHas('payable.medicalVisit.patient')->with('payable.medicalVisit.patient')->firstOrFail();
        $visit = $transaction->payable->medicalVisit;
        $patient = $visit->patient;
        DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => 'EXAMINATION_FEE', 'amount' => $transaction->amount]);
        DB::table('patients')->where('id', $patient->id)->update(['date_of_birth' => null, 'gender' => null, 'user_id' => null]);
        $patient->detail()->updateOrCreate([], ['current_address' => null]);
        DB::table('visit_diagnoses')->where('medical_visit_id', $visit->id)->delete();
        $html = $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())
            ->get(route('employee.payments.receipt', $transaction))->assertOk()->getContent();
        foreach (['Tuổi:', 'Giới tính:', 'Địa chỉ:', 'Số điện thoại:', 'Chẩn đoán:'] as $label) {
            $this->assertMatchesRegularExpression('/'.preg_quote($label, '/').'\s*—/u', strip_tags($html));
        }
    }

    public function test_receipt_reads_transaction_amount_in_vietnamese_without_modifying_financial_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $cashier = User::where('business_code', 'NV000004')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')
            ->whereHas('payable', fn ($q) => $q->where('payment_type', 'EXAMINATION_FEE'))->firstOrFail();
        $count = PaymentTransaction::count();
        $this->actingAs($cashier);
        foreach ([
            0 => 'Không đồng', 1 => 'Một đồng', 5 => 'Năm đồng', 10 => 'Mười đồng',
            15 => 'Mười lăm đồng', 21 => 'Hai mươi mốt đồng', 105 => 'Một trăm lẻ năm đồng',
            1005 => 'Một nghìn không trăm lẻ năm đồng', 10005 => 'Mười nghìn không trăm lẻ năm đồng',
            100000 => 'Một trăm nghìn đồng', 120000 => 'Một trăm hai mươi nghìn đồng',
            1000001 => 'Một triệu không trăm lẻ một đồng', 1400000 => 'Một triệu bốn trăm nghìn đồng',
            10000000 => 'Mười triệu đồng', 100000000 => 'Một trăm triệu đồng', 1000000000 => 'Một tỷ đồng',
        ] as $amount => $words) {
            DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => $amount]);
            DB::table('payables')->where('id', $transaction->payable_id)->update(['amount' => $amount]);
            $this->get(route('employee.payments.receipt', $transaction))->assertOk()
                ->assertSeeText($words)->assertSee($transaction->transaction_code)->assertSee(number_format($amount, 0, ',', '.'));
            $this->assertSame($count, PaymentTransaction::count());
        }
    }

    public function test_receipt_requires_authorization_and_successful_cash_transaction(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->firstOrFail();
        $url = route('employee.payments.receipt', $transaction);
        $this->actingAs(User::where('business_code', 'NV000001')->firstOrFail())->get($url)->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        foreach ([['transaction_result' => 'FAILED', 'payment_method' => 'CASH'], ['transaction_result' => 'SUCCESS', 'payment_method' => 'QR']] as $values) {
            DB::table('payment_transactions')->where('id', $transaction->id)->update($values);
            $this->get($url)->assertNotFound();
        }
    }

    public function test_receipt_maps_each_fee_type_and_uses_the_payable_description_and_transaction_amount(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        foreach (['EXAMINATION_FEE' => 'Phí khám', 'SERVICE_FEE' => 'Phí dịch vụ kỹ thuật', 'MEDICINE_FEE' => 'Tiền thuốc'] as $type => $label) {
            DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => $type, 'description' => $label, 'amount' => 999999]);
            DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => 999999]);
            if ($type !== 'EXAMINATION_FEE') {
                $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
                continue;
            }
            $html = $this->get(route('employee.payments.receipt', $transaction))->assertOk()->getContent();
            $document = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $header = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' document-meta ')]")->item(0)->textContent;
            $this->assertStringContainsString($transaction->transaction_code, $header);
            $this->assertStringContainsString($transaction->payable->medicalVisit->patient->patient_code, $header);
            $this->assertStringNotContainsString('Ngày thu:', $header);
            $this->assertStringNotContainsString('Giờ thu:', $header);
            $this->assertSame('HÓA ĐƠN', trim($xpath->query('//h1')->item(0)->textContent));
            $this->assertStringContainsString('I. Thông tin bệnh nhân', strip_tags($html));
            $this->assertStringContainsString('II. Chi phí khám, chữa bệnh', strip_tags($html));
            $this->assertStringNotContainsString('PHIẾU THU TIỀN KIÊM BẢNG KÊ', $html);
            $this->assertStringContainsString('Phí khám bệnh', $html);
            $this->assertStringContainsString('999.999', $xpath->query("//table[contains(@class,'print-table')]/tfoot")->item(0)->textContent);
            $this->assertStringContainsString('Chín trăm chín mươi chín nghìn chín trăm chín mươi chín đồng', $html);
            $this->assertSame(6, $xpath->query("//table[contains(@class,'print-table')]/thead/tr/th")->length);
            $this->assertSame(6, $xpath->query("//table[contains(@class,'print-table')]/tbody/tr[1]/*")->length);
            $this->assertSame(1, $xpath->query("//table[contains(@class,'print-table')]/tbody/tr")->length);
            $this->assertStringContainsString('TỔNG CHI PHÍ KHÁM, CHỮA BỆNH', $html);
            $this->assertStringNotContainsString('Cộng 1', $html);
            $this->assertStringNotContainsString('Mã lượt khám', $html);
            $this->assertStringNotContainsString('In lần 1', $html);
            $this->assertStringNotContainsString('Chưa có dữ liệu phân bổ BHYT', $html);
            $this->assertStringNotContainsString('TỔNG SỐ TIỀN ĐÃ THU', $html);
            DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => 150000]);
            $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
        }
    }

    public function test_receipt_displays_only_the_service_belonging_to_the_payable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $item = \App\Models\ServiceOrderItem::with(['medicalService', 'serviceOrder.orderedByDoctor', 'payable.medicalVisit.patient.user', 'payable.medicalVisit.specialty', 'payable.medicalVisit.examinationRoom'])->whereHas('medicalService')->whereHas('payable.medicalVisit.patient.user')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['payable_id' => $item->payable->id, 'amount' => $item->price_at_order]);
        $transaction->refresh();
        $patient = $item->payable->medicalVisit->patient;
        DB::table('patients')->where('id', $patient->id)->update(['date_of_birth' => '1990-01-01', 'gender' => 'FEMALE']);
        $patient->detail()->updateOrCreate([], ['current_address' => 'Địa chỉ dịch vụ thử nghiệm']);
        DB::table('users')->where('id', $patient->user_id)->update(['phone' => '0999999433']);
        $response = $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())
            ->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee('HÓA ĐƠN')->assertSee('Hóa đơn dịch vụ')
            ->assertSee($item->medicalService->name)->assertSeeText('Hình thức nộp tiền:')->assertDontSee('Tổng số tiền đã thu:')
            ->assertSee('Địa chỉ dịch vụ thử nghiệm')->assertSee('0999999433')->assertSeeText('Nữ')
            ->assertSee(number_format((float) $item->price_at_order, 0, ',', '.'))
            ->assertSee(number_format((float) $transaction->amount, 0, ',', '.'))->assertDontSee('Ngày chỉ định:')->assertDontSee('Mã chỉ định dịch vụ:')
            ->assertDontSee('Mã lượt khám')->assertSeeText('I. Thông tin bệnh nhân')->assertSeeText('II. Chi tiết dịch vụ')->assertSeeText('III. Thông tin giao dịch')->assertDontSee('Hóa đơn thuốc');
        if ($item->serviceOrder->orderedByDoctor) $response->assertSee($item->serviceOrder->orderedByDoctor->full_name);
        if ($item->medicalService->unit) $response->assertSee($item->medicalService->unit);
        if ($item->payable->medicalVisit->specialty) $response->assertSee($item->payable->medicalVisit->specialty->name);
        $referenceDate = $item->payable->medicalVisit->examination_started_at ?? $item->payable->medicalVisit->checked_in_at ?? now();
        $expectedAge = (int) \Carbon\Carbon::parse('1990-01-01')->diffInYears($referenceDate);
        $this->assertMatchesRegularExpression('/Tuổi:\s*'.$expectedAge.'/u', strip_tags($response->getContent()));
        $otherService = \App\Models\ServiceOrderItem::with('medicalService')->where('service_order_id', $item->service_order_id)
            ->where('medical_service_id', '!=', $item->medical_service_id)->whereHas('medicalService')->first();
        if ($otherService && $otherService->medicalService->name !== $item->medicalService->name) {
            $response->assertDontSee($otherService->medicalService->name);
        }
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $header = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' document-meta ')]")->item(0)->textContent;
        $this->assertStringContainsString($transaction->transaction_code, $header);
        $this->assertStringNotContainsString('Ngày thu:', $header);
        $this->assertStringNotContainsString('Giờ thu:', $header);
        $this->assertSame(1, $xpath->query("//section[@aria-labelledby='receipt-transaction']//dt[contains(., 'Hình thức nộp tiền')]")->length);
        $this->assertSame(6, $xpath->query("//table[contains(@class,'print-table')]/thead/tr/th")->length);
        $this->assertSame('—', trim($xpath->query("//table[contains(@class,'print-table')]/tbody/tr[1]/*[4]")->item(0)->textContent));
        $this->assertSame(1, substr_count(strip_tags($response->getContent()), 'Hình thức nộp tiền:'));
        DB::table('patients')->where('id', $patient->id)->update(['date_of_birth' => null, 'gender' => null, 'user_id' => null]);
        $patient->detail()->update(['current_address' => null]);
        $missingHtml = $this->get(route('employee.payments.receipt', $transaction))->assertOk()->getContent();
        foreach (['Tuổi:', 'Giới tính:', 'Địa chỉ:', 'Số điện thoại:'] as $label) {
            $this->assertMatchesRegularExpression('/'.preg_quote($label, '/').'\s*—/u', strip_tags($missingHtml));
        }
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => (float) $item->price_at_order + 1]);
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
    }

    public function test_receipt_displays_actual_prescription_items_without_other_visits_items(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $prescription = \App\Models\Prescription::with('items.medicine')->whereHas('items')->firstOrFail();
        $total = 0;
        foreach ($prescription->items as $item) {
            $line = (float) $item->quantity * 2000;
            $total += $line;
            DB::table('prescription_items')->where('id', $item->id)->update(['unit_price' => '2000.00', 'line_total' => $line]);
        }
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => 'MEDICINE_FEE', 'prescription_id' => $prescription->id, 'medical_visit_id' => $prescription->medical_visit_id]);
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => $total]);
        DB::table('prescriptions')->where('id', $prescription->id)->update(['general_instructions' => "Lời dặn đúng đơn.\nDòng thứ hai."]);
        DB::table('prescriptions')->where('id', '!=', $prescription->id)->update(['general_instructions' => 'Lời dặn của đơn khác.']);
        $transactionBefore = DB::table('payment_transactions')->where('id', $transaction->id)->first();
        $response = $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())->get(route('employee.payments.receipt', $transaction))
            ->assertOk()->assertSee('HÓA ĐƠN')->assertSee('HÓA ĐƠN TIỀN THUỐC')->assertSee($prescription->prescription_code)->assertDontSee('Hóa đơn dịch vụ')
            ->assertSee(number_format($total, 0, ',', '.'));
        foreach ($prescription->items as $item) $response->assertSee($item->medicine->name)->assertSee(number_format((float) $item->quantity * 2000, 0, ',', '.'));
        $response->assertSee('Bác sĩ kê đơn')->assertSee('Chẩn đoán')
            ->assertSee('NGƯỜI GIAO THUỐC')->assertSee('BỆNH NHÂN / NGƯỜI NHẬN THUỐC')->assertSee('NGƯỜI THU TIỀN')
            ->assertSee('Lưu ý chung')->assertSee('Lời dặn của bác sĩ')
            ->assertSee('Lời dặn đúng đơn.')->assertSee('Dòng thứ hai.')->assertDontSee('Lời dặn của đơn khác.')
            ->assertSeeText('I. Thông tin bệnh nhân');
        $this->assertEquals($transactionBefore, DB::table('payment_transactions')->where('id', $transaction->id)->first());
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $info = $xpath->query("//section[@aria-labelledby='receipt-patient']//dl")->item(0)->textContent;
        foreach (['Nhân viên thu tiền:', 'Phương thức:', 'Ngày thanh toán:', 'Giờ thanh toán:', 'Mã bệnh nhân:', 'Ngày kê đơn:', 'Mã đơn thuốc:'] as $label) {
            $this->assertStringNotContainsString($label, $info);
        }
        $cells = $xpath->query("//section[@aria-labelledby='receipt-patient']//dt");
        foreach (['Họ và tên:', 'Ngày sinh:', 'Giới tính:', 'Tuổi:', 'Số điện thoại:', 'Địa chỉ:', 'Chẩn đoán:', 'Bác sĩ kê đơn:'] as $index => $label) {
            $this->assertStringContainsString($label, $cells->item($index)->textContent);
        }
        $meta = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' document-meta ')]")->item(0)->textContent;
        $this->assertStringContainsString('Mã đơn thuốc:', $meta);
        $this->assertStringContainsString($prescription->prescription_code, $meta);
        $response->assertSeeInOrder(['</table>', 'Lời dặn của bác sĩ', 'Lưu ý chung', 'NGƯỜI GIAO THUỐC'], false);
        $this->assertStringContainsString('Số giao dịch:', $response->getContent());
        $this->assertStringContainsString($transaction->transaction_code, $meta);
        $this->assertStringNotContainsString('Ngày thu:', $meta);
        $this->assertStringNotContainsString('Giờ thu:', $meta);

        DB::table('prescriptions')->where('id', $prescription->id)->update(['general_instructions' => null]);
        $this->get(route('employee.payments.receipt', $transaction))->assertOk()->assertSee('Chưa có lời dặn riêng.');

        DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => $total + 1]);
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['amount' => $total]);
        DB::table('prescription_items')->where('id', $prescription->items->first()->id)->update(['line_total' => 1]);
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
    }

    public function test_patient_and_guest_cannot_view_another_patients_receipt_by_id(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $patient = User::where('business_code', 'BN000001')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')
            ->whereHas('payable.medicalVisit', fn ($query) => $query->where('patient_id', '!=', $patient->patient->id))
            ->firstOrFail();
        $url = route('employee.payments.receipt', $transaction);
        $this->get($url)->assertRedirect(route('patient.login'));
        $this->actingAs($patient)->get($url)->assertForbidden()->assertDontSee($transaction->transaction_code);
    }

    public function test_receipt_rejects_mismatched_visit_and_missing_service_price(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $item = \App\Models\ServiceOrderItem::with(['serviceOrder', 'payable'])->whereHas('payable')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->firstOrFail();
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['payable_id' => $item->payable->id]);
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        $otherVisit = \App\Models\MedicalVisit::whereKeyNot($item->serviceOrder->medical_visit_id)->firstOrFail();
        DB::table('payables')->where('id', $item->payable->id)->update(['medical_visit_id' => $otherVisit->id]);
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422)->assertDontSee($item->medicalService?->name ?? 'NO-MATCH');
    }

    public function test_service_receipt_builder_rejects_missing_price_even_if_an_item_is_present(): void
    {
        $this->seed(DatabaseSeeder::class);
        $item = \App\Models\ServiceOrderItem::with(['medicalService.serviceGroups', 'serviceOrder.orderedByDoctor', 'payable'])
            ->whereHas('payable')->whereHas('medicalService')->firstOrFail();
        $payable = $item->payable;
        $item->price_at_order = null; // Model-only fixture; the schema normally forbids NULL.
        $payable->setRelation('serviceOrderItem', $item);
        try {
            app(\App\Support\Payments\ReceiptDataBuilder::class)->build($payable, 'service');
            $this->fail('Missing service price must not produce a receipt.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_receipt_rejects_incomplete_prescription_prices_without_writing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $prescription = \App\Models\Prescription::with('items')->whereHas('items')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => 'MEDICINE_FEE', 'prescription_id' => $prescription->id, 'medical_visit_id' => $prescription->medical_visit_id]);
        DB::table('prescription_items')->where('id', $prescription->items->first()->id)->update(['unit_price' => null]);
        $before = DB::table('payment_transactions')->where('id', $transaction->id)->first();
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail())->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
        $this->assertEquals($before, DB::table('payment_transactions')->where('id', $transaction->id)->first());
    }

    public function test_receipt_rejects_empty_or_cross_visit_prescription(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();
        $prescription = \App\Models\Prescription::with('items')->whereHas('items')->firstOrFail();
        $transaction = PaymentTransaction::where('transaction_result', 'SUCCESS')->where('payment_method', 'CASH')->whereHas('payable')->firstOrFail();
        $other = \App\Models\MedicalVisit::whereKeyNot($prescription->medical_visit_id)->firstOrFail();
        DB::table('payables')->where('id', $transaction->payable_id)->update(['payment_type' => 'MEDICINE_FEE', 'prescription_id' => $prescription->id, 'medical_visit_id' => $other->id]);
        $this->actingAs(User::where('business_code', 'NV000004')->firstOrFail());
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
        DB::table('payables')->where('id', $transaction->payable_id)->update(['medical_visit_id' => $prescription->medical_visit_id]);
        DB::table('prescription_items')->where('prescription_id', $prescription->id)->delete();
        $this->get(route('employee.payments.receipt', $transaction))->assertStatus(422);
    }
}
