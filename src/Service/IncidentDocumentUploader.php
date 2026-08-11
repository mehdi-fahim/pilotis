<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Incident;
use App\Domain\Entity\IncidentDocument;
use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class IncidentDocumentUploader
{
    public function __construct(
        private readonly string $targetDirectory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function upload(Incident $incident, User $uploadedBy, UploadedFile $file): IncidentDocument
    {
        $originalFilename = $file->getClientOriginalName();
        $storedFilename = sprintf('%s_%s', bin2hex(random_bytes(8)), $file->getClientOriginalName());
        $mimeType = $file->getClientMimeType() ?? 'application/octet-stream';
        $size = (int) ($file->getSize() ?? 0);

        try {
            $file->move($this->targetDirectory, $storedFilename);
        } catch (FileException $exception) {
            throw new FileException(sprintf('Unable to upload document "%s".', $originalFilename), 0, $exception);
        }

        if ($size === 0) {
            $filePath = $this->targetDirectory . DIRECTORY_SEPARATOR . $storedFilename;
            $size = is_file($filePath) ? (int) filesize($filePath) : 0;
        }

        $document = (new IncidentDocument())
            ->setIncident($incident)
            ->setOriginalFilename($originalFilename)
            ->setStoredFilename($storedFilename)
            ->setMimeType($mimeType)
            ->setSize($size)
            ->setUploadedBy($uploadedBy);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    public function remove(IncidentDocument $document): void
    {
        $filePath = $this->getFilePath($document);

        if (is_file($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }

    public function getFilePath(IncidentDocument $document): string
    {
        return $this->targetDirectory . DIRECTORY_SEPARATOR . $document->getStoredFilename();
    }
}
