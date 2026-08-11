<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Entity\Department;
use App\DTO\ActorDto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ActorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Marie'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Dupont'],
            ])
            ->add('role', TextType::class, [
                'label' => 'Fonction / rôle',
                'required' => false,
                'attr' => ['placeholder' => 'Sponsor, Chef de service…'],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Service de rattachement',
                'required' => false,
                'placeholder' => 'Sélectionner un service',
                'choice_label' => static fn (Department $department): string => $department->getName(),
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('d')
                    ->orderBy('d.name', 'ASC'),
                'attr' => ['class' => 'form-select form-select-modern'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'required' => false,
                'attr' => ['placeholder' => 'marie.dupont@exemple.fr'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '+33 6 00 00 00 00'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActorDto::class,
        ]);
    }
}
