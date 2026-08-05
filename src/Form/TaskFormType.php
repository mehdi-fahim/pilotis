<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use App\DTO\TaskDto;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TaskFormType extends AbstractType
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
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'label' => 'Projet',
                'placeholder' => 'Sélectionner un projet',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Service responsable',
                'required' => false,
                'placeholder' => 'Aucun service',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('assignedActor', EntityType::class, [
                'class' => Actor::class,
                'label' => 'Acteur assigné',
                'required' => false,
                'placeholder' => 'Non assigné',
                'choice_label' => static fn (Actor $actor): string => $actor->getFullName(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', EnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (TaskStatus $status): string => $status->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('priority', EnumType::class, [
                'class' => Priority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (Priority $priority): string => $priority->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('estimateMinutes', IntegerType::class, [
                'label' => 'Estimation (minutes)',
                'attr' => ['class' => 'form-control', 'min' => '0'],
            ])
            ->add('timeSpentMinutes', IntegerType::class, [
                'label' => 'Temps passé (minutes)',
                'attr' => ['class' => 'form-control', 'min' => '0'],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date de fin / échéance',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskDto::class,
        ]);
    }
}
