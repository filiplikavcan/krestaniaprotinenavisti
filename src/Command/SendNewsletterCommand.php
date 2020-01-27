<?php

namespace App\Command;

use App\Entity\Signature as SignatureEntity;
use App\Model\Newsletter;
use App\Model\Signature;
use Doctrine\DBAL\DBALException;
use Swift_Image;
use Swift_Mailer;
use Swift_Plugins_AntiFloodPlugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendNewsletterCommand extends Command
{
    protected static $defaultName = 'app:send-newsletters';

    private $signatureModel;
    private $template;
    private $mailer;
    private $newsletterModel;
    private $rootDir;
    private $router;

    public function __construct(string $rootDir, Signature $signatureModel, Newsletter $newsletterModel, Environment $template, RouterInterface $router, Swift_Mailer $mailer)
    {
        parent::__construct();

        $this->signatureModel = $signatureModel;
        $this->template = $template;
        $this->mailer = $mailer;
        $this->newsletterModel = $newsletterModel;
        $this->rootDir = $rootDir;
        $this->router = $router;
    }

    protected function configure()
    {
        parent::configure();

        $this->addArgument('newsletter_id', InputArgument::REQUIRED, 'Newsletter id');
        $this->addArgument('limit', InputArgument::REQUIRED, 'Maximum nr of email to send');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = (int) $input->getArgument('limit');

        $newsletterIdRaw = $input->getArgument('newsletter_id');
        $newsletter = $this->newsletterModel->findOneById((int)$newsletterIdRaw);

        if (null === $newsletter)
        {
            $output->writeln("Newsletter with id $newsletterIdRaw not found.");

            return 0;
        }

        $output->writeln("Sending newsletter: {$newsletter->getName()}.");

        $output->writeln("Sending newsletter emails.");

        $this->mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(50, 2));

        /** @var SignatureEntity $signature */
        foreach ($this->signatureModel->newsletterableSignatures($newsletter->getId(), $limit) as $signature)
        {
            if ($this->signatureModel->markNewsletterSent($newsletter->getId(), $signature->getId()))
            {
                $output->write("Sending email to {$signature->getEmail()} (id={$signature->getId()})... ");

                $message = (new \Swift_Message('Potrebujeme Vašu pomoc. Hľadajú sa dobrovoľníci.'))
                    ->setFrom('dakujeme@krestaniaprotinenavisti.sk', 'Kresťania proti nenávisti')
                    ->setTo($signature->getEmail());

                $message->setBody(
                        $this->template->render('emails/newsletter-volunteers.html.twig', [
                            'logo_image' =>  $message->embed(Swift_Image::fromPath($this->rootDir . '/templates/emails/attachments/email-dobrovolnici-v1.png')),
                            'fb_icon' =>  $message->embed(Swift_Image::fromPath($this->rootDir . '/templates/emails/attachments/icon-facebook.png')),
                            'unsubscribe_link' => $this->router->generate('unsubscribe', [
                                'hash' => $signature->getHash(),
                                'newsletterId' => $newsletter->getId(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL),
                            'signature' => $signature
                        ]),
                        'text/html'
                    );

                $this->mailer->send($message);

                $output->writeln("Sent.");
            }
        }

        $output->writeln("DONE");

        return 0;
    }
}