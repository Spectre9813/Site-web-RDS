<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Models\RoleEnum;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use App\Repository\UserRepository;
use Twig\Environment;
use App\Util;

class ApplicationController extends BaseController
{
    private ApplicationRepository $repo;
    private OfferRepository $offerRepository;
    private UserRepository $userRepository;

    public function __construct(ApplicationRepository $repo, OfferRepository $offerRepository, UserRepository $userRepository, Environment $twig)
    {
        parent::__construct($twig);
        $this->offerRepository = $offerRepository;
        $this->repo = $repo;
        $this->userRepository = $userRepository;
    }

    /**
     * Permet à un Admin/Pilote de voir les candidatures d'un étudiant spécifique
     */
    public function viewStudentApplications(string $id): void
    {
        // Sécurité : réservé au staff (Admin / Pilote)
        $this->abortIfNotPriv();

        $student = $this->userRepository->findById($id);

        // L'utilisateur ciblé doit exister ET être un étudiant
        if ($student === null || $student->role !== RoleEnum::Student) {
            $this->abort(404, "Étudiant introuvable.");
        }

        $currentUser = Util::getUser();
        $applications = $this->repo->findByStudentDetailed($student->id);

        echo $this->twig->render("dashboard/my_applications.html.twig", [
            "user" => $currentUser,
            "student" => $student,
            "applications" => $applications,
            "sidebar_active" => "students",
            "is_student" => false
        ]);
    }

    /**
     * GET /dashboard/pilote/candidatures
     * SFx22 — Liste des candidatures des étudiants de la promotion du pilote.
     */
    public function pilotApplications(): void
    {
        $pilotId = Util::getUserId();
        $applications = $this->repo->findByPilot($pilotId);

        echo $this->twig->render("dashboard/pilot_applications.html.twig", [
            "user" => Util::getUser(),
            "applications" => $applications,
            "sidebar_active" => "pilot_applications",
        ]);
    }

    /**
     * GET /dashboard/applications
     * Affiche les candidatures envoyées par l'étudiant connecté.
     */
    public function myApplications(): void
    {
        $myId = Util::getUserId();
        $myApplications = $this->repo->findByStudent($myId);
        $currentUser = $this->userRepository->findById($myId);

        echo $this->twig->render("dashboard/my_applications.html.twig", [
            "user" => [
                "role" => $currentUser->role->value,
                "first_name" => $currentUser->first_name,
                "last_name" => $currentUser->last_name,
                "email" => $currentUser->email,
            ],
            "applications" => $myApplications,
            "sidebar_active" => "applications",
            "is_student" => true,
        ]);
    }

    /**
     * GET /app/offers/{id}/apply
     * Affiche le formulaire de candidature
     */
    public function viewApply(string|int $id): void
    {
        $off = $this->offerRepository->findById($id);
        $usr = Util::getUser();

        // Diagnostic deserialization check
        if (is_object($off) && $off instanceof \__PHP_Incomplete_Class) {
            $className = (array) $off;
            error_log('Incomplete class: ' . ($className['__PHP_Incomplete_Class_Name'] ?? 'unknown'));
        }
        
        if (
            currentRoleValue() !== RoleEnum::Admin->value
            && currentRoleValue() !== RoleEnum::Pilote->value
        ) {
            $this->offerRepository->incrementViews((int) $id);
        }

        echo $this->twig->render('offers/apply.html.twig', [
            'offer_id' => $id,
            'offer' => is_object($off) ? (array) $off : $off,
            'user' => $usr,
            'title' => 'Candidature à l\'offre',
            'error' => 0,
            'success' => 0,
            'csrf_token' => Util::getCSRFToken(),
            'views' => $this->offerRepository->countViews((int) $id),
            'applications' => $this->repo->countByOffer((int) $id),
        ]);
    }

    private function handleFileUpload(array $file, string $type): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Only allow PDF
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return null;
        }

        // Max 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        // Determine subfolder
        $subFolder = match ($type) {
            'cv' => 'cv',
            'motivation' => 'lm',
            default => null
        };

        if (!$subFolder) {
            return null;
        }

        // Absolute storage path
        $basePath = '/var/www/html/cdn/uploads/';
        $uploadDir = $basePath . $subFolder . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = uniqid('doc_', true) . '.pdf';
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        // Return PUBLIC path (not filesystem path)
        return '/cdn/uploads/' . $subFolder . '/' . $filename;
    }

    /**
     * POST /app/offers/{id}/apply
     */
    public function doApply(string $id): void
    {
        // Handle CV upload if present
        $cvPath = $_POST['cv_path'] ?? null;
        if (!empty($_FILES['cv_file']['name'])) {
            $cvPath = $this->handleFileUpload($_FILES['cv_file'], 'cv');
        }

        // Handle motivation letter upload if present
        $coverLetterPath = $_POST['cover_letter_path'] ?? null;
        if (!empty($_FILES['cover_letter_file']['name'])) {
            $coverLetterPath = $this->handleFileUpload($_FILES['cover_letter_file'], 'motivation');
        }

        $application = ApplicationModel::fromArray([
            'student_id_application' => Util::getUserId(),
            'offer_id_application' => $id,
            'cv_path_application' => $cvPath,
            'cover_letter_path_application' => $coverLetterPath,
            'status_application' => 'pending'
        ]);

        error_log('Application data: ' . print_r($application, true));

        if ($this->repo->push($application)) {
            header('Location: /dashboard/applications?status=success');
        } else {
            header("Location: /app/offers/{$id}/apply?error=save_failed");
        }
        exit;
    }

    /**
     * POST /api/offers/{id}/apply (AJAX)
     */
    public function applyAjax(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $application = ApplicationModel::fromArray([
            'student_id_application' => Util::getUserId(),
            'offer_id_application' => $id,
            'cv_path_application' => $input['cv_path'] ?? null,
            'cover_letter_path_application' => $input['cover_letter_path'] ?? null,
            'status_application' => 'pending'
        ]);

        if ($this->repo->push($application)) {
            $this->jsonResponse(['status' => 'success', 'data' => $application], 201);
        }

        $this->jsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
    }

    /**
     * DELETE /api/applications/{id}
     */
    public function deleteAjax(string $id): void
    {
        $app = $this->repo->findById($id);

        if (!$app) {
            $this->jsonResponse(['error' => 'Candidature introuvable'], 404);
        }

        // Sécurité : Propriétaire OU Admin/Pilote
        if ($app->student_id_application !== Util::getUserId() && !$this->isPrivileged()) {
            $this->jsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $this->repo->delete($id)
            ? $this->jsonResponse(['status' => 'deleted'])
            : $this->jsonResponse(['error' => 'Échec de la suppression'], 500);
    }

    /**
     * GET /api/applications/{id}
     */
    public function showJson(string $id): void
    {
        $app = $this->repo->findById($id);

        if (!$app) {
            $this->jsonResponse(['error' => 'Not found'], 404);
        }

        $this->jsonResponse($app);
    }

    /**
     * PATCH /api/applications/{id}/status
     */
    public function updateStatusAjax(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? null;

        if (!$status) {
            $this->jsonResponse(['error' => 'Statut manquant'], 400);
        }

        $app = $this->repo->findById($id);
        if (!$app) {
            $this->jsonResponse(['error' => 'Candidature inexistante'], 404);
        }

        $app->status_application = $status;
        $this->repo->push($app);

        $this->jsonResponse(['status' => 'updated', 'new_status' => $status]);
    }
}
