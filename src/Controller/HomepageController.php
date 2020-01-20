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

class HomepageController extends AbstractController
{
    public function index(Request $request, Signature $signatureModel, Swift_Mailer $mailer)
    {
        $signature = $signatureModel->create();

        $form = $this->createForm(SignatureType::class, $signature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $signature = $signatureModel->insert($formData);
            $this->sendVerificationEmail($signature, $mailer);

            return $this->redirect($this->generateUrl('thank_you'));
        }

        return $this->render('Homepage/index.html.twig', [
            'form' => $form->createView(),
            'last_signatures' => $signatureModel->lastVisibleSignatures(),
            'signatures_count' => $signatureModel->signaturesCount(),
        ]);
    }

    public function list(Signature $signatureModel)
    {
        return $this->render('Homepage/list.html.twig', [
            'signatures' => $signatureModel->allVisibleSignatures(),
            'signatures_count' => $signatureModel->signaturesCount(),
        ]);
    }

    public function verify(string $hash, Signature $signatureModel)
    {
        $signature = $signatureModel->verify($hash);
        $wasVerified5MinutesAgo = null === $signature ? false : (300 >= time() - $signature->getVerifiedAt()->getTimestamp());

        return $this->render('Homepage/verify.html.twig', [
            'signature' => $signature,
            'was_verified_5_minutes_ago' => $wasVerified5MinutesAgo,
        ]);
    }

    private function sendVerificationEmail(SignatureEntity $signature, Swift_Mailer $mailer)
    {
        $message = (new Swift_Message('Overenie podpisu pod výzvou Kresťania proti nenávisti'))
            ->setFrom('dakujeme@krestaniaprotinenavisti.sk')
            ->setTo($signature->getEmail())
            ->setBody(
                $this->renderView(
                    'emails/verification.html.twig',
                    [
                        'verification_link' => $this->generateUrl('verify', [
                            'hash' => $signature->getHash()
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'signature' => $signature
                    ]
                ),
                'text/html'
            );

        $mailer->send($message);
    }
}