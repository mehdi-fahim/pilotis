<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\DepartmentDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DepartmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du service',
                'attr' => ['placeholder' => 'Ex. Direction des Systèmes d\'Information'],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code',
                'required' => false,
                'attr' => ['placeholder' => 'DSI, RH, FIN…'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Périmètre et mission du service…'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DepartmentDto::class,
        ]);
    }
}
