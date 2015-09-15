<?php
namespace Craft;

/**
 *  event
 */
class GuestEntriesStripeEvent extends Event
{
	/**
	 * @var bool Whether the submission is valid.
	 */
	public $isValid = true;

	/**
	 * @var bool Is this a test payment?
	 */
	public $testPayment = false;
}