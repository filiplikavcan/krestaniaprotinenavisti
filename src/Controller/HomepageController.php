<?php

namespace App\Controller;

use App\Entity\Signature;
use App\Form\Type\SignatureType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class HomepageController extends AbstractController
{
    public function index(Request $request)
    {
        $signature = new Signature();

        $form = $this->createForm(SignatureType::class, $signature);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO: save
        }

        return $this->render('Homepage/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}