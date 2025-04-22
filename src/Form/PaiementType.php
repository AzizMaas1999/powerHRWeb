<?php

namespace App\Form;

use App\Entity\Paiement;
use App\Entity\Facture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\FactureRepository;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de paiement',
                'required' => true,
                'input' => 'datetime',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'Virement' => 'Virement',
                    'Chèque' => 'Chèque',
                    'Espèce' => 'espece',
                    'Traite' => 'traite',
                ],
                'label' => 'Mode de paiement',
                'attr' => ['class' => 'form-control']
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'attr' => ['class' => 'form-control']
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant',
                'attr' => ['class' => 'form-control', 'min' => 0, 'step' => '0.01']
            ])

            ->add('factures', EntityType::class, [
                'class' => Facture::class,
                'multiple' => true,
                'expanded' => false,
                'choice_label' => function (Facture $facture) {
                    return sprintf(
                        'Facture N°%s | %s | %.2f €',
                        $facture->getNum(),
                        $facture->getDate()->format('d/m/Y'),
                        $facture->getTotal()
                    );
                },
                'choice_attr' => function (Facture $facture) {
                    return ['data-total' => $facture->getTotal()];
                },
                'attr' => [
                    'class' => 'form-control',
                    'size' => 6
                ],
                'mapped' => false,
                'label' => 'Factures à associer au paiement',
                'choices' => $options['factures'],
            ])
        ;
    }            

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
            'factures' => [],
        ]);
    }
}
