<?php

namespace App\Form;

use App\Entity\Clfr;
use App\Entity\Feedback;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Positif' => 'positif',
                    'Négatif' => 'negatif',
                    'Neutre' => 'neutre',
                ],
                'placeholder' => 'Sélectionnez un type',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le type est obligatoire.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Saisissez votre commentaire ici...',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description est obligatoire.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 1000,
                        'minMessage' => 'La description doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas excéder {{ limit }} caractères.'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}
