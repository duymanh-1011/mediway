<?php

namespace Tests\Unit;

use App\Models\AuditValuePresenter;
use Tests\TestCase;

class AuditValuePresenterTest extends TestCase
{
    public function test_empty_history_is_not_displayed_as_json(): void
    {
        foreach ([null, '', [], '[]', '{}'] as $value) {
            $this->assertSame([], app(AuditValuePresenter::class)->rows($value));
        }
    }

    public function test_values_have_readable_labels_dates_money_flags_and_no_secrets_or_internal_paths(): void
    {
        $source = ['notes' => 'Nội dung lịch sử', 'amount' => '120000.00', 'is_active' => 0,
            'allows_transition' => 1, 'has_follow_up' => false, 'resulted_at' => '2026-10-22 10:30:00',
            'result_pdf_path' => 'service-results/private-secret.pdf', 'password' => 'secret-password',
            'metadata' => ['remember_token' => 'private-token']];
        $original = $source;
        $rows = app(AuditValuePresenter::class)->rows($source);
        $values = array_column($rows, 'value');
        foreach (['120.000 ₫', 'Ngừng hoạt động', 'Cho phép', 'Không', '22/10/2026 10:30:00', 'Có tệp đính kèm'] as $value) {
            $this->assertContains($value, $values);
        }
        foreach (['private-secret.pdf', 'secret-password', 'private-token', 'result_pdf_path', 'remember_token'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($rows));
        }
        $this->assertSame($original, $source);
        $this->assertContains('Thời điểm trả kết quả', array_column($rows, 'label'));
    }

    public function test_nested_values_remain_readable_without_changing_the_recorded_text(): void
    {
        $rows = app(AuditValuePresenter::class)->rows(['items' => [['notes' => '<script>test</script>']]]);
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Mục 1', $rows[0]['label']);
        $this->assertSame('<script>test</script>', $rows[0]['value']);
    }
}
