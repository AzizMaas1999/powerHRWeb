<?php

namespace App\Form;

use App\Entity\Candidat;
use App\Entity\Entreprise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                
                'label' => ' Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prenom', TextType::class, [
            
                'label' => 'prenom',
                'attr' => ['class' => 'form-control']
                ])
            ->add('email', EmailType::class, [
                
                'label' => 'Email Address',
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', NumberType::class, [
                'label' => 'Numéro Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control',]
            ])
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class, // The entity to use
                'choice_label' => 'nom', // The field to display in the dropdown
                'placeholder' => 'Sélectionnez une entreprise', // Default option
                'label' => 'Entreprise',
                'attr' => ['class' => 'form-select']
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Submit Application',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidat::class,
        ]);
    }
}
