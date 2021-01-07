<?php
namespace Chocolata\MollieMall\Classes;

use OFFLINE\Mall\Models\PaymentGatewaySettings;
use Omnipay\Omnipay;
use Omnipay\Mollie;
use OFFLINE\Mall\Classes\Payments\PaymentResult;
use RainLab\Translate\Classes\Translator;
use Request;
use Session;
use Throwable;
use Validator;

class MollieMall extends \OFFLINE\Mall\Classes\Payments\PaymentProvider
{
    /**
     * The order that is being paid.
     *
     * @var \OFFLINE\Mall\Models\Order
     */
    public $order;
    /**
     * Data that is needed for the payment.
     * Card numbers, tokens, etc.
     *
     * @var array
     */
    public $data;

    /**
     * Return the display name of your payment provider.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Mollie';
    }

    /**
     * Return a unique identifier for this payment provider.
     *
     * @return string
     */
    public function identifier(): string
    {
        return 'mollie';
    }

    /**
     * Validate the given input data for this payment.
     *
     * @return bool
     * @throws \October\Rain\Exception\ValidationException
     */
    public function validate(): bool
    {

        $rules = [

        ];

        $validation = \Validator::make($this->data, $rules);
        if ($validation->fails()) {
            throw new \October\Rain\Exception\ValidationException($validation);
        }

        return true;
    }

    /**
     * Return any custom backend settings fields.
     *
     * These fields will be rendered in the backend
     * settings page of your provider.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'api_key'     => [
                'label'   => 'API-Key',
                'comment' => 'The API Key for the payment service',
                'span'    => 'left',
                'type'    => 'text',
            ],
        ];
    }

    /**
     * Setting keys returned from this method are stored encrypted.
     *
     * Use this to store API tokens and other secret data
     * that is needed for this PaymentProvider to work.
     *
     * @return array
     */
    public function encryptedSettings(): array
    {
        return ['api_key'];
    }

    /**
     * Process the payment.
     *
     * @param PaymentResult $result
     *
     * @return PaymentResult
     */

    public function process(PaymentResult $result): PaymentResult
    {
        $gateway = \Omnipay\Omnipay::create('Mollie');
        $gateway->setApiKey(decrypt(PaymentGatewaySettings::get('api_key')));


        $response = $gateway->purchase(
            [
                'amount'    => $this->order->total_in_currency,
                'currency'  => $this->order->currency['code'],
                'description' => '#'.$this->order->id,
                'billingEmail' => $this->order->customer->user->email,
                'metadata' => [
                    'order_id' => $this->order->id,
                    'payment_hash' => $this->order->payment_hash,
                ],
                'returnUrl' => $this->returnUrl(),
                'cancelUrl' => $this->cancelUrl(),
            ]
        )->send();

        // This example assumes that if no redirect response is returned, something went wrong.
        // Maybe there is a case, where a payment can succeed without a redirect?
        if ( ! $response->isRedirect()) {
            return $result->fail((array)$response->getData(), $response);
        }

        Session::put('mall.payment.callback', self::class);
        return $result->redirect($response->getRedirectResponse()->getTargetUrl());
    }

    public function complete(PaymentResult $result): PaymentResult
    {
        $data = $this->setOrder($result->order);

        // Using the purchase example from the mollie docs, you don't have to do anything here.
        // Just return $result->success to mark your purchase as successful.
        // If you are using the "Order API" example, you would call $gateway->completeOrder here.
        return $result->success((array)$data, null);
    }
}