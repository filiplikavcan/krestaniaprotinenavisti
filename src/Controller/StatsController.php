<?php

namespace App\Controller;

use App\Entity\Signature as SignatureEntity;
use App\Form\Type\SignatureType;
use App\Model\Signature;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StatsController extends AbstractController
{
    public function index(Signature $signatureModel)
    {
        return $this->render('Stats/index.html.twig', [
            'hours' => $signatureModel->hourlyStats(),
        ]);
    }

//    public function cloud(Signature $signatureModel)
//    {
//        $top100Words = $signatureModel->top100OccupationWords();
//
//        foreach ($top100Words as $word => $count)
//        {
//            echo "1\t$word\n";
//        }
//        die;
//    }
}