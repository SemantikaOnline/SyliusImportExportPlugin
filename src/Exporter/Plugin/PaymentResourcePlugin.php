<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductInterface;



class PaymentResourcePlugin extends ResourcePlugin
{
    public function __construct(
        RepositoryInterface $repository,
        PropertyAccessorInterface $propertyAccessor,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($repository, $propertyAccessor, $entityManager);
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $idsToExport): void
    {
        parent::init($idsToExport);

        /** @var PaymentInterface $resource */
        foreach ($this->resources as $resource) {
            $this->addGeneralData($resource);
            $this->addOrderData($resource);
            $items = $this->getItemsAndCount($resource);
            $this->addOrderItemData($items, $resource);
        }
    }

    private function addGeneralData(PaymentInterface $resource): void
    {
        $this->addDataForResource($resource, 'Payment_date', $resource->getPaidAt());
        $this->addDataForResource($resource, 'Ext_transaction_number', $resource->getExtTransactionNumber());
        $this->addDataForResource($resource, 'Ext_invoice_number', $resource->getExtInvoiceNumber());
    }

    private function addOrderData(PaymentInterface $resource): void
    {
        $order = $resource->getOrder();
        $this->addDataForResource($resource, 'Order_number', $order->getNumber());
        $this->addDataForResource($resource, 'Order_email', $order->getCustomer()->getEmail());
        $this->addDataForResource($resource, 'Items_total', $order->getItemsTotal() / 100);
        $this->addDataForResource($resource, 'Adjustments_total', $order->getAdjustmentsTotal() / 100);
        $this->addDataForResource($resource, 'Order_total', $order->getTotal() / 100);
        $this->addDataForResource($resource, 'Vat_number', $order->getBillingAddress()->getVatNumber());


    }

    private function addOrderItemData(array $items, PaymentInterface $resource): void
    {
        $str = '';

        unset($items['total']);

        foreach ($items as $itemId => $item) {
            if (!empty($str)) {
                $str .= ' | ';
            }
            $str .= sprintf('%dx %s', $item['count'], $item['name']);
        }

        $this->addDataForResource($resource, 'Product_list', $str);
    }


    private function getItemsAndCount(PaymentInterface $resource): array
    {
        $items = [];

        /** @var OrderItemInterface $orderItem */
        foreach ($resource->getOrder()->getItems() as $orderItem) {
            /** @var ProductVariantInterface $variant */
            $variant = $orderItem->getVariant();
            /** @var ProductInterface $product */
            $product = $variant->getProduct();

            if (!isset($items[$product->getId()])) {
                $items[$product->getId()] = [
                    'name' => $product->getCode(),
                    'count' => 0
                ];
            }
            $items[$product->getId()]['count'] += $orderItem->getQuantity();
        }
        return $items;
    }
}
