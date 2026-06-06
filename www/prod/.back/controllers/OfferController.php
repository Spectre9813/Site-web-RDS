<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\ApplicationRepository;
use App\Util;
use App\Repository\OfferRepository;
use App\Repository\CompanyRepository;
use App\Repository\WishListRepository;
use App\Models\OfferModel;
use PDO;
use Twig\Environment;

class OfferController extends BaseController
{
    public function __construct(
        Environment $twig,
        private readonly OfferRepository $offerRepository,
        protected readonly PDO $pdo
    ) {
        parent::__construct($twig);

        if (Util::getCSRFToken() === null) {
            Util::setCSRFToken(bin2hex(random_bytes(32)));
        }
    }

    private function validateCSRF(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (empty($token) || $token !== Util::getCSRFToken()) {
            $this->abort(403, "Requête invalide (CSRF Token mismatch).");
        }
    }

    public function edit(string $id): void
    {
        $this->abortIfNotPriv();

        $offer = $this->offerRepository->findById((int) $id);

        if (!$offer) {
            $this->abort(404, "Offre introuvable.");
        }

        $siteRepo = new CompanyRepository($this->pdo);
        $companyId = $offer->company_id_company_site ?? $offer->company_id ?? null;
        $sites = $companyId ? $siteRepo->findSitesByCompany((int) $companyId) : [];

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'edit',
            'offer' => $offer,
            'sites' => $sites,
            'error' => null,
            'companies' => [],
            'csrf_token' => Util::getCSRFToken(),
            'sidebar_active' => 'offers',
        ]);
    }

    public function index(): void
    {
        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $filters = [
            'title_internship_offer' => $_GET['q'] ?? null,
            'city_company_site' => $_GET['city'] ?? null,
        ];

        $offers = $this->offerRepository->findPaginated($limit, $offset);
        $totalOffers = $this->offerRepository->countAll();
        $totalPages = (int) ceil($totalOffers / $limit);

        echo $this->twig->render('offers/index.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged(),
            'sidebar_active' => 'offers',
        ]);
    }

    public function search(): void
    {
        // Collect filters from query parameters
        $filters = [
            'keyword' => $_GET['keyword'] ?? null,
            'city' => $_GET['city'] ?? null,
            'duration' => !empty($_GET['duration']) ? (int) $_GET['duration'] : null,
            'sort' => $_GET['sort'] ?? 'recent',
        ];

        // Pagination
        $limit = 10;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        // Fetch results
        $results = $this->offerRepository->advancedSearch($filters, $limit, $offset);
        $offers = $results['data'] ?? [];
        $totalCount = $results['total'] ?? 0;
        $totalPages = (int) ceil($totalCount / $limit);

        // Render template
        echo $this->twig->render('offers/offer_search.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'count' => $totalCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged(),
            'sidebar_active' => 'offers',
        ]);
    }

    /**
     * SFx11 — Statistiques globales des offres (accessible à tous).
     */
    public function stats(): void
    {
        $totalOffers = $this->offerRepository->countAll();

        // Moyenne de candidatures par offre
        $avg = 0.0;
        try {
            $totalApplications = (int) $this->pdo
                ->query("SELECT COUNT(*) FROM application")
                ->fetchColumn();
            $avg = $totalOffers > 0 ? $totalApplications / $totalOffers : 0.0;
        } catch (\Throwable $e) {
            $avg = 0.0;
        }

        // Répartition par durée (en semaines)
        $durationBreakdown = [];
        try {
            $rows = $this->pdo->query(
                "SELECT duration_weeks_internship_offer AS weeks, COUNT(*) AS nb
                 FROM internship_offer
                 WHERE duration_weeks_internship_offer IS NOT NULL
                 GROUP BY duration_weeks_internship_offer
                 ORDER BY duration_weeks_internship_offer"
            )->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $durationBreakdown[] = [
                    'label' => $row['weeks'] . ' semaines',
                    'value' => (int) $row['nb'],
                ];
            }
        } catch (\Throwable $e) {
            $durationBreakdown = [];
        }

        // Top 5 des offres les plus ajoutées en wish-list
        $topWishlist = [];
        try {
            $topWishlist = $this->pdo->query(
                "SELECT o.title_internship_offer AS label, COUNT(*) AS value
                 FROM wishlist w
                 JOIN internship_offer o ON o.id_internship_offer = w.offer_id_wishlist
                 GROUP BY o.id_internship_offer, o.title_internship_offer
                 ORDER BY value DESC
                 LIMIT 5"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $topWishlist = [];
        }

        echo $this->twig->render('offers/stats.html.twig', [
            'total_offers'         => $totalOffers,
            'average_applications' => $avg,
            'duration_breakdown'   => $durationBreakdown,
            'top_wishlist'         => $topWishlist,
        ]);
    }

    public function searchJson(): void
    {
        $filters = [
            'title_internship_offer' => $_GET['keyword'] ?? null,
            'city_company_site' => $_GET['city'] ?? null,
            'duration_weeks_internship_offer' => !empty($_GET['duration']) ? (int) $_GET['duration'] : null,
            'sort' => $_GET['sort'] ?? 'recent',
        ];

        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $results = $this->offerRepository->advancedSearch($filters, $limit, $offset);

        $this->jsonResponse([
            'data' => $results['data'] ?? [],
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'total_pages' => (int) ceil(($results['total'] ?? 0) / $limit),
        ]);
    }

    public function show(int $id): void
    {
        $offer = $this->offerRepository->findById((int) $id);

        if (!$offer) {
            $this->abort(404, "L'offre d'internat introuvable.");
        }

        if (!$this->isPrivileged()) {
            $this->offerRepository->incrementViews((int) $offer->id);
            $offer->views++;
            $offer->views_internship_offer++;
        }

        $isWishlisted = false;

        if (Util::getRole()?->value === 'student') {
            $wishlistRepo = new WishListRepository($this->pdo);
            $isWishlisted = $wishlistRepo->exists((int) Util::getUserId(), (int) $offer->id);
        }

        $skillsString = $this->offerRepository->getSkillsAsString((int) $offer->id);
        $skills = array_filter(array_map('trim', explode(',', $skillsString)));

        // Navigation logic from 'main'
        $from = $_GET['from'] ?? 'offers';

        // Application and Analytics logic from 'HEAD'
        $applicationRepo = new ApplicationRepository($this->pdo);

        // Optional: Keep these if you still need the debug logs, otherwise they can be removed
        error_log("views" . $this->offerRepository->countViews((int) $id));
        error_log("applications" . $applicationRepo->countByOffer((int) $id));

        echo $this->twig->render('offers/show.html.twig', [
            'offer' => $offer,
            'isPrivileged' => $this->isPrivileged(),
            'csrf_token' => Util::getCSRFToken(),
            'isWishlisted' => $isWishlisted,
            'skills' => $skills,
            'from' => $from,
            'views' => (string) $this->offerRepository->countViews((int) $offer->id),
            'applications' => (string) $applicationRepo->countByOffer((int) $id)
        ]);
    }

    public function create(): void
    {
        $this->abortIfNotPriv();

        $rep = new CompanyRepository($this->pdo);
        $companies = $rep->findAllActive();

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'create',
            'error' => null,
            'companies' => $companies,
            'sites' => [],
            'isPrivileged' => $this->isPrivileged(),
            'offer' => new OfferModel(),
            'csrf_token' => Util::getCSRFToken(),
            'sidebar_active' => 'offers',
        ]);
    }

    public function destroy(int $id): void
    {
        $this->abortIfNotPriv();

        if ($this->offerRepository->delete((int) $id)) {
            header('Location: /app/offers?deleted=1');
            exit;
        }

        $this->abort(400, "Impossible de supprimer cette offre.");
    }

    public function store(): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF();

        $siteId = $_POST['site_id'] ?? ($_POST['company_site_id_internship_offer'] ?? '');

        $data = [
            'title_internship_offer' => $_POST['title'] ?? '',
            'description_internship_offer' => $_POST['description'] ?? null,
            'hourly_rate_internship_offer' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : null,
            'start_date_internship_offer' => $_POST['start_date'] ?? null,
            'duration_weeks_internship_offer' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'company_site_id_internship_offer' => $siteId,
            'required_skills' => $_POST['required_skills'] ?? null,
        ];

        if ($this->offerRepository->create($data)) {
            header('Location: /app/offers?success=1');
            exit;
        }

        $this->abort(500, "Erreur lors de la création de l'offre.");
    }

    public function update(string $id): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF();

        $siteId = $_POST['site_id'] ?? ($_POST['company_site_id_internship_offer'] ?? '');

        $data = [
            'title_internship_offer' => $_POST['title'] ?? '',
            'description_internship_offer' => $_POST['description'] ?? '',
            'hourly_rate_internship_offer' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.0,
            'is_active_internship_offer' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'start_date_internship_offer' => $_POST['start_date'] ?? null,
            'duration_weeks_internship_offer' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'company_site_id_internship_offer' => $siteId,
            'required_skills' => $_POST['required_skills'] ?? null,
        ];

        if ($this->offerRepository->update((int) $id, $data)) {
            header("Location: /app/offers/show/$id?updated=1");
            exit;
        }

        $this->abort(500, "Erreur lors de la mise à jour.");
    }
}
