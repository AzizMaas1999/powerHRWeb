<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Paie;
use App\Entity\Pointage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PointageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'widget' => 'single_text',
            ])
            ->add('heureEntree', null, [
                'widget' => 'single_text',
            ])
            ->add('heureSortie', null, [
                'widget' => 'single_text',
            ])
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => 'id',
            ])
            ->add('paie', EntityType::class, [
                'class' => Paie::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pointage::class,
        ]);
    }
}
