<?php

use App\Services\StripeGateway;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\InvalidRequestException;

/*
 * Only a Stripe "resource missing" answer counts as an already-gone
 * subscription; outages and other request errors do not.
 */

it('detects a resource_missing request error', function () {
    $e = InvalidRequestException::factory('No such subscription', 404, null, null, null, 'resource_missing');

    expect(StripeGateway::isResourceMissing($e))->toBeTrue();
});

it('ignores other invalid request errors', function () {
    $e = InvalidRequestException::factory('Bad', 400, null, null, null, 'parameter_invalid_empty');

    expect(StripeGateway::isResourceMissing($e))->toBeFalse();
});

it('ignores connection errors', function () {
    expect(StripeGateway::isResourceMissing(ApiConnectionException::factory('down')))->toBeFalse();
});

it('ignores non-Stripe errors', function () {
    expect(StripeGateway::isResourceMissing(new RuntimeException('boom')))->toBeFalse();
});

it('treats a live/test mode mismatch as a real failure, not a missing resource', function () {
    $e = InvalidRequestException::factory(
        "No such subscription: 'sub_x'; a similar object exists in live mode, but a test mode key was used to make this request.",
        404, null, null, null, 'resource_missing',
    );

    expect(StripeGateway::isModeMismatch($e))->toBeTrue()
        ->and(StripeGateway::isResourceMissing($e))->toBeFalse();
});

it('does not flag a plain resource_missing error as a mode mismatch', function () {
    $e = InvalidRequestException::factory("No such subscription: 'sub_x'", 404, null, null, null, 'resource_missing');

    expect(StripeGateway::isModeMismatch($e))->toBeFalse();
});
