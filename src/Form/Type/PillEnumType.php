<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
final class PillEnumType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => false,
            'attr' => [
                'class' => 'pill-field pill-field-enum',
            ],
        ]);
    }

    public function getParent(): string
    {
        return EnumType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'pill_enum';
    }
}
