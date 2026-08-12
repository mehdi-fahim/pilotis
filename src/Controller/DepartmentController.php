<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Department;
use App\DTO\DepartmentDto;
use App\Form\DepartmentFormType;
use App\Repository\DepartmentRepository;
use App\Service\CsvExporter;
use App\Service\ListFilterResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/services')]
#[IsGranted('ROLE_ADMIN')]
final class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly DepartmentRepository $departmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsvExporter $csvExporter,
        private readonly ListFilterResolver $filters,
    ) {
    }

    #[Route('', name: 'app_department_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');

        return $this->render('department/index.html.twig', [
            'departments' => $this->departmentRepository->findFiltered($q),
            'filters' => ['q' => $q ?? ''],
        ]);
    }

    #[Route('/export.csv', name: 'app_department_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');
        $rows = [];
        foreach ($this->departmentRepository->findFiltered($q) as $department) {
            $rows[] = [
                $department->getName(),
                $department->getCode(),
                $department->getActors()->count(),
                $department->getTasks()->count(),
            ];
        }

        return $this->csvExporter->export('services.csv', ['Nom', 'Code', 'Acteurs', 'Tâches'], $rows);
    }

    #[Route('/new', name: 'app_department_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new DepartmentDto();
        $form = $this->createForm(DepartmentFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $department = $this->mapDtoToEntity(new Department(), $dto);
            $this->entityManager->persist($department);
            $this->entityManager->flush();

            $this->addFlash('success', 'Service créé.');

            return $this->redirectToRoute('app_department_show', ['id' => $department->getId()]);
        }

        return $this->render('department/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_department_show', methods: ['GET'])]
    public function show(Department $department): Response
    {
        return $this->render('department/show.html.twig', [
            'department' => $department,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_department_edit', methods: ['GET', 'POST'])]
    public function edit(Department $department, Request $request): Response
    {
        $dto = $this->mapEntityToDto($department);
        $form = $this->createForm(DepartmentFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mapDtoToEntity($department, $dto);
            $this->entityManager->flush();

            $this->addFlash('success', 'Service mis à jour.');

            return $this->redirectToRoute('app_department_show', ['id' => $department->getId()]);
        }

        return $this->render('department/edit.html.twig', [
            'form' => $form,
            'department' => $department,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_department_delete', methods: ['POST'])]
    public function delete(Department $department, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-department-' . $department->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($department);
        $this->entityManager->flush();

        $this->addFlash('success', 'Service supprimé.');

        return $this->redirectToRoute('app_department_index');
    }

    private function mapEntityToDto(Department $department): DepartmentDto
    {
        $dto = new DepartmentDto();
        $dto->name = $department->getName();
        $dto->code = $department->getCode();
        $dto->description = $department->getDescription();

        return $dto;
    }

    private function mapDtoToEntity(Department $department, DepartmentDto $dto): Department
    {
        return $department
            ->setName((string) $dto->name)
            ->setCode($dto->code)
            ->setDescription($dto->description);
    }
}
