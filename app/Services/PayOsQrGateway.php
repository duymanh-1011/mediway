<?php

namespace App\Services;

use App\Models\QrPayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PayOsQrGateway
{
    public function create(QrPayment $payment): array
    {
        $url = rtrim((string) config('app.url'), '/').route('employee.payments.qr.show', $payment, absolute: false);
        $data = [
            'amount' => $payment->amount,
            'cancelUrl' => $url,
            'description' => 'MD'.strtoupper(base_convert((string) $payment->order_code, 10, 36)),
            'orderCode' => $payment->order_code,
            'returnUrl' => $url,
        ];
        $result = $this->data($this->client()->post('/v2/payment-requests', [
            ...$data, 'expiredAt' => $payment->expires_at->timestamp, 'signature' => $this->signature($data),
        ]));
        if (($result['orderCode'] ?? null) !== $payment->order_code || ($result['amount'] ?? null) !== $payment->amount
            || ($result['currency'] ?? null) !== 'VND' || ($result['status'] ?? null) !== 'PENDING'
            || ! is_string($result['paymentLinkId'] ?? null) || blank($result['paymentLinkId']) || strlen($result['paymentLinkId']) > 80
            || ! is_string($result['qrCode'] ?? null) || blank($result['qrCode']) || strlen($result['qrCode']) > 1024) {
            $this->unavailable();
        }

        return ['provider_link_id' => $result['paymentLinkId'], 'qr_payload' => $result['qrCode']];
    }

    public function lookup(QrPayment $payment): array
    {
        $data = $this->data($this->client()->get($this->path($payment)));
        if (($data['orderCode'] ?? null) !== $payment->order_code || ($data['amount'] ?? null) !== $payment->amount
            || ! is_string($data['id'] ?? null) || ($payment->provider_link_id && $data['id'] !== $payment->provider_link_id)) {
            $this->unavailable();
        }

        return $data;
    }

    public function cancel(QrPayment $payment): array
    {
        $data = $this->lookup($payment);
        if (($data['status'] ?? null) === 'PENDING' && ($data['amountPaid'] ?? null) === 0) {
            $this->client()->post($this->path($payment).'/cancel', ['cancellationReason' => 'Doi phuong thuc hoac danh sach']);
            $data = $this->lookup($payment);
        }

        return $data;
    }

    public function verifiedWebhook(array $payload): array
    {
        abort_unless($this->isSigned($payload['data'] ?? null, $payload['signature'] ?? null), 400, 'Chữ ký giao dịch QR không hợp lệ.');

        return $payload['data'];
    }

    private function path(QrPayment $payment): string
    {
        return '/v2/payment-requests/'.rawurlencode($payment->provider_link_id ?? (string) $payment->order_code);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api-merchant.payos.vn')->acceptJson()->connectTimeout(3)->timeout(7)
            ->withHeaders([
                'x-client-id' => (string) config('services.cashier_qr.client_id'),
                'x-api-key' => (string) config('services.cashier_qr.api_key'),
            ]);
    }

    private function data(Response $response): array
    {
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ($body['code'] ?? null) !== '00'
            || ! $this->isSigned($body['data'] ?? null, $body['signature'] ?? null)) {
            $this->unavailable();
        }

        return $body['data'];
    }

    private function isSigned(mixed $data, mixed $signature): bool
    {
        return filled(config('services.cashier_qr.checksum_key')) && is_array($data) && is_string($signature)
            && hash_equals($this->signature($data), $signature);
    }

    private function signature(array $data): string
    {
        ksort($data, SORT_STRING);
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode(array_map(function ($item) {
                    if (is_array($item)) {
                        ksort($item, SORT_STRING);
                    }

                    return $item;
                }, $value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if ($value === null || in_array($value, ['null', 'undefined'], true)) {
                $value = '';
            }
            $fields[] = $key.'='.$value;
        }

        return hash_hmac('sha256', implode('&', $fields), (string) config('services.cashier_qr.checksum_key'));
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['qr' => 'Chưa xác minh được phản hồi từ payOS. Hãy kiểm tra lại giao dịch trước khi thu tiền theo cách khác.']);
    }
}
