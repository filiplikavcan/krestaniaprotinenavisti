<?php

namespace App\Form\Type;

use App\Entity\Signature;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
            ->add('first_name', TextType::class, [
                'label' => 'Krstné meno',
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Priezvisko',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
            ])
            ->add('city', TextType::class, [
                'label' => 'Mesto/obec',
            ])
            ->add('occupation', TextType::class, [
                'label' => 'Povolanie',
                'required' => false,
            ])
            ->add('allow_display', CheckboxType::class, [
                'label' => 'Súhlasím so zverejnením môjho mena a povolaniapri texte vyhlásenia na webstránke.',
                'required' => false,
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