<?php

namespace App\Form\Type;

use App\Entity\Signature;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignatureType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'novalidate' => 'novalidate',
            ],
            'data_class' => Signature::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'homepage',
//                'script_nonce_csp' => $nonceCSP,
                'locale' => 'sk',
            ])
            ->add('first_name', TextType::class, [
                'label' => 'Krstné meno',
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Priezvisko',
            ])
            ->add('occupation', TextType::class, [
                'label' => 'Povolanie',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail (nebude zverejnený)',
            ])
            ->add('city', TextType::class, [
                'label' => 'Mesto/obec (nebude zverejnené)',
            ])
            ->add('display', ChoiceType::class, [
                'label' => 'Svoj podpis chcem zverejniť vo forme',
                'expanded' => true,
                'choices' => [
                    'Celé meno a povolanie <span>ukážka: <strong id="signature-example-full">Jozef Mrkvička, tesár</strong></span>' => 'full',
                    'Krstné meno a povolanie <span>ukážka: <strong id="signature-example-first-name">Mária, matka v domácnosti</strong></span>' => 'first_name_and_occupation',
                    'Anonymne <span>ukážka: <strong>skrytý podpis</strong></span>' => 'anonymous',
                ]
            ])
            ->add('agree_with_support_statement', CheckboxType::class, [
                'label' => 'Súhlasím so spracúvaním mojich osobných údajov na účely podpory Iniciatívy v zmysle <a href="/pravidla-ochrany-osobnych-udajov" target="_blank">Pravidiel ochrany osobných údajov</a>.',
            ])
            ->add('agree_with_contact_later', CheckboxType::class, [
                'label' => 'Súhlasím so spracúvaním mojich osobných údajov na účely budúceho informovania o aktivitách Iniciatívy v zmysle <a href="/pravidla-ochrany-osobnych-udajov" target="_blank">Pravidiel ochrany osobných údajov</a>.',
                'required' => false,
            ])
            ->add('sign', SubmitType::class, [
                'label' => 'Podpísať',
            ])
        ;
    }
}
