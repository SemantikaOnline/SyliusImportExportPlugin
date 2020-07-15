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
        $this->addDataForResource($resource, 'Order_number', $resource->getOrder()->getNumber());
        $this->addDataForResource($resource, 'Order_email', $resource->getOrder()->getCustomer()->getEmail());
    }
}
