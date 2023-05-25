<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Coupon;
use App\Entity\Tax;

class ApiService
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getProductPrice(Product $product, Coupon $coupon = null, Tax $tax) {

        return true;
    }

}