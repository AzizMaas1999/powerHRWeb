<?php

namespace App\Form;

use App\Entity\Facture;
use App\Form\ArticleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Article;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FactureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('date', null, ['widget' => 'single_text'])
        ->add('typeFact')
        ->add('num')
        // Supprimer ou rendre en lecture seule le champ total
        ->add('total', NumberType::class, [
            'label' => 'Total',
            'attr' => [
                'class' => 'form-control',
                'min' => 0,
                'step' => '0.01',
                'readonly' => true
            ]
        ])
        
        
                    

        ->add('articles', EntityType::class, [
            'class' => Article::class,
            'choice_label' => 'description',
            'multiple' => true,
            'expanded' => true, // Affiche en cases à cocher
            'label' => 'Articles disponibles',
            'required' => false

            
        ]);

        

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
        ]);
    }
}
