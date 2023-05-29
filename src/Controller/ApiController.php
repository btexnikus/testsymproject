<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiService;
use App\Entity\Product;
use App\Entity\Coupon;
use App\Entity\Tax;
use App\Processor\PaypalPaymentProcessor;
use App\Processor\StripePaymentProcessor;
/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
{

    private EntityManagerInterface $em;
    private ApiService $apiSer;
    private PaypalPaymentProcessor $paypalPr;

    public function __construct(EntityManagerInterface $entityManager, ApiService $apiService, PaypalPaymentProcessor $paypalPaymentProcessor)
    {
        $this->em = $entityManager;
        $this->apiSer = $apiService;
        $this->paypalPr = $paypalPaymentProcessor;
    }

    /**
     * @Route("/test", name="api_test", methods={"GET"})
     */
    public function test(): Response
    {
        $data = [
            'result' => 'ok'
        ];

        return $this->json($data);
    }

    /**
     * @Route("/getprice", name="api_getprice", methods={"GET"})
     */
    public function getprice(Request $request): Response
    {
        $productRep = $this->em->getRepository(Product::class);
        $couponRep = $this->em->getRepository(Coupon::class);
        $taxRep = $this->em->getRepository(Tax::class);

        $data = [];
        $errors = [];

        $productId = (int)$request->get('product');
        $taxNumber = (string)$request->get('taxNumber');
        $couponCode = (string)$request->get('couponCode');

        $productItem = $productRep->findActiveProductById($productId);
        if (!$productItem instanceof Product)
            $errors['product'] = 'Product not found!';

        $coupon = null;
        if($couponCode) {
            $coupon = $couponRep->findOneBy([
                'code' => $couponCode
            ]);
            if (!$coupon instanceof Coupon)
                $errors['product'] = 'Invalid coupone code!';
        }

        $tax = null;
        if ($taxNumber && preg_match("/^[A-Z]{2}\d+/",$taxNumber)) {
            preg_match('/^[A-Z]{2}/', $taxNumber, $matches);
            if( isset($matches[0])) {
                $tax = $taxRep->findOneBy([
                    'code' => $matches[0]
                ]);
                if (!$tax instanceof Tax)
                    $errors['taxNumber'] = 'Tax code not value';
            }
        } else {
            $errors['taxNumber'] = 'Invalid tax number format!';
        }

        if (count($errors)){
            $data['code'] = 400;
            $data = array_merge($data, $errors);
        } else {
            $price = $this->apiSer->getProductPrice($productItem, $coupon, $tax);

            $data['code'] = 200;
            $data['product'] = $productItem->getTitle();
            $data['price'] = $price;
        }

        return $this->json($data);
    }

    /**
     * @Route("/buy", name="api_buy", methods={"POST"})
     */
    public function buy(Request $request): Response
    {
        $productRep = $this->em->getRepository(Product::class);
        $couponRep = $this->em->getRepository(Coupon::class);
        $taxRep = $this->em->getRepository(Tax::class);

        $paymentParameters = $this->getParameter('payment_config');

        $data = [];
        $errors = [];

        $productId = (int)$request->get('product');
        $taxNumber = (string)$request->get('taxNumber');
        $couponCode = (string)$request->get('couponCode');
        $paymentProcessor = (string)$request->get('paymentProcessor');

        $productItem = $productRep->findActiveProductById($productId);
        if (!$productItem instanceof Product)
            $errors['product'] = 'Product not found!';

        $coupon = null;
        if($couponCode) {
            $coupon = $couponRep->findOneBy([
                'code' => $couponCode
            ]);
            if (!$coupon instanceof Coupon)
                $errors['product'] = 'Invalid coupone code!';
        }

        $tax = null;
        if ($taxNumber && preg_match("/^[A-Z]{2}\d+/",$taxNumber)) {
            preg_match('/^[A-Z]{2}/', $taxNumber, $matches);
            if( isset($matches[0])) {
                $tax = $taxRep->findOneBy([
                    'code' => $matches[0]
                ]);
                if (!$tax instanceof Tax)
                    $errors['taxNumber'] = 'Tax code not value';
            }
        } else {
            $errors['taxNumber'] = 'Invalid tax number format!';
        }

        if (isset($paymentParameters[$paymentProcessor])) {
            $paymentProcessorClass = (string)$paymentParameters[$paymentProcessor]['handler'];
            $paymentProcessorMethod = (string)$paymentParameters[$paymentProcessor]['method'];
        } else {
            $errors['paymentProcessor'] = 'Payment method not found!';
        }

        if (count($errors)){
            $data['code'] = 400;
            $data = array_merge($data, $errors);
        } else {
            $price = $this->apiSer->getProductPrice($productItem, $coupon, $tax);

            $paymentProcessor = new $paymentProcessorClass();
            $res = $paymentProcessor->$paymentProcessorMethod($price);

            $data['code'] = 200;
            $data['result'] = 'ok';
        }

        return $this->json($data);
    }
}
