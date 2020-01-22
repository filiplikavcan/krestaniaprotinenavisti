<?php

namespace App\Command;

use App\Entity\Signature as SignatureEntity;
use App\Model\Signature;
use DateTimeImmutable;
use Swift_Mailer;
use Swift_Plugins_AntiFloodPlugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendNotificationsCommand extends Command
{
    protected static $defaultName = 'app:send-notifications';

    private $signatureModel;
    private $template;
    private $router;
    private $mailer;

    public function __construct(Signature $signatureModel, \Twig\Environment $template, RouterInterface $router, Swift_Mailer $mailer)
    {
        parent::__construct();

        $this->signatureModel = $signatureModel;
        $this->template = $template;
        $this->router = $router;
        $this->mailer = $mailer;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Sending notification emails.");

        $this->mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(50, 2));

        /** @var SignatureEntity $signature */
        foreach ($this->signatureModel->notifiableSignatures(100) as $signature)
        {
            if ($this->canSendNotification($signature))
            {
                if ($this->signatureModel->markAsNotified($signature))
                {
                    $output->write("Sending email to {$signature->getFirstName()} {$signature->getLastName()} (id={$signature->getId()})... ");

                    $message = (new \Swift_Message('[Kresťania proti nenávisti] Nezabudnite potvrdiť svoj podpis!'))
                        ->setFrom('dakujeme@krestaniaprotinenavisti.sk', 'Kresťania proti nenávisti')
                        ->setTo($signature->getEmail())
                        ->setBody(
                            $this->template->render('emails/notification.html.twig', [
                                'verification_link' => $this->router->generate('verify', [
                                    'hash' => $signature->getHash()
                                ], UrlGeneratorInterface::ABSOLUTE_URL),
                                'signature' => $signature
                            ]),
                            'text/html'
                        );

                    $this->mailer->send($message);

                    $output->writeln("Sent.");
                }
            }
        }

        $output->writeln("DONE");

        return 0;
    }

    private function canSendNotification(SignatureEntity $signature)
    {
        if (null === $signature->getVerifiedAt())
        {
            $lastNotifiedAt = $signature->getLastNotifiedAt() ?? $signature->getCreatedAt();
            $waitPeriodInDays = $signature->getNotificationCount() * 3 + 1;
            $nDaysAgo = new DateTimeImmutable("$waitPeriodInDays days ago");

            if ($signature->getNotificationCount() < 2 && $lastNotifiedAt < $nDaysAgo)
            {
                return true;
            }
        }

        return false;
    }
}