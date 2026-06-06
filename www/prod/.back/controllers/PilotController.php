<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Repository\PromotionRepository; // Ajout du repository des promotions
use App\Models\UserModel;
use App\Models\RoleEnum;
use PharIo\Manifest\Email;
use App\Util;
use Exception;
use Twig\Environment;

class PilotController extends BaseController
{
    // On ajoute PDO dans le constructeur
    public function __construct(
        private UserRepository $repo,
        protected Environment $twig,
        private \PDO $pdo 
    ) {
        parent::__construct($twig);
        if (Util::getCSRFToken() === null) {
            Util::setCSRFToken(bin2hex(random_bytes(32)));
        }
    }

    public function renderList(): void
    {
        $this->abortIfNotPriv();

        $filters = [
            'name'   => $_GET['name'] ?? null,
            'status' => $_GET['status'] ?? null,
            'page'   => (int) ($_GET['page'] ?? 1),
            'limit'  => 10
        ];

        $pilots = $this->repo->searchPilots($filters);

        echo $this->twig->render('pilots/pilot_list.html.twig', [
            'pilots'     => $pilots,
            'filters'    => $filters,
            'csrf_token' => Util::getCSRFToken(),
            'deleted'    => $_GET['deleted'] ?? null,
            'sidebar_active' => 'pilots'
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau pilote.
     * Route : GET /dashboard/pilotes/new
     */
    public function renderCreateForm(): void
    {
        $this->abortIfNotPriv();

        $allPromotions = $this->fetchAllPromotions();

        echo $this->twig->render('pilots/pilot_create.html.twig', [
            'all_promotions' => $allPromotions,
            'csrf_token'     => Util::getCSRFToken(),
            'error'          => $_SESSION['flash_error'] ?? null,
            'success'        => $_SESSION['flash_success'] ?? null,
            'old'            => $_SESSION['flash_old'] ?? [],
            'sidebar_active' => 'pilots'
        ]);

        // On vide les messages flash après affichage
        unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['flash_old']);
    }

    /**
     * Traite la création d'un nouveau pilote.
     * Route : POST /dashboard/pilotes/new
     * (Protection CSRF assurée en amont par le Router.)
     */
    public function handleStore(): void
    {
        $this->abortIfNotPriv();

        $email     = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $promoId   = $_POST['promotion_id'] ?? null;
        $isActive  = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true;

        // --- Validation ---
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectCreateWithError("Format d'email invalide.", $_POST);
        }
        if ($firstName === '' || $lastName === '') {
            $this->redirectCreateWithError("Le nom et le prénom sont requis.", $_POST);
        }
        if ($this->repo->findByEmail($email)) {
            $this->redirectCreateWithError("Cet email est déjà utilisé.", $_POST);
        }

        try {
            // Mot de passe provisoire généré automatiquement
            $tempPassword = bin2hex(random_bytes(4));

            $user = new UserModel();
            $user->email      = new Email($email);
            $user->password   = password_hash($tempPassword, PASSWORD_ARGON2ID);
            $user->first_name = $firstName;
            $user->last_name  = $lastName;
            $user->role       = RoleEnum::Pilote;
            $user->is_active  = $isActive;

            // push() insère dans `user` PUIS dans `pilot` car role = Pilote
            $newUserId = $this->repo->push($user);

            if (!$newUserId) {
                $this->redirectCreateWithError("Erreur technique lors de la création du pilote.", $_POST);
            }

            // Assignation d'une promotion (optionnelle)
            if (!empty($promoId)) {
                $this->repo->assignPromotionToPilot($newUserId, $promoId);
            }

            $_SESSION['flash_success'] = "Pilote « {$firstName} {$lastName} » créé avec succès. "
                . "Mot de passe provisoire : <strong>{$tempPassword}</strong> (à communiquer au pilote).";

            header("Location: /dashboard/pilotes/new");
            exit;

        } catch (Exception $e) {
            error_log("Pilot creation error: " . $e->getMessage());
            $this->redirectCreateWithError("Erreur : cet email est peut-être déjà utilisé.", $_POST);
        }
    }

    public function renderEditForm(string $id): void
    {
        $this->abortIfNotPriv();
        
        $pilot = $this->repo->findById($id);
        if (!$pilot) {
            $this->abort(404, "Pilote introuvable.");
        }

        // 1. On récupère la promotion actuelle du pilote
        $currentPromo = $this->repo->getPromoByPilote($id);

        // 2. On récupère TOUTES les promotions pour le menu déroulant
        // (On utilise une requête directe simple ici pour éviter de modifier ton PromotionRepository)
        
        $stmt = $this->pdo->query("
    SELECT 
        id_promotion AS id, 
        label_promotion AS label, 
        academic_year_promotion AS academic_year 
    FROM promotion 
    ORDER BY academic_year_promotion DESC
");

        $allPromotions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('pilots/pilot_editor.html.twig', [
            'id'             => $id,
            'pilot'          => $pilot,
            'current_promo'  => $currentPromo,
            'all_promotions' => $allPromotions,
            'csrf_token'     => Util::getCSRFToken(),
            'error'          => $_SESSION['flash_error'] ?? null,
            'success'        => $_GET['success'] ?? null,
            'sidebar_active' => 'pilots'
        ]);
        unset($_SESSION['flash_error']);
    }

    public function handleUpdate(string $id): void
    {
        $this->abortIfNotPriv();
        try {
            $data = $_POST;
            
            // 1. Mise à jour des informations de base (Nom, Prénom, Statut)
            if (!$this->repo->updateUser($id, $data)) {
                throw new Exception("Erreur lors de la mise à jour de l'identité.");
            }

            // 2. Mise à jour du mot de passe (Seulement si le champ a été rempli)
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_ARGON2ID); // Comme dans ton AuthController
                $this->repo->updatePassword($id, $hashedPassword);
            }

            // 3. Assignation d'une nouvelle promotion
            if (!empty($_POST['promotion_id'])) {
                $this->repo->assignPromotionToPilot($id, $_POST['promotion_id']);
            }

            header("Location: /dashboard/pilotes/{$id}?success=1");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Supprime définitivement un compte pilote.
     * Route : POST /dashboard/pilotes/{id}/delete
     * (Protection CSRF assurée en amont par le Router.)
     *
     * La suppression de la ligne `user` est répercutée en cascade
     * sur les tables `pilot` et `promotion_assignment` (ON DELETE CASCADE).
     */
    public function handleDelete(string $id): void
    {
        $this->abortIfNotPriv();

        $pilot = $this->repo->findById($id);
        if (!$pilot) {
            $this->abort(404, "Pilote introuvable.");
        }

        // Sécurité : on s'assure que la cible est bien un pilote
        // (on n'autorise pas la suppression d'un admin ou d'un étudiant via cette route).
        if ($pilot->role !== RoleEnum::Pilote) {
            $this->abort(403, "Cet utilisateur n'est pas un pilote.");
        }

        try {
            $this->repo->delete($id);
            header("Location: /dashboard/pilotes?deleted=1");
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /dashboard/pilotes");
            exit;
        }
    }

    /**
     * Récupère toutes les promotions (pour les menus déroulants).
     */
    private function fetchAllPromotions(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id_promotion AS id,
                label_promotion AS label,
                academic_year_promotion AS academic_year
            FROM promotion
            ORDER BY academic_year_promotion DESC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Réaffiche le formulaire de création avec un message d'erreur
     * et conserve les valeurs déjà saisies.
     */
    private function redirectCreateWithError(string $message, array $old): void
    {
        $_SESSION['flash_error'] = $message;
        $_SESSION['flash_old']   = $old;
        header("Location: /dashboard/pilotes/new");
        exit;
    }
}
