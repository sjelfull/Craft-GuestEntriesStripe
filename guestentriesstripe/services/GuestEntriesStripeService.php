<?php
namespace Craft;

class GuestEntriesStripeService extends BaseApplicationComponent
{
    protected $settings;

    public function init() {
        $plugin = craft()->plugins->getPlugin('guestentriesstripe');

        if (!$plugin) {
            throw new Exception('Couldn’t find the GuestEntriesStripe plugin!');
        }

        $this->settings = $plugin->getSettings();

    }

    public function getPublishKey()
    {
        $mode = ucfirst($this->settings->stripeAccountMode);
        $key = 'stripe' . $mode . 'CredentialsPK';

        return $this->settings->$key;
    }
}