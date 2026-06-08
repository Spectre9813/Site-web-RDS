<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CampusModel;
use App\Models\RoleEnum;
use App\Repository\CampusRepository;
use App\Util;
use Twig\Environment;

class CampusController extends BaseController
{
    public function __construct(
        Environment $twig,
        private CampusRepository $campusRepository
    ) {
        parent::__construct($twig);
    }

    /**
     * Retourne tous les campus.
     * Réservé aux utilisateurs privilégiés (Admin ou Pilote).
     *
     * @return CampusModel[]
     */
    public function index(): void
    {

        $campuses = $this->campusRepository->findAll();
        $this->abortIfNotPriv();

        echo $this->twig->render('campuses/index.html.twig', [
            'campuses' => $campuses,   // array of CampusModel instances
            'filters' => ['keyword' => $_GET['keyword'] ?? ''],
            'isPrivileged' => $this->isPrivileged(), // or however you check privileges
            'csrf' => Util::getCSRFToken(),
            'sidebar_active' => 'campuses'
        ]);
    }

    /**
     * Retourne un campus par son identifiant.
     */
    public function getById(int $id): CampusModel
    {
        $this->abortIfNotPriv();

        $campus = $this->campusRepository->getById($id);

        if ($campus === null) {
            $this->abort(404, "Campus not found.");
        }

        return $campus;
    }

    /**
     * Crée ou met à jour un campus depuis un tableau d'attributs.
     * $data['id_campus'] est optionnel — absent ou vide déclenche un INSERT, sinon UPDATE.
     *
     * Clés attendues : name_campus, address_campus, (optionnel) id_campus
     */
    public function store(array $data): void
    {
        $this->abortIfNotPriv();

        if (empty($data['name_campus']) || empty($data['address_campus'])) {
            $this->jsonError('Fields "name_campus" and "address_campus" are required', 422);
        }

        $campus = CampusModel::fromArray([
            'id_campus'      => $data['id_campus'] ?? null,
            'name_campus'    => $data['name_campus'],
            'address_campus' => $data['address_campus'],
        ]);

        $success = $this->campusRepository->save($campus);

        if (!$success) {
            $this->jsonError('Failed to save campus', 500);
        }
    }

    public function findAllAsAjax(): string
    {
        $this->abortIfNotPriv();

        $campuses = $this->campusRepository->findAll();

//        header('Content-Type: application/json');
        return json_encode($campuses);
    }

    public function getAllAjax(): void
    {
        header('Content-Type: application/json');
        echo $this->findAllAsAjax();
    }

    /**
     * Supprime un campus par son identifiant.
     * Réservé aux Admin uniquement.
     */
    public function deleteById(int $id): void
    {
        $this->checkRole([RoleEnum::Admin]);

        $campus = $this->campusRepository->getById($id);

        if ($campus === null) {
            $this->abort(404, "Campus not found.");
        }

        $this->campusRepository->deleteById($id);
    }

    /**
     * Affiche le formulaire de création (GET) et enregistre un nouveau campus (POST).
     * Réservé aux utilisateurs privilégiés (Admin ou Pilote).
     */
    public function create(): void
    {
        $this->abortIfNotPriv();

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name    = trim((string) ($_POST['name_campus'] ?? ''));
            $address = trim((string) ($_POST['address_campus'] ?? ''));

            if ($name === '') {
                $error = "Le nom du campus est requis.";
            } else {
                $campus = CampusModel::fromArray([
                    // pas d'id_campus -> le repository effectue un INSERT
                    'name_campus'    => $name,
                    'address_campus' => $address !== '' ? $address : null,
                ]);

                if ($this->campusRepository->save($campus)) {
                    $this->redirect('/dashboard/campus');
                }

                $error = "L'enregistrement du campus a échoué. Veuillez réessayer.";
            }
        }

        echo $this->twig->render('campuses/create.html.twig', [
            'sidebar_active' => 'campuses',
            'error'          => $error,
            'old'            => $_POST ?? [],
        ]);
    }

    public function edit(int|string $id): void
    {
        $this->abortIfNotPriv();

        // Fetch campus or abort if not found
        $campus = $this->campusRepository->getById($id);
        if (!$campus) {
            $this->abort(404, "Campus introuvable.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name_campus'] ?? '');
            $address = trim($_POST['address_campus'] ?? null);

            if ($name === '') {
                $error = "Le nom du campus est requis.";
            } else {
                $campus->name_campus = $name;
                $campus->address_campus = $address;
                $this->campusRepository->save($campus);

                $this->redirect('/dashboard/campus');
            }
        }

        echo $this->twig->render('campuses/edit.html.twig', [
            'sidebar_active' => 'campuses',
            'campus' => $campus,
            'error' => $error ?? null,
        ]);
    }

    // Delete a campus
    public function delete(int|string $id): void
    {
        $this->abortIfNotPriv();

        // Protection CSRF (cohérent avec les formulaires entreprises/étudiants)
        $token = $_POST['csrf_token'] ?? '';
        if (!Util::validateCSRFToken($token)) {
            $this->abort(403, "Requête invalide (CSRF Token mismatch).");
        }

        $campus = $this->campusRepository->getById($id);
        if (!$campus) {
            $this->abort(404, "Campus introuvable.");
        }

        // Optionally, you could check for linked offers before deletion
        $this->campusRepository->deleteById($id);

        $this->redirect('/dashboard/campus');
    }
}
