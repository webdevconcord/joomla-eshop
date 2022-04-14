<?php

// No direct access
defined('_JEXEC') or die();

// API service connection.
require_once 'ConcordPayApi.php';

/**
 * PHP version 7.2.34
 *
 * @category   Class
 * @package    EShop
 * @subpackage EShop
 * @author     ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright  2022 ConcordPay
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://concordpay.concord.ua
 * @since      3.8.0
 */
class os_concordpay extends os_payment
{
	// Order ID by table #__eshop_orderstatusdetails.
	public const CONCORDPAY_ORDER_STATUS_REFUNDED = 11;

    /**
     * Translatable phrases.
     *
     * @var string[]
     *
     * @since 3.8.0
     */
    protected static $phrases = [
        'Payment on the site' => [
            'en-GB' => 'Payment on the site',
            'ua-UK' => 'Оплата картой на сайті',
            'uk-UK' => 'Оплата картой на сайті',
            'ru-RU' => 'Оплата картой на сайте',
        ]
    ];

	/**
	 * @var ConcordPayApi
	 *
	 * @since version 3.8.0
	 */
	private $concordpay;

	/**
	 * @var string
	 *
	 * @since version 3.8.0
	 */
	private $merchantId;

	/**
	 * @var string
	 * @since version 3.8.0
	 */
	private $secretKey;

	/**
	 * @var string
	 * @since version 3.8.0
	 */
	private $language;

	/**
	 * Class constructor.
	 *
	 * @param $params
	 *
	 * @since version 3.8.0
	 */
	public function __construct($params)
	{
		$this->merchantId = $params->get('merchant_id');
		$this->secretKey  = $params->get('secret_key');
		$this->language   = $params->get('language');

		$this->concordpay = new ConcordPayApi($this->secretKey);

		$config = array(
			'type' => 0,
			'show_card_type' => false,
			'show_card_holder_name' => false
		);
		parent::__construct($params, $config);
	}

    /**
     * Payment processing.
     *
     * @param array $data Order info.
     *
     * @return void
     *
     * @throws Exception
     *
     * @since 3.8.0
     */
	public function processPayment($data)
	{
		$this->url = $this->concordpay->getApiUrl();

		$siteUrl   = JUri::root();
		$orderId   = $data['order_id'];
		$firstname = $data['firstname'];
		$lastname  = $data['lastname'];
		$phone     = $data['telephone'];
		$email     = $data['email'];

		$description = self::cpTranslate('Payment on the site') . ' ' . htmlspecialchars($_SERVER['HTTP_HOST'])
			. " , $firstname $lastname, $phone.";

		$approveUrl  = $siteUrl . 'index.php?option=com_eshop&view=checkout&layout=complete&id=' . $orderId;
		$declineUrl  = $siteUrl . 'index.php?option=com_eshop&view=checkout&layout=cancel&id=' . $orderId;
		$cancelUrl   = $siteUrl . 'index.php?option=com_eshop&view=checkout&layout=cancel&id=' . $orderId;
		$callbackUrl = $siteUrl . 'index.php?option=com_eshop&task=checkout.verifyPayment&payment_method=os_concordpay&type=notify';

		$orderInfo = [
			'operation'    => 'Purchase',
			'merchant_id'  => $this->merchantId,
			'amount'       => $data['total'],
			'order_id'     => $orderId,
			'currency_iso' => $data['currency_code'],
			'description'  => $description,
			'approve_url'  => $approveUrl,
			'decline_url'  => $declineUrl,
			'cancel_url'   => $cancelUrl,
			'callback_url' => $callbackUrl,
            'language'     => $this->language,
			// Statistics.
			'client_last_name'  => $firstname,
			'client_first_name' => $lastname,
			'phone' => $phone,
			'email' => $email,
		];

		$orderInfo['signature'] = $this->concordpay->getRequestSignature($orderInfo);

		$this->submitPost($this->url, $orderInfo);
	}

    /**
     * Process payment
     *
     * @return boolean
     *
     * @throws Exception
     *
     * @since 3.8.0
     */
	public function verifyPayment()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		$settings = [
			'merchant_id' => $this->merchantId,
		];
		$isPaymentValid = $this->concordpay->isPaymentValid($data, $settings);

		if ($isPaymentValid)
		{
			$errorMessages = '';

			// Check amount.
			$amount = $data['amount'] ?? '';

			if (empty($amount))
			{
				$errorMessages .= 'Error: Amount is incorrect.' . PHP_EOL;
			}

			// Check currency.
			$currency = $data['currency'] ?? '';

			if (empty($currency))
			{
				$errorMessages .= 'Error: Currency is incorrect.' . PHP_EOL;
			}

			// Check operation type.
			$type = $data['type'] ?? '';

			if (empty($type) || !in_array($type, $this->concordpay->getAllowedOperationTypes(), true))
			{
				$errorMessages .= 'Error: Unknown operation type.' . PHP_EOL;
			}

			/** @var OrderEshop $order */
			$order = JTable::getInstance('Eshop', 'Order');

			// Check order existing.
			$id = (int) $data['orderReference'];

			if (!$order->load($id))
			{
				$errorMessages .= 'Error: Order not found.' . PHP_EOL;
			}

			// Re-paid check.
			if ($data['type'] !== ConcordPayApi::RESPONSE_TYPE_REVERSE
				&& (int) $order->order_status_id === (int) $this->params->get('status_approved')
			)
			{
				$errorMessages .= 'Error: Order already completed.' . PHP_EOL;
			}

			// Terminate and show errors.
			if (!empty($errorMessages))
			{
				exit($errorMessages);
			}

			$statusApproved = empty($this->params->get('status_approved'))
				? (int) EshopHelper::getConfigValue('complete_status_id')
				: (int) $this->params->get('status_approved');
			$statusDeclined = empty($this->params->get('status_declined'))
				? (int) EshopHelper::getConfigValue('canceled_status_id')
				: (int) $this->params->get('status_declined');
			$statusRefunded = empty($this->params->get('status_refunded'))
				? self::CONCORDPAY_ORDER_STATUS_REFUNDED
				: (int) $this->params->get('status_refunded');

			// Update order status.
			if ($data['transactionStatus'] === ConcordPayApi::TRANSACTION_STATUS_APPROVED)
			{
				// Ordinary payment.
				if ($data['type'] === ConcordPayApi::RESPONSE_TYPE_PAYMENT)
				{
					$order->transaction_id  = $data['transactionId'];
					$order->order_status_id = $statusApproved;
					$order->store();

					EshopHelper::completeOrder($order);
					JPluginHelper::importPlugin('eshop');
                    JFactory::getApplication()->triggerEvent('onAfterCompleteOrder', array($order));

					// Send confirmation email here
					if (EshopHelper::getConfigValue('order_alert_mail'))
					{
						EshopHelper::sendEmails($order);
					}

					exit('Payment OK');
				}

				// Refunded payment.
				if ($data['type'] === ConcordPayApi::RESPONSE_TYPE_REVERSE)
				{
					$order->transaction_id  = $data['transactionId'];
					$order->order_status_id = $statusRefunded;
					$order->comment = 'Payment successfully refunded. ConcordPay transaction ID: ' . $order->transaction_id;
					$order->store();

					exit('Refunded OK');
				}
			}
		}

		return false;
	}

    /**
     * Custom translate method for plugin strings.
     *
     * @param $text
     * @return string|string[]
     * @throws Exception
     * @since 3.8.0
     */
    protected static function cpTranslate($text)
    {
        $lang = JFactory::getApplication()->getLanguage()->getTag();
        $translations = self::$phrases;

        return $translations[$text][$lang] ?? $text;
    }
}
