<?php
namespace App\Form;

use App\Entity\Employe;
use App\Entity\FicheEmploye;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Validator\Constraints\File;

class FicheEmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'attr' => ['class' => 'form-control']
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control']
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
            ->add('zip', TextType::class, [
                'label' => 'zip',
                'attr' => ['class' => 'form-control']
            ])
            ->add('numTel', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control']
            ])
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => 'username', 
                'placeholder' => 'Sélectionner un employé',
                'label' => 'Employé',
                'attr' => ['class' => 'form-select']
            ])
            ->add('cvPdfFile', VichFileType::class, [
                'label' => 'Télécharger CV (PDF)',
                'required' => true,
                'allow_delete' => true,
                'download_uri' => true,
                'asset_helper' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FicheEmploye::class,
        ]);
    }
}
