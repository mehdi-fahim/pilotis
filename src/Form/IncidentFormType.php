<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Enum\IncidentStatus;
use App\Domain\Enum\Priority;
use App\DTO\IncidentDto;
use App\Form\Type\ActorPillsDynamicType;
use App\Form\Type\PillChoiceType;
use App\Form\Type\PillEnumType;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class IncidentFormType extends AbstractType
{
    public function __construct(
        private readonly ActorRepository $actorRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isCreate = (bool) $options['is_create'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Ex. Panne réseau service RH'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du problème',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('priority', PillEnumType::class, [
                'class' => Priority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (Priority $priority): string => $priority->label(),
            ])
            ->add('discoveredAt', DateTimeType::class, [
                'label' => 'Date de découverte du bug',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('environment', PillChoiceType::class, [
                'label' => 'Environnement',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choices' => [
                    'Production' => 'production',
                    'Préproduction' => 'preproduction',
                    'Recette / QA' => 'recette',
                    'Développement' => 'development',
                    'Autre' => 'other',
                ],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Service impacté',
                'required' => false,
                'placeholder' => 'Sélectionner un service',
                'choice_label' => static fn (Department $department): string => $department->getName(),
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('d')
                    ->orderBy('d.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select form-select-modern',
                ],
            ])
            ->add('reproductionSteps', TextareaType::class, [
                'label' => 'Étapes de reproduction',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => '1. Ouvrir… 2. Cliquer sur…'],
            ])
            ->add('impact', TextareaType::class, [
                'label' => 'Impact métier',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Ex. 50 utilisateurs bloqués'],
            ])
            ->add('solution', TextareaType::class, [
                'label' => 'Solution apportée',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Décrire la solution mise en place ou envisagée…',
                ],
                'help' => 'Peut être renseignée dès la création, puis complétée au fil du traitement.',
            ])
        ;

        $this->addAssignedActorsField($builder, []);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $dto = $event->getData();
            $choices = $dto instanceof IncidentDto ? $dto->assignedActors : [];
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

        if (!$isCreate) {
            $builder
                ->add('status', PillEnumType::class, [
                    'class' => IncidentStatus::class,
                    'label' => 'Statut',
                    'choice_label' => static fn (IncidentStatus $status): string => $status->label(),
                ])
                ->add('rootCause', TextareaType::class, [
                    'label' => 'Cause racine',
                    'required' => false,
                    'attr' => ['rows' => 3],
                ])
                ->add('dueDate', DateType::class, [
                    'label' => 'Échéance SLA (manuelle)',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'required' => false,
                    'help' => 'Laisser vide pour recalculer selon priorité et date de découverte.',
                ])
            ;
        }
    }

    /**
     * @param list<Actor> $choices
     */
    private function addAssignedActorsField(FormBuilderInterface|\Symfony\Component\Form\FormInterface $form, array $choices): void
    {
        $form->add('assignedActors', ActorPillsDynamicType::class, [
            'class' => Actor::class,
            'choices' => $choices,
            'choice_label' => static fn (Actor $actor): string => $actor->getFullName(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncidentDto::class,
            'is_create' => false,
        ]);
    }
}
