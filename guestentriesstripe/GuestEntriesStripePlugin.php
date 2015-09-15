<?php

namespace Craft;

use Stripe\Stripe;

class GuestEntriesStripePlugin extends BasePlugin
{
    function getName ()
    {
        return Craft::t('Guest Entries Stripe');
    }


    public function init ()
    {
        require CRAFT_PLUGINS_PATH . 'guestentriesstripe/vendor/autoload.php';
        $settings = $this->getSettings();

        craft()->on('guestEntries.beforeSave', function (GuestEntriesEvent $event) use ($settings) {
            $entryModel = $event->params['entry'];
            $postData = $event->params['post'];

            // Test cards
            // https://stripe.com/docs/testing
            // https://github.com/stripe/jquery.payment
            // https://stripe.com/docs/tutorials/charges
            // https://stripe.com/docs/api#pagination
            // http://www.larryullman.com/2012/12/05/writing-the-javascript-code-for-handling-stripe-payments/

            if ($settings->enabled) {
                $mode = ucfirst($settings->stripeAccountMode);
                $key = 'stripe' . $mode . 'CredentialsSK';
                Stripe::setApiKey($settings->$key);
                $token = isset($postData['stripeToken']) ? $postData['stripeToken'] : null;

                // If we have a token in the post data
                if ($token) {
                    // Try to charge token

                    try {
                        $charge = \Stripe\Charge::create(array(
                                "amount" => 10000, // amount in cents, again
                                "currency" => $settings->stripeDefaultCurrency,
                                "source" => $token,
                                "description" => "Example charge")
                        );

                        Craft::import('plugins.guestentriesstripe.events.GuestEntriesStripeEvent');
                        $event = new GuestEntriesStripeEvent($this, array('entry' => $entryModel, 'post' => craft()->request->getPost()));
                        craft()->guestEntriesStripe->onBeforeSave($event);
                    } catch(\Stripe\Error\Card $e) {
                        // The card has been declined
                        $entryModel->addError('stripePayment', sprintf('Stripe payment error (%s): %s', $e->getCode(), $e->getMessage()));
                        $event->isValid = false;
                    } catch (\Stripe\Error\ApiConnection $e) {
                        // Network communication with Stripe failed
                    } catch (\Stripe\Error\Base $e) {
                        // Display a very generic error to the user, and maybe send
                        // yourself an email
                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe
                    }

                }
            }
        });
    }

    protected function _parseStripeError ($code)
    {

    }

    function getVersion ()
    {
        return '0.1';
    }

    function getDeveloper ()
    {
        return 'Fred Carlsen';
    }

    function getDeveloperUrl ()
    {
        return 'http://sjelfull.no';
    }

    public function getSettingsHtml ()
    {
        $currencies = array();

        foreach(GuestEntriesStripePlugin::getCurrencies('all') as $key => $currency) {
            $currencies[strtoupper($key)] = strtoupper($key) . ' - '. $currency['name'];
        }

        return craft()->templates->render('guestentriesstripe/_settings', array(
            'settings' => $this->getSettings(),
            'currencies' => $currencies,
            'accountModes'	=> array('test' => 'Test Mode', 'live' => 'Live Mode')
        ));
    }

    protected function defineSettings ()
    {
        return array(
            'enabled'                 => array( AttributeType::Bool, 'default' => false ),
            'stripeAccountMode'       => array( AttributeType::String, 'required' => true ),
            'stripeTestCredentialsSK' => array( AttributeType::String, 'required' => true ),
            'stripeTestCredentialsPK' => array( AttributeType::String, 'required' => true ),
            'stripeLiveCredentialsSK' => array( AttributeType::String, 'required' => true ),
            'stripeLiveCredentialsPK' => array( AttributeType::String, 'required' => true ),
            'stripeDefaultCurrency' 	=> array(AttributeType::String, 'required' => true),
        );
    }

    // Currency list borrowed from Charge
    public static function getCurrencies($key = 'all')
    {
        $key = strtolower($key);

        $defaultCurrency = 'usd';

        $supportedCurrencies = array(  		'usd' => array('name' => 'American Dollar', 'symbol' => '&#36;', 'symbol_long' => 'US&#36;', 'default' => true),
                                             'gbp' => array('name' => 'British Pound Sterling', 'symbol' => '&#163;', 'symbol_long' => '&#163;'),
                                             'eur' => array('name' => 'Euro', 'symbol' => '&#128;', 'symbol_long' => '&#128;'),
                                             'cad' => array('name' => 'Canadian Dollars', 'symbol' => '&#36;', 'symbol_long' => 'CA&#36;'),
                                             'aud' => array('name' => 'Australian Dollar', 'symbol' => '&#36;', 'symbol_long' => 'AU&#36;'),
                                             'hkd' => array('name' => 'Hong Kong Dollar', 'symbol' => '&#36;', 'symbol_long' => 'HK&#36;'),
                                             'sek' => array('name' => 'Swedish Krona', 'symbol' => ':-', 'symbol_long' => 'kr'),
                                             'nok' => array('name' => 'Norwegian Kroner', 'symbol' => ':-', 'symbol_long' => 'kr'),
                                             'dkk' => array('name' => 'Danish Krone', 'symbol' => ',-', 'symbol_long' => 'dkr'),
                                             'pen' => array('name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/.', 'symbol_long' => 'S/.'),
                                             'jpy' => array('name' => 'Japanese Yen', 'symbol' => '&#165;', 'symbol_long' => '&#165;') );

        if($key == 'all') return $supportedCurrencies;

        if(!isset($supportedCurrencies[$key])) return $supportedCurrencies[$defaultCurrency];

        return $supportedCurrencies[$key];
    }


}