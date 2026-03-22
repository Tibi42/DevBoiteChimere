<?php

namespace App\Form;

use App\Entity\Activity;
use App\Enum\ActivityKind;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = ['-- Choisir --' => ''];
        foreach (ActivityKind::cases() as $kind) {
            if ($kind === ActivityKind::AG && !$options['is_admin']) {
                continue;
            }
            $choices[$kind->label()] = $kind->value;
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'attr' => ['placeholder' => 'Ex : Soirée Jeux de société', 'class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange'],
                'constraints' => [new NotBlank(message: 'Le titre est obligatoire.')],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'activité',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'choices' => $choices,
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'required' => false,
                'attr' => ['placeholder' => 'Description de l\'événement...', 'rows' => 4, 'class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange'],
            ])
            ->add('startAt', DateType::class, [
                'label' => 'Date de début',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange activity-date-picker',
                    'autocomplete' => 'off',
                ],
                'constraints' => [new NotBlank(message: 'La date de début est obligatoire.')],
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Lieu',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'choices' => [
                    '-- Choisir --' => '',
                    'L\'auberge de jeunesse Yves Robert' => 'L\'auberge de jeunesse Yves Robert',
                    'Le Natema' => 'Le Natema',
                ],
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange'],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'label_attr' => ['class' => 'block text-xs font-bold uppercase tracking-wider text-text-primary mb-1'],
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 8', 'min' => 1, 'class' => 'w-full rounded-lg border border-custom bg-custom-secondary px-4 py-3 text-text-primary focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'is_admin' => false,
        ]);
    }
}
