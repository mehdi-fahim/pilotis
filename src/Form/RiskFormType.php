<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Enum\RiskStatus;
use App\DTO\RiskDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RiskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('probability', IntegerType::class, [
                'label' => 'Probabilité (1-5)',
                'attr' => ['class' => 'form-control', 'min' => '1', 'max' => '5'],
            ])
            ->add('impact', IntegerType::class, [
                'label' => 'Impact (1-5)',
                'attr' => ['class' => 'form-control', 'min' => '1', 'max' => '5'],
            ])
            ->add('mitigationPlan', TextareaType::class, [
                'label' => 'Plan de mitigation',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('status', EnumType::class, [
                'class' => RiskStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (RiskStatus $status): string => $status->label(),
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RiskDto::class,
        ]);
    }
}
