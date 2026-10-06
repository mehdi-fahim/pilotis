<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use App\DTO\TaskDto;
use App\Form\Type\ActorPillsDynamicType;
use App\Form\Type\PillEnumType;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TaskFormType extends AbstractType
{
    public function __construct(
        private readonly ActorRepository $actorRepository,
    ) {
    }

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

        $this->addAssignedActorsField($builder, []);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $dto = $event->getData();
            $choices = $dto instanceof TaskDto ? $dto->assignedActors : [];
            $this->addAssignedActorsField($event->getForm(), $choices);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $ids = array_map(
                static fn (mixed $id): int => (int) $id,
                (array) ($data['assignedActors'] ?? []),
            );
            $choices = $this->actorRepository->findByIds($ids);
            $this->addAssignedActorsField($event->getForm(), $choices);
        });
    }

    /**
     * @param list<Actor> $choices
     */
    private function addAssignedActorsField(FormBuilderInterface|FormInterface $form, array $choices): void
    {
        $form->add('assignedActors', ActorPillsDynamicType::class, [
            'class' => Actor::class,
            'label' => 'Acteurs assignés',
            'choices' => $choices,
            'choice_label' => static fn (Actor $actor): string => $actor->getFullName(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskDto::class,
        ]);
    }
}
