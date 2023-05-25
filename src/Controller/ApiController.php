<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
{
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
}
