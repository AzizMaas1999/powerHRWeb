<?php

namespace App\Form;
use App\Entity\Repquestionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepquestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           // ->add('dateCreation', null, [
             //   'widget' => 'single_text',
          //  ])
            ->add('reponse')
           // ->add('questionnaire_id')
            //->add('employe_id')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Repquestionnaire::class,
        ]);
    }
}
