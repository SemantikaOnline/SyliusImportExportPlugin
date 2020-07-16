<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

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
        }
    }

    private function addGeneralData(PaymentInterface $resource): void
    {
        $this->addDataForResource($resource, 'Payment_date', $resource->getUpdatedAt());

    }

    private function addOrderData(PaymentInterface $resource): void
    {
        $order = $resource->getOrder();
        $this->addDataForResource($resource, 'Order_number', $order->getNumber());
        $this->addDataForResource($resource, 'Order_email', $order->getCustomer()->getEmail());
        $this->addDataForResource($resource, 'Items_total', $order->getItemsTotal() / 100);
        $this->addDataForResource($resource, 'Adjustments_total', $order->getAdjustmentsTotal() / 100);
    }
}
