<?php
// prod/index.php

declare(strict_types=1);

use App\Controllers\ApplicationController;
use App\Controllers\AuthController;
use App\Controllers\CampusController;
use App\Controllers\CVFast;
use App\Controllers\CompanyController;
use App\Controllers\DashboardController;
use App\Controllers\OfferController;
use App\Controllers\PilotController;
use App\Controllers\PromotionController;
use App\Controllers\ReviewController;
use App\Controllers\SiteController;
use App\Controllers\SkillController;
use App\Controllers\StudentController;
use App\Controllers\WishListController;
use App\Controllers\LegalsController;
use App\Controllers\DataExportController;
use App\Controllers\AccountDeletionController;
use App\Models\RoleEnum;
use App\Repository\ApplicationRepository;
use App\Repository\CampusRepository;
use App\Repository\CompanyRepository;
use App\Repository\CompanySiteRepository;
use App\Repository\OfferRepository;
use App\Repository\PromotionRepository;
use App\Repository\ReviewRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Repository\WishListRepository;
use App\Util;
use App\Util\ComplianceLogger;
use App\Util\DataDeletionManager;

// --- 1. SESSION & SECURITY ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. REQUIRES & FACTORIES ---
require_once __DIR__ . '/.back/util/config.php';
require_once __DIR__ . '/.back/util/Router.php';

$twig = TwigFactory::getTwig();

// --- 3. HELPERS & VIEW DATA ---
function currentRoleValue(): ?string
{
    return Util::getRole()?->value;
}

function isPrivilegedUser(): bool
{
    return in_array(currentRoleValue(), [
        RoleEnum::Admin->value,
        RoleEnum::Pilote->value,
    ], true);
}

function defaultViewData(array $data = []): array
{
    return array_merge([
        'isLoggedIn' => Util::isLoggedIn(),
        'isPrivileged' => isPrivilegedUser(),
        'currentUser' => Util::getUser(),
        'currentRole' => currentRoleValue(),
    ], $data);
}

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';

unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$twig->addGlobal('isLoggedIn', Util::isLoggedIn());
$twig->addGlobal('isPrivileged', isPrivilegedUser());
$twig->addGlobal('currentUser', Util::getUser());
$twig->addGlobal('currentRole', currentRoleValue());
$twig->addGlobal('flashMessage', $flashMessage);
$twig->addGlobal('flashType', $flashType);

// --- 4. ROUTER SETUP ---
$router = new Router($pdo, $twig);

$staff = [RoleEnum::Admin->value, RoleEnum::Pilote->value];
$everyone = [RoleEnum::Admin->value, RoleEnum::Pilote->value, RoleEnum::Student->value];
$admin= [RoleEnum::Admin->value];
$pilote = [RoleEnum::Pilote->value];
$student = [RoleEnum::Student->value];
$idPattern = '([a-fA-F0-9]{32}|[0-9]+)';

// --- 5. CONTROLLER FACTORIES ---
$authHandler = fn($pdo, $twig) => new AuthController(new UserRepository($pdo), $twig, $pdo);
$cvHandler = fn($pdo, $twig) => new CVFast(new UserRepository($pdo), $twig, $pdo);
$dashHandler = fn($pdo, $twig) => new DashboardController($twig);
$compHandler = fn($pdo, $twig) => new CompanyController(new CompanyRepository($pdo), $twig, new ReviewRepository($pdo));
$reviewHandler = fn($pdo, $twig) => new ReviewController(new ReviewRepository($pdo), $twig);
$offerHandler = fn($pdo, $twig) => new OfferController($twig, new OfferRepository($pdo), $pdo);
$siteHandler = fn($pdo, $twig) => new SiteController(new CompanySiteRepository($pdo), new CompanyRepository($pdo), $twig);
$skillHandler = fn($pdo, $twig) => new SkillController(new SkillRepository($pdo), $twig);

$appHandler = fn($pdo, $twig) => new ApplicationController(
    new ApplicationRepository($pdo),
    new OfferRepository($pdo),
    new UserRepository($pdo),
    $twig
);

$wishlistHandler = fn($pdo, $twig) => new WishListController(new WishlistRepository($pdo), $twig);
$pilotHandler = fn($pdo, $twig) => new PilotController(new UserRepository($pdo), $twig, $pdo);
$studentHandler = fn($pdo, $twig) => new StudentController(new UserRepository($pdo), $twig, $pdo);
$campusHandler = fn($pdo, $twig) => new CampusController($twig, new CampusRepository($pdo));
$promotionHandler = fn($pdo, $twig) => new PromotionController($twig, new PromotionRepository($pdo), new CampusRepository($pdo));
$legalsHandler = fn($pdo, $twig) => new LegalsController($twig);
$complianceLogger = new ComplianceLogger($pdo);
$dataExportHandler = fn($pdo, $twig) => new DataExportController($pdo, $twig, $complianceLogger);
//$dataDeletionManager = new DataDeletionManager($pdo, $complianceLogger, 'https://example.fr', 'legal@example.fr');
//$accountDeletionHandler = fn($pdo, $twig) => new AccountDeletionController($pdo, $twig, $complianceLogger, $dataDeletionManager);

$promotionHandler = fn($pdo, $twig) => new PromotionController(
    $twig,
    new PromotionRepository($pdo),
    new CampusRepository($pdo)
);

// --- 6. ROUTES ---
// ════════════════════════════════════════════════════════════════════════════
// AUTH & AUTHENTICATION
// ════════════════════════════════════════════════════════════════════════════
$router->add('GET', '/login', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('POST', '/login', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('GET', '/logout', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->logout());

// Use the $everyone array to ensure Anonyme (Public) is excluded
$router->add('GET', '/profile', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);
$router->add('POST', '/profile', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);

// Ensure the predicate actually validates ownership
$router->add(
    'GET',
    '/api/profile/get-lms',
    fn($p, $pdo, $twig) => $cvHandler($pdo, $twig)->ajaxGetAllLms(Util::getUserId()),
    roles: $student,
    predicate: fn($p) => Util::getUserId() !== null // Or compare against a route param if applicable
);

// ════════════════════════════════════════════════════════════════════════════
// HOME PAGE
// ════════════════════════════════════════════════════════════════════════════
$router->add('GET', '/', function ($p, $pdo, $twig) {
    $offerRepo = new OfferRepository($pdo);
    $applicationRepo = new ApplicationRepository($pdo);

    $limit = 5;
    $page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    $totalOffers = $offerRepo->countAll();

    $studentProgress = [
        'pending' => 0,
        'accepted' => 0,
        'rejected' => 0,
    ];

    $recentApplications = [];

    if (currentRoleValue() === RoleEnum::Student->value && Util::getUserId() !== null) {
        $studentId = (int) Util::getUserId();
        $studentProgress = $applicationRepo->getStudentProgress($studentId);
        $recentApplications = $applicationRepo->findStudentApplicationsOverview($studentId, 5);
    }

    echo $twig->render('index.html.twig', defaultViewData([
        'offers' => $offerRepo->findPaginated($limit, $offset),
        'totalOffers' => $totalOffers,
        'totalPages' => (int) ceil($totalOffers / $limit),
        'page' => $page,
        'studentProgress' => $studentProgress,
        'recentApplications' => $recentApplications,
    ]));
});

// ════════════════════════════════════════════════════════════════════════════
// DASHBOARDS
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/admin/dashboard',
    fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(),
    roles: $admin
);

$router->add(
    'GET',
    '/pilote/dashboard',
    fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(),
    roles: $pilote
);

$router->add('GET', '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());
$router->add('POST', '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());

// ════════════════════════════════════════════════════════════════════════════
// COMPANIES (Management & API)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/companies',
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderList(),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/companies/new',
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm('new'),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/companies/new',
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave('new'),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/companies/' . $idPattern,
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm((int) $p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/companies/' . $idPattern,
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave((int) $p[0]),
    roles: $staff
);

$router->add('GET', '/api/companies', fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->getCompaniesAjax());

// ── Company reviews (notes/avis) — admins AND pilots ──────────────────────────
$router->add(
    'POST',
    '/dashboard/companies/' . $idPattern . '/reviews',
    fn($p, $pdo, $twig) => $reviewHandler($pdo, $twig)->store((int) $p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/companies/' . $idPattern . '/reviews/delete',
    fn($p, $pdo, $twig) => $reviewHandler($pdo, $twig)->delete((int) $p[0]),
    roles: $staff
);

$router->add(
    'GET',
    '/api/companies/' . $idPattern . '/sites',
    fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->getSitesByCompany($p[0]),
    roles: $staff
);

// ════════════════════════════════════════════════════════════════════════════
// SITES (Management & API)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/companies/' . $idPattern . '/sites',
    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->index((int) $p[0]),
    roles: $staff
);

$router->add(
    'GET',
    '/app/sites/new',
    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->new(),
    roles: $staff
);

$router->add(
    'POST',
    '/app/sites/save',
    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->handleSave(),
    roles: $staff
);

$router->add(
    'GET',
    '/app/sites/' . $idPattern,
    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->show((int) $p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/app/sites/delete/' . $idPattern,
    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->delete((int) $p[0]),
    roles: $staff
);

// ════════════════════════════════════════════════════════════════════════════
// OFFERS (Management, Public Display & API)
// ════════════════════════════════════════════════════════════════════════════
$router->add('GET', '/app/offers', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());
$router->add('GET', '/app/offers/search', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());

$router->add(
    'GET',
    '/app/offers/show/' . $idPattern,
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->show((int) $p[0])
);

$router->add(
    'GET',
    '/dashboard/offers/new',
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->create(),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/offers/new',
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->store(),
    roles: $staff
);

$router->add(
    'GET',
    '/app/offers/edit/' . $idPattern,
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->edit($p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/app/offers/update/' . $idPattern,
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->update($p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/app/offers/delete/' . $idPattern,
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->destroy((int) $p[0]),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/offers',
    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->index(),
    roles: $everyone
);

// ════════════════════════════════════════════════════════════════════════════
// SKILLS (Management & API)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/skills',
    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->index(),
    roles: $everyone
);

$router->add('GET', '/api/skills', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->listJson());

$router->add(
    'POST',
    '/api/skills/create',
    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->createAjax(),
    roles: $admin
);

$router->add(
    'PATCH',
    '/api/skills/update/' . $idPattern,
    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->updateAjax($p),
    roles: $admin
);

$router->add(
    'DELETE',
    '/api/skills/delete/' . $idPattern,
    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->deleteAjax($p),
    roles: $admin
);

$router->add(
    'GET',
    '/api/campus/' . $idPattern . '/promotions',
    fn($p, $pdo, $twig) => $promotionHandler($pdo, $twig)->getAllAjax($p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/api/campus/' . $idPattern . '/promotions',
    fn($p, $pdo, $twig) => $promotionHandler($pdo, $twig)->store(array_merge(
        json_decode(file_get_contents('php://input'), true) ?? [],
        ['campus_id_promotion' => $p[0]]
    )),
    roles: $staff
);

$router->add(
    'GET',
    '/api/campus',
    fn($p, $pdo, $twig) => $campusHandler($pdo, $twig)->getAllAjax(),
    roles: $staff
);

// ════════════════════════════════════════════════════════════════════════════
// APPLICATIONS (Student & Staff Management)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/app/offers/' . $idPattern . '/apply',
    fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewApply($p[0]),
    roles: $student
);

$router->add(
    'POST',
    '/app/offers/' . $idPattern . '/apply',
    fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->doApply($p[0]),
    roles: $student
);

// GET — Student applications (staff view) - Now includes Admin
$router->add(
    'GET', 
    '/dashboard/applications/' . $idPattern, 
    fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewStudentApplications($p), 
    roles: $staff
);

$router->add(
    'GET', 
    '/dashboard/applications', 
    fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->myApplications(), 
    roles: $student
);

// API — Update application status - Now includes Admin
$router->add(
    'PATCH', 
    '/api/applications/' . $idPattern . '/status', 
    fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->updateStatusAjax($p), 
    roles: $staff
);

// API — Update application status
$router->add('PATCH', '/api/applications/' . $idPattern . '/status', fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->updateStatusAjax($p[0]), roles: [RoleEnum::Pilote->value]);

// ════════════════════════════════════════════════════════════════════════════
// WISHLIST (Student Management)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/app/wishlist',
    fn($p, $pdo, $twig) => $wishlistHandler($pdo, $twig)->index(),
    roles: $student
);

$router->add(
    'GET',
    '/dashboard/wishlist',
    fn($p, $pdo, $twig) => $wishlistHandler($pdo, $twig)->dashboard(),
    roles: $student
);

$router->add(
    'POST',
    '/app/wishlist/toggle/' . $idPattern,
    fn($p, $pdo, $twig) => $wishlistHandler($pdo, $twig)->toggle($p[0]),
    roles: $student
);

// ════════════════════════════════════════════════════════════════════════════
// PILOTS (Admin Management)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/pilotes',
    fn($p, $pdo, $twig) => $pilotHandler($pdo, $twig)->renderList(),
    roles: [RoleEnum::Admin->value]
);

$router->add(
    'GET',
    '/dashboard/pilotes/' . $idPattern,
    fn($p, $pdo, $twig) => $pilotHandler($pdo, $twig)->renderEditForm($p[0]),
    roles: [RoleEnum::Admin->value]
);

$router->add(
    'POST',
    '/dashboard/pilotes/' . $idPattern,
    fn($p, $pdo, $twig) => $pilotHandler($pdo, $twig)->handleUpdate($p[0]),
    roles: [RoleEnum::Admin->value]
);

// ════════════════════════════════════════════════════════════════════════════
// STUDENTS (Admin & Pilot Management)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/etudiants',
    fn($p, $pdo, $twig) => $studentHandler($pdo, $twig)->renderList(),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/etudiants/new',
    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->registerStudent(),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/etudiants/new',
    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->registerStudent(),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/etudiants/' . $idPattern,
    fn($p, $pdo, $twig) => $studentHandler($pdo, $twig)->renderEditForm($p[0]),
    roles: $staff
);

$router->add(
    'POST',
    '/dashboard/etudiants/' . $idPattern,
    fn($p, $pdo, $twig) => $studentHandler($pdo, $twig)->handleUpdate($p[0]),
    roles: $staff
);

$router->add(
    'GET',
    '/dashboard/promotions',
    fn($p, $pdo, $twig) => $promotionHandler($pdo, $twig)->renderIndex(),
    roles: $staff
);

// ════════════════════════════════════════════════════════════════════════════
// CAMPUSES (Admin Management)
// ════════════════════════════════════════════════════════════════════════════
$router->add(
    'GET',
    '/dashboard/campus',
    fn($p, $pdo, $twig) => $campusHandler($pdo, $twig)->index(),
    roles: [RoleEnum::Admin->value]
);

$router->add(
    'GET',
    '/dashboard/campus/edit/' . $idPattern,
    fn($p, $pdo, $twig) => $campusHandler($pdo, $twig)->edit((int) $p[0]),
    roles: [RoleEnum::Admin->value]
);

$router->add(
    'POST',
    '/dashboard/campus/edit/' . $idPattern,
    fn($p, $pdo, $twig) => $campusHandler($pdo, $twig)->edit((int) $p[0]),
    roles: [RoleEnum::Admin->value]
);

// ════════════════════════════════════════════════════════════════════════════
// LEGALS & GDPR (Public & Protected)
// ════════════════════════════════════════════════════════════════════════════

// GET — Legal pages (public)
$router->add('GET', '/legals', fn($p, $pdo, $twig) => $legalsHandler($pdo, $twig)->index());
$router->add('GET', '/mentions-legales', fn($p, $pdo, $twig) => $legalsHandler($pdo, $twig)->index());
$router->add('GET', '/privacy', fn($p, $pdo, $twig) => $legalsHandler($pdo, $twig)->index());

$router->add(
    'POST',
    '/dashboard/campus/delete/' . $idPattern,
    fn($p, $pdo, $twig) => $campusHandler($pdo, $twig)->delete((int) $p[0]),
    roles: $staff
);

// --- 7. DISPATCH ---
$router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
