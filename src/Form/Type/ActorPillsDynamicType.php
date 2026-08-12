<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Repository\DepartmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
final class ActorPillsDynamicType extends AbstractType
{
    public function __construct(
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['browse_departments'] = $this->departmentRepository->findBy([], ['name' => 'ASC']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'placeholder' => false,
            'label' => 'Intervenants',
            'help' => 'Filtrez par service pour ajouter des personnes ; la sélection est conservée d’un service à l’autre.',
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'actor_pills_dynamic';
    }
}
