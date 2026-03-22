<?php

namespace App\Form;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isLoggedIn = $options['is_logged_in'];
        $inputClass = 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange';
        if ($isLoggedIn) {
            $inputClass .= ' opacity-60 cursor-not-allowed';
        }

        $builder
            ->add('participantName', TextType::class, [
                'label' => 'Votre nom',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'attr' => ['placeholder' => 'Jean Dupont', 'class' => $inputClass, 'readonly' => $isLoggedIn],
                'constraints' => $isLoggedIn ? [] : [new NotBlank(message: 'Merci de renseigner votre nom.')],
            ])
            ->add('participantEmail', EmailType::class, [
                'label' => 'Votre email',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'attr' => ['placeholder' => 'jean@exemple.fr', 'class' => $inputClass, 'readonly' => $isLoggedIn],
                'constraints' => $isLoggedIn ? [] : [
                    new NotBlank(message: 'Merci de renseigner votre email.'),
                    new Email(message: 'Merci de saisir une adresse email valide.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
            'is_logged_in' => false,
        ]);
    }
}
