<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
final class PillEntityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => false,
            'placeholder' => false,
            'attr' => [
                'class' => 'pill-field',
            ],
        ]);

        $resolver->setDefined(['pill_variant']);
        $resolver->setAllowedValues('pill_variant', ['department', 'actor', 'project', 'default']);
        $resolver->setDefault('pill_variant', 'default');
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'pill_entity';
    }
}
