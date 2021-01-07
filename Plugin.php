<?php namespace Chocolata\MollieMall;

use Chocolata\MollieMall\Classes\MollieMall;
use System\Classes\PluginBase;
use OFFLINE\Mall\Classes\Payments\PaymentGateway;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function boot()
    {
        $gateway = $this->app->get(PaymentGateway::class);
        $gateway->registerProvider(new MollieMall());
    }
}
