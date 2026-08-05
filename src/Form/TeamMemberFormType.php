<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\TeamMember;
use App\Domain\Entity\User;
use App\Domain\Enum\TeamRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TeamMemberFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Utilisateur',
                'placeholder' => 'Sélectionner un utilisateur',
                'choice_label' => static fn (User $user): string => $user->getFullName(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('role', EnumType::class, [
                'class' => TeamRole::class,
                'label' => 'Rôle',
                'choice_label' => static fn (TeamRole $role): string => $role->label(),
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TeamMember::class,
        ]);
    }
}
