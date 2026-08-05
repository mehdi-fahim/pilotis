<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use App\DTO\ProjectDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('forecastEndDate', DateType::class, [
                'label' => 'Date de fin prévisionnelle',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', EnumType::class, [
                'class' => ProjectStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (ProjectStatus $status): string => $status->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('priority', EnumType::class, [
                'class' => Priority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (Priority $priority): string => $priority->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('healthStatus', EnumType::class, [
                'class' => HealthStatus::class,
                'label' => 'État de santé',
                'choice_label' => static fn (HealthStatus $status): string => $status->label(),
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectDto::class,
        ]);
    }
}
