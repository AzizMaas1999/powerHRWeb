<?php

namespace App\Form;

use App\Entity\Departement;
use App\Entity\Employe;
use App\Entity\FicheEmploye;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Enum\Poste;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('password')
            ->add('poste', ChoiceType::class, [
                'choices' => Poste::cases(),
                'choice_label' => fn(Poste $poste) => $poste->value,
                'choice_value' => fn (?Poste $poste) => $poste?->value,
                'label' => 'Poste'
            ])            
            ->add('salaire')
            ->add('rib')
            ->add('codeSociale')
            ->add('departement', EntityType::class, [
                'class' => Departement::class,
                'choice_label' => 'nom', // 👈 show the name instead
                'label' => 'Département',
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
