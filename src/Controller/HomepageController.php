<?php

namespace App\Controller;

use App\Entity\Signature as SignatureEntity;
use App\Form\Type\SignatureType;
use App\Model\Signature;
use App\Service\ListService;
use Doctrine\DBAL\DBALException;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomepageController extends AbstractController
{
    public function __construct(private ListService $listService)
    {
    }

    public function index(Request $request, Signature $signatureModel, string $slug = '')
    {
        $currentList = $this->getList('');
        $list = $this->getList($slug);

        if ($list['enabled']) {
            $signature = $signatureModel->create();

            $form = $this->createForm(SignatureType::class, $signature);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $formData->setPetition($list['slug']);
//                $signature = $signatureModel->insert($formData);
//                $this->sendVerificationEmail($signature, $mailer);

                return $this->redirect($this->generateUrl('thank_you'));
            }
        }

        return $this->render('Homepage/index.html.twig', $list + [
                'fb_share_version' => 2023,
                'current_list'     => $currentList,
            ] + ($list['enabled'] ? [
                'form'             => $form->createView(),
                'last_signatures'  => $signatureModel->lastVisibleSignatures($list['limit']),
                'signatures_count' => $signatureModel->signaturesCount(),
            ] : []));
    }

    private function getList(string $slug): ?array
    {
        if (empty($slug)) {
            return $this->listService->default();
        }

        $list = $this->listService->find($slug);

        if (null === $list) {
            throw $this->createNotFoundException();
        }

        return $list;
    }

    public function list(Signature $signatureModel, string $slug = '')
    {
        return $this->render('Homepage/list.html.twig', [
            'slug'                     => $slug,
            'visible_signatures_count' => $signatureModel->visibleSignaturesCount(),
            'signatures'               => $signatureModel->allVisibleSignatures(),
            'signatures_count'         => $signatureModel->signaturesCount(),
        ]);
    }

    public function verify(string $hash, Signature $signatureModel)
    {
        $signature = $signatureModel->verify($hash);

        $wasVerified5MinutesAgo = null === $signature ? false : (300 >= time() - $signature->getVerifiedAt()->getTimestamp());

        return $this->render('Homepage/verify.html.twig', [
            'signature'                  => $signature,
            'was_verified_5_minutes_ago' => $wasVerified5MinutesAgo,
        ]);
    }

    private function sendVerificationEmail(SignatureEntity $signature, Swift_Mailer $mailer)
    {
        $message = (new Swift_Message('Dôležité: Potvrďte svoj podpis pod vyhlásením'))
            ->setFrom('dakujeme@krestaniaprotinenavisti.sk', 'Overenie podpisu (Kresťania proti nenávisti)')
            ->setTo($signature->getEmail())
            ->setBody(
                $this->renderView(
                    'emails/verification.html.twig',
                    [
                        'verification_link' => $this->generateUrl('verify', [
                            'hash' => $signature->getHash()
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'signature'         => $signature
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

        if ($signature instanceof SignatureEntity) {
            $signatureModel->unsubscribe($signature->getId(), $newsletterId);
        }

        return $this->render('Homepage/unsubscribe.html.twig', [
            'signature' => $signature,
        ]);
    }
}
