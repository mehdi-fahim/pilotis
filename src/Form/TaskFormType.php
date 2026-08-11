<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use App\DTO\TaskDto;
use App\Form\Type\PillEnumType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
                'label' => 'Titre de la tâche',
                'attr' => ['placeholder' => 'Ex. Rédiger le cahier des charges'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Détails, contexte, critères d\'acceptation…'],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'label' => 'Projet rattaché',
                'placeholder' => 'Sélectionner un projet',
                'choice_label' => static fn (Project $project): string => $project->getName(),
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('p')
                    ->orderBy('p.name', 'ASC'),
                'attr' => ['class' => 'form-select form-select-modern'],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Service responsable',
                'required' => false,
                'placeholder' => 'Aucun service',
                'choice_label' => static fn (Department $department): string => $department->getName(),
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('d')
                    ->orderBy('d.name', 'ASC'),
                'attr' => ['class' => 'form-select form-select-modern'],
            ])
            ->add('assignedActor', EntityType::class, [
                'class' => Actor::class,
                'label' => 'Acteur assigné',
                'required' => false,
                'placeholder' => 'Non assigné',
                'choice_label' => static function (Actor $actor): string {
                    $label = $actor->getFullName();
                    if ($actor->getDepartment()) {
                        $label .= ' · ' . $actor->getDepartment()->getName();
                    }

                    return $label;
                },
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('a')
                    ->leftJoin('a.department', 'd')
                    ->addOrderBy('a.lastName', 'ASC')
                    ->addOrderBy('a.firstName', 'ASC'),
                'attr' => ['class' => 'form-select form-select-modern'],
            ])
            ->add('status', PillEnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (TaskStatus $status): string => $status->label(),
            ])
            ->add('priority', PillEnumType::class, [
                'class' => Priority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (Priority $priority): string => $priority->label(),
            ])
            ->add('estimateMinutes', IntegerType::class, [
                'label' => 'Estimation (minutes)',
                'attr' => ['min' => '0', 'placeholder' => '480'],
            ])
            ->add('timeSpentMinutes', IntegerType::class, [
                'label' => 'Temps passé (minutes)',
                'attr' => ['min' => '0', 'placeholder' => '0'],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Échéance',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
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
