<?php

declare(strict_types=1);

namespace Hotchand\FreeShippingWeightLimit\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflineShipping\Model\Carrier\Freeshipping;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Restricts Magento's native Free Shipping carrier so it is only offered
 * when the cart's total weight is at or below an admin-configured limit.
 *
 * Wraps Freeshipping::collectRates(): if the restriction is enabled and the
 * request's total weight exceeds the configured max, the original method is
 * never called and `false` is returned instead — which is exactly how
 * Magento's shipping carriers signal "no rate available" (see
 * Magento\Shipping\Model\Carrier\AbstractCarrier / CarrierInterface).
 */
class RestrictByWeightPlugin
{
    private const XML_PATH_ENABLED    = 'carriers/freeshipping/weight_restriction_enabled';
    private const XML_PATH_MAX_WEIGHT = 'carriers/freeshipping/max_weight';

    /** Default fallback if admin field is empty/invalid, per the request. */
    private const DEFAULT_MAX_WEIGHT = 8.0;

    private ScopeConfigInterface $scopeConfig;
    private ?LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ?LoggerInterface $logger = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger      = $logger;
    }

    /**
     * @param Freeshipping $subject
     * @param \Closure $proceed
     * @param RateRequest $request
     * @return Result|bool
     */
    public function aroundCollectRates(
        Freeshipping $subject,
        \Closure $proceed,
        RateRequest $request
    ) {
        $storeId = $request->getStoreId();

        if (!$this->isRestrictionEnabled($storeId)) {
            return $proceed($request);
        }

        $limit = $this->getMaxWeight($storeId);
        $totalWeight = $this->resolveTotalWeight($request);

        if ($totalWeight === null) {
            // Could not determine weight reliably — fail open and let the
            // carrier's own logic decide, rather than silently blocking
            // free shipping due to a data issue.
            $this->log(sprintf(
                'Hotchand_FreeShippingWeightLimit: could not determine cart weight '
                . 'for store %s; skipping weight restriction for this request.',
                (string) $storeId
            ));
            return $proceed($request);
        }

        if ($totalWeight > $limit) {
            // Over the limit — Free Shipping is not offered at all.
            return false;
        }

        return $proceed($request);
    }

    /**
     * Determine total cart weight. Prefers the pre-computed package weight
     * that Magento's shipping module sets on the request (the same value
     * carriers like UPS/FedEx rely on); falls back to manually summing line
     * items if that isn't populated for some reason (e.g. certain custom
     * checkout/estimation flows that build a RateRequest more minimally).
     */
    private function resolveTotalWeight(RateRequest $request): ?float
    {
        $packageWeight = $request->getPackageWeight();
        if ($packageWeight !== null && $packageWeight !== '' && is_numeric($packageWeight)) {
            return (float) $packageWeight;
        }

        $items = $request->getAllItems();
        if (empty($items)) {
            return null;
        }

        $total = 0.0;
        foreach ($items as $item) {
            // Skip child items of a configurable/bundle parent to avoid
            // double-counting weight already represented by the parent line.
            if ($item->getParentItem()) {
                continue;
            }

            $weight = $item->getWeight();
            $qty    = $item->getQty();

            if (!is_numeric($weight) || !is_numeric($qty)) {
                continue;
            }

            $total += (float) $weight * (float) $qty;
        }

        return $total;
    }

    private function isRestrictionEnabled($storeId): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getMaxWeight($storeId): float
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_MAX_WEIGHT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === null || $value === '' || !is_numeric($value)) {
            return self::DEFAULT_MAX_WEIGHT;
        }

        return (float) $value;
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->info($message);
        }
    }
}
