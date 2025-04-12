<?php

namespace App\Form;

use App\Entity\Clfr;
use App\Entity\Employe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ClfrType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('matricule_fiscale', null, [
                'label' => 'Matricule Fiscale',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('adresse', null, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('numTel', null, [
                'label' => 'Numéro de téléphone',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Client' => 'client',
                    'Fournisseur' => 'fournisseur',
                ],
                'placeholder' => 'Choisir un type',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('photoPath', FileType::class, [
                'label' => 'Photo (JPG ou PNG)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG ou PNG)',
                    ])
                ],
            ])
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => 'username',
                'label' => 'Employé associé',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Clfr::class,
        ]);
    }
}
