<?php

// File generated from our OpenAPI spec

namespace EDD\Vendor\Stripe\Service\BillingPortal;

/**
 * @phpstan-import-type RequestOptionsArray from \EDD\Vendor\Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \EDD\Vendor\Stripe\Util\RequestOptions
 */
class ConfigurationService extends \EDD\Vendor\Stripe\Service\AbstractService
{
    /**
     * Returns a list of configurations that describe the functionality of the customer
     * portal.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\EDD\Vendor\Stripe\Util\RequestOptions $opts
     *
     * @throws \EDD\Vendor\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \EDD\Vendor\Stripe\Collection<\EDD\Vendor\Stripe\BillingPortal\Configuration>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/billing_portal/configurations', $params, $opts);
    }

    /**
     * Creates a configuration that describes the functionality and behavior of a
     * PortalSession.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\EDD\Vendor\Stripe\Util\RequestOptions $opts
     *
     * @throws \EDD\Vendor\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \EDD\Vendor\Stripe\BillingPortal\Configuration
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/billing_portal/configurations', $params, $opts);
    }

    /**
     * Retrieves a configuration that describes the functionality of the customer
     * portal.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\EDD\Vendor\Stripe\Util\RequestOptions $opts
     *
     * @throws \EDD\Vendor\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \EDD\Vendor\Stripe\BillingPortal\Configuration
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/billing_portal/configurations/%s', $id), $params, $opts);
    }

    /**
     * Updates a configuration that describes the functionality of the customer portal.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\EDD\Vendor\Stripe\Util\RequestOptions $opts
     *
     * @throws \EDD\Vendor\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \EDD\Vendor\Stripe\BillingPortal\Configuration
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/billing_portal/configurations/%s', $id), $params, $opts);
    }
}
