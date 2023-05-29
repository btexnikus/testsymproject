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

    /**
     * @param Product $product
     * @param Coupon|null $coupon
     * @param Tax $tax
     * @return int
     */
    public function getProductPrice(Product $product, Coupon $coupon = null, Tax $tax) {

        $productPrice = $product->getPrice();
        $taxPercent = $tax->getValue();

        if ($coupon) {
            if ($coupon->getType() == Coupon::TYPE_DISCOUNT)
                $productPrice = $productPrice - $coupon->getValue();
            elseif ($coupon->getType() == Coupon::TYPE_PERCENTAGE)
                $productPrice = $productPrice - ($productPrice * ($coupon->getValue() / 100));
        }

        return $productPrice + ($productPrice * ($taxPercent / 100));

    }

}