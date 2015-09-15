<?php
namespace Craft;

class GuestEntriesStripeVariable
{
    public function getPublishKey()
    {
        return craft()->guestEntriesStripe->getPublishKey();
    }
}