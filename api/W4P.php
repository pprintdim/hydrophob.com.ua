<?php
// WayForPay: підписи запиту/відповіді (порт хелпера з hydrophob.net, 1:1 логіка).

class W4P {
    const ORDER_APPROVED = 'Approved';
    const ORDER_DECLINED = 'Declined';
    const ORDER_REFUNDED = 'Refunded';
    const ORDER_EXPIRED  = 'Expired';
    const ORDER_HOLD_APPROVED = 'WaitingAuthComplete';

    const SIGNATURE_SEPARATOR = ';';
    const URL = 'https://secure.wayforpay.com/pay';

    protected string $secret_key = '';

    protected array $keysForResponseSignature = [
        'merchantAccount', 'orderReference', 'amount', 'currency',
        'authCode', 'cardPan', 'transactionStatus', 'reasonCode',
    ];

    protected array $keysForSignature = [
        'merchantAccount', 'merchantDomainName', 'orderReference', 'orderDate',
        'amount', 'currency', 'productName', 'productCount', 'productPrice',
    ];

    public function __construct(string $secret_key) {
        $this->secret_key = $secret_key;
    }

    public function getSignature(array $option, array $keys): string {
        $hash = [];
        foreach ($keys as $dataKey) {
            if (!isset($option[$dataKey])) {
                $option[$dataKey] = '';
            }
            if (is_array($option[$dataKey])) {
                foreach ($option[$dataKey] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash[] = $option[$dataKey];
            }
        }
        return hash_hmac('md5', implode(self::SIGNATURE_SEPARATOR, $hash), $this->secret_key);
    }

    public function getRequestSignature(array $options): string {
        return $this->getSignature($options, $this->keysForSignature);
    }

    public function getResponseSignature(array $options): string {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }

    public function getAnswerToGateWay(array $data): string {
        $response = [
            'orderReference' => $data['orderReference'] ?? '',
            'status'         => 'accept',
            'time'           => time(),
        ];
        $response['signature'] = hash_hmac('md5', implode(self::SIGNATURE_SEPARATOR, $response), $this->secret_key);
        return json_encode($response);
    }

    /** true = оплачено; рядок = причина відмови/помилки. */
    public function isPaymentValid(array $response) {
        if (!$response) {
            return 'Empty WayForPay response';
        }
        if (!isset($response['merchantSignature'])) {
            return $response['reason'] ?? 'Missing WayForPay signature';
        }
        if ($this->getResponseSignature($response) != $response['merchantSignature']) {
            return 'An error has occurred during payment';
        }
        return $response['transactionStatus'] == self::ORDER_APPROVED || $response['transactionStatus'] == self::ORDER_HOLD_APPROVED;
    }
}
