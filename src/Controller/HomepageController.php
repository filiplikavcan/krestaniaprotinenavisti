<?php

namespace App\Controller;

use App\Entity\Signature as SignatureEntity;
use App\Form\Type\SignatureType;
use App\Model\Signature;
use Doctrine\DBAL\DBALException;
use org\nameapi\client\services\ServiceFactory;
use org\nameapi\ontology\input\context\Context;
use org\nameapi\ontology\input\context\Priority;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomepageController extends AbstractController
{
    public function index(Request $request, Signature $signatureModel, Swift_Mailer $mailer, $version = 1)
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
            'fb_share_version' => $version,
            'form' => $form->createView(),
            'last_signatures' => $signatureModel->lastVisibleSignatures(),
            'signatures_count' => $signatureModel->signaturesCount(),
        ]);
    }

    public function list(Signature $signatureModel)
    {
        return $this->render('Homepage/list.html.twig', [
            'visible_signatures_count' => $signatureModel->visibleSignaturesCount(),
            'signatures' => $signatureModel->allVisibleSignatures(),
            'signatures_count' => $signatureModel->signaturesCount(),
        ]);
    }

    public function verify(string $hash, Signature $signatureModel)
    {
        $signature = $signatureModel->verify($hash);

//        $context = Context::builder()
//            ->priority(Priority::REALTIME())
//            ->build();
//
//        $serviceFactory = new ServiceFactory('api-key', $context);
//        $deaDetector = $serviceFactory->emailServices()->disposableEmailAddressDetector();
//        $result = $deaDetector->isDisposable("abcdefgh@10minutemail.com");

        $wasVerified5MinutesAgo = null === $signature ? false : (300 >= time() - $signature->getVerifiedAt()->getTimestamp());

        return $this->render('Homepage/verify.html.twig', [
            'signature' => $signature,
            'was_verified_5_minutes_ago' => $wasVerified5MinutesAgo,
        ]);
    }

    private function sendVerificationEmail(SignatureEntity $signature, Swift_Mailer $mailer)
    {
        $message = (new Swift_Message('Dôležité: Potvrďte svoj podpis pod vyhlásením!'))
            ->setFrom('dakujeme@krestaniaprotinenavisti.sk', 'Overenie podpisu (Kresťania proti nenávisti)')
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

    /**
     * @param string $hash
     * @param int $newsletterId
     * @param Signature $signatureModel
     * @return Response
     * @throws DBALException
     */
    public function unsubscribe(string $hash, int $newsletterId, Signature $signatureModel)
    {
        $signature = $signatureModel->findOneByHash($hash);

        if ($signature instanceof SignatureEntity)
        {
            $signatureModel->unsubscribe($signature->getId(), $newsletterId);
        }

        return $this->render('Homepage/unsubscribe.html.twig', [
            'signature' => $signature,
        ]);
    }
}