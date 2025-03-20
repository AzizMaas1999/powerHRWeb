<?php

namespace App\Form;

use App\Entity\Clfr;
use App\Entity\Employe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClfrType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('matricule_fiscale')
            ->add('adresse')
            ->add('numTel')
            ->add('type')
            ->add('photoPath')
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Clfr::class,
        ]);
    }
}
