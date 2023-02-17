<?php

namespace App\Controller;

use App\Entity\Signature;
use App\Form\Type\SignatureType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ArticleController extends AbstractController
{
    public function tos()
    {
        return $this->render('Article/tos.html.twig');
    }

    public function thankYou()
    {
        return $this->render('Article/thankYou.html.twig');
    }

    public function cookies()
    {
        return $this->render('Article/cookies.html.twig');
    }
}
