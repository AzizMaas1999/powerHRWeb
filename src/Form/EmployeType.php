<?php

namespace App\Form;

use App\Entity\Departement;
use App\Entity\Employe;
use App\Entity\FicheEmploye;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('password')
            ->add('poste')
            ->add('salaire')
            ->add('rib')
            ->add('codeSociale')
            ->add('departement', EntityType::class, [
                'class' => Departement::class,
                'choice_label' => 'id',
            ])
            ->add('ficheEmploye', EntityType::class, [
                'class' => FicheEmploye::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}
