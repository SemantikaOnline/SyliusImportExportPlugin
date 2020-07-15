<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductInterface;

class ShipmentResourcePlugin extends ResourcePlugin
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

        /** @var ShipmentInterface $resource */
        foreach ($this->resources as $resource) {
            $this->addGeneralData($resource);
            $this->addShippingAdressData($resource);
            $this->addCustomerData($resource);
            $items = $this->getItemsAndCount($resource);
            $this->addOrderItemData($items, $resource);

        }
    }

    private function addGeneralData(ShipmentInterface $resource): void
    {
        $this->addDataForResource($resource, 'Shipping_country', 'PL');
        $this->addDataForResource($resource, 'Shipping_packages', '1');
        $this->addDataForResource($resource, 'Shipping_cash', '0');
        $this->addDataForResource($resource, 'Shipping_insurance', '0');
        $this->addDataForResource($resource, 'Shipping_reference', '');
    }

    private function addCustomerData(ShipmentInterface $resource): void
    {
        $customer = $resource->getOrder()->getCustomer();
        if (null === $customer) {
            return;
        }
        $this->addDataForResource($resource, 'Email', $customer->getEmail());
    }

    private function addShippingAdressData(ShipmentInterface $resource): void
    {
        $shippingAddress = $resource->getOrder()->getShippingAddress();

        if (null === $shippingAddress) {
            return;
        }

        $this->addDataForResource($resource, 'Shipping_full_name', $shippingAddress->getFirstName().' '.$shippingAddress->getLastName());
        $company = $shippingAddress->getCompany();
        if(!$company){
            $company = $shippingAddress->getFirstName().' '.$shippingAddress->getLastName();
        }
        $this->addDataForResource($resource, 'Shipping_company', $company);
        $this->addDataForResource($resource, 'Shipping_telephone', $shippingAddress->getPhoneNumber());
        $this->addDataForResource($resource, 'Shipping_street', $shippingAddress->getStreet());
        $this->addDataForResource($resource, 'Shipping_postcode', $shippingAddress->getPostcode());
        $this->addDataForResource($resource, 'Shipping_city', $shippingAddress->getCity());
    }

    private function addOrderItemData(array $items, ShipmentInterface $resource): void
    {
        $str = '';
        $total_weight = 0;

        unset($items['total']);

        foreach ($items as $itemId => $item) {
            if (!empty($str)) {
                $str .= ' | ';
            }
            $str .= sprintf('%dx %s', $item['count'], $item['name']);
            $total_weight += $item['weight'];
        }

        $this->addDataForResource($resource, 'Product_list', $str);
        $this->addDataForResource($resource, 'Weight', $total_weight);
    }

    private function getItemsAndCount(ShipmentInterface $resource): array
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
                    'count' => 0,
                    'weight' => 0,
                ];
            }
            $items[$product->getId()]['count'] += $orderItem->getQuantity();
            $items[$product->getId()]['weight'] += $variant->getWeight() * $orderItem->getQuantity();
        }
        return $items;
    }

}
