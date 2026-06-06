<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use App\Util;
use Exception;
use Twig\Environment;

class CompanyController extends BaseController
{
    public function __construct(
        private CompanyRepository $repo,
        Environment $twig,
        private ?ReviewRepository $reviewRepo = null
    ) {
        parent::__construct($twig);
    }

    // -------------------------------------------------------------------------
    // API / AJAX Methods
    // -------------------------------------------------------------------------

    /**
     * API Endpoint: GET /api/companies/{id}/sites
     * Used by the Offer Editor to populate the site dropdown dynamically.
     */
    public function getSitesByCompany(string $companyId): void
    {
        if (empty($companyId)) {
            $this->jsonResponse(['error' => 'Invalid or missing Company ID'], 400);
            return;
        }

        try {
            $sites = $this->repo->findSitesByCompany((int) $companyId);
            $this->jsonResponse($sites);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Database error'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // View Rendering Methods
    // -------------------------------------------------------------------------

    /**
     * Renders the list of companies with optional filters
     */
    public function renderList(): void
    {
        $sectors = $this->repo->findAllSectors();

        $filters = [
            // clés existantes conservées
            'name'      => $_GET['name'] ?? null,
            'sector_id' => $_GET['sector_id'] ?? null,
            'status'    => $_GET['status'] ?? null,
            'page'      => (int) ($_GET['page'] ?? 1),
            'limit'     => 10,

            // alias nouvelle BDD
            'name_company'       => $_GET['name'] ?? null,
            'sector_id_company'  => $_GET['sector_id'] ?? null,
            'is_active_company'  => $_GET['status'] ?? null,
        ];

        $companies = $this->repo->search($filters);

        echo $this->twig->render('companies/company_list.html.twig', [
            'companies' => $companies,
            'sectors'   => $sectors,
            'filters'   => $filters,
            'csrf'      => Util::getCSRFToken(),
            'sidebar_active' => 'companies',
            'app' => [
                'request' => ['query' => $_GET, 'uri' => $_SERVER['REQUEST_URI']]
            ]
        ]);
    }

    /**
     * Renders the Creation/Edition form
     */
    public function renderForm(int|string $id): void
    {
        $isNew = ($id === 'new');
        $company = $isNew ? null : $this->repo->getById((int) $id);
        $sectors = $this->repo->findAllSectors();

        // --- Reviews (notes/avis) data, only relevant for existing companies ---
        $reviews   = [];
        $reviewStats = ['count' => 0, 'average' => 0.0];
        $myReview  = null;
        $currentUserId = Util::getUserId() !== null ? (int) Util::getUserId() : null;

        if (!$isNew && $this->reviewRepo !== null) {
            $reviews     = $this->reviewRepo->findByCompanyDetailed((int) $id);
            $reviewStats = $this->reviewRepo->getStatsForCompany((int) $id);
            if ($currentUserId !== null) {
                $myReview = $this->reviewRepo->findOne($currentUserId, (int) $id);
            }
        }

        echo $this->twig->render('companies/company_form.html.twig', [
            'id'         => $id,
            'company'    => $company,
            'sectors'    => $sectors,
            'csrf_token' => Util::getCSRFToken(),
            'error'      => $_SESSION['flash_error'] ?? null,
            'success'    => $_GET['success'] ?? null,
            'sidebar_active' => 'companies',
            'role' => Util::getRole()->value,
            // Review data
            'reviews'        => $reviews,
            'reviewStats'    => $reviewStats,
            'myReview'       => $myReview,
            'currentUserId'  => $currentUserId,
        ]);

        unset($_SESSION['flash_error']);
    }

    // -------------------------------------------------------------------------
    // Persistence & Logic
    // -------------------------------------------------------------------------

    /**
     * Unified handler for POST /app/companies/{id}
     */
    public function handleFormSave(int|string $id): void
    {
        try {
            $isNew = ($id === 'new');
            $finalId = $isNew ? null : (int) $id;

            // Merge aliases for the new BDD structure
            $data = array_merge($_POST, [
                'id'                  => $finalId,
                'id_company'          => $finalId,
                'name_company'        => $_POST['name_company'] ?? ($_POST['name'] ?? null),
                'description_company' => $_POST['description_company'] ?? ($_POST['description'] ?? null),
                'email_company'       => $_POST['email_company'] ?? ($_POST['email'] ?? null),
                'phone_company'       => $_POST['phone_company'] ?? ($_POST['phone'] ?? null),
                'tax_id_company'      => $_POST['tax_id_company'] ?? ($_POST['siren'] ?? null),
                'sector_id_company'   => isset($_POST['sector_id_company'])
                    ? (int) $_POST['sector_id_company']
                    : (isset($_POST['sector_id']) ? (int) $_POST['sector_id'] : null),
                'is_active_company'   => isset($_POST['is_active_company'])
                    ? 1
                    : (isset($_POST['is_active']) ? 1 : 0),
            ]);

            $company = CompanyModel::fromArray($data);

            $this->validateCompany($company);

            if (!$this->repo->push($company)) {
                throw new Exception("Échec de l'enregistrement en base de données.");
            }

            $resolvedId = $company->id_company ?? $finalId;
            $redirectUrl = "/dashboard/companies/{$resolvedId}?" . ($isNew ? "created=1" : "success=1");
            header("Location: $redirectUrl");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Deletes a company and its associated sites
     */
    public function deleteCompany(int $id): void
    {
        try {
            $companyId = (int) $id;
            $this->repo->deleteSitesByCompany($companyId);
            if (!$this->repo->deleteById($companyId)) {
                throw new Exception("Deletion failed.");
            }
            header("Location: /dashboard/companies?deleted=1");
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Erreur lors de la suppression : " . $e->getMessage();
            header("Location: /dashboard/companies");
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    private function validateCompany(CompanyModel $company): void
    {
        $name = $company->name_company ?? $company->name ?? null;
        $siren = $company->tax_id_company ?? $company->siren ?? null;

        if (empty($name)) {
            throw new \InvalidArgumentException("Le nom est obligatoire.");
        }

        if (empty($siren)) {
            throw new \InvalidArgumentException("Le SIREN est obligatoire.");
        }

        if (strlen((string) $siren) !== 9) {
            throw new \InvalidArgumentException("Le SIREN doit comporter 9 chiffres.");
        }
    }

    public function getCompaniesAjax(): void
    {
        try {
            $filters = [
                'name'      => $_GET['q'] ?? $_GET['name'] ?? null,
                'sector_id' => $_GET['sector_id'] ?? null,
                'status'    => $_GET['status'] ?? 1, // Default to active only if needed
                'limit'     => (int) ($_GET['limit'] ?? 20),
            ];

            // Reuse your existing search logic
            $companies = $this->repo->search($filters);
            
            $this->jsonResponse($companies);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to fetch companies'], 500);
        }
    }
}
