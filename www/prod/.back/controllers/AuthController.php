<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Models\UserModel;
use App\Models\RoleEnum;
use PharIo\Manifest\Email;
use PDO;
use Twig\Environment;
use App\Util;

class AuthController extends BaseController
{
    private UserRepository $userRepository;
    private PDO $pdo;

    public function __construct(UserRepository $userRepository, Environment $twig, PDO $pdo)
    {
        parent::__construct($twig);
        $this->userRepository = $userRepository;
        $this->pdo = $pdo;
    }


    public function register(): void
    {
        // 1. Check if the user is even allowed to be here
        $currentRole = Util::getRole()?->value; // 'admin' | 'pilote' | 'student' | null
        if ($currentRole !== 'admin' && $currentRole !== 'pilote') {
            $this->abort(403, "Accès refusé. Vous n'avez pas les droits requis.");
        }

        $error = null;
        $success = null;
        $old = $_POST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "Invalid CSRF token.");
            }

            $emailRaw = trim($_POST['email'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $promoId = $_POST['promotion_id'] ?? null;

            // 2. Role Enforcement
            $requestedRole = $_POST['role'] ?? 'student';

            // Safety check: Pilots can ONLY create students. 
            // If a Pilot tries to send role=admin via Postman/Inspect Element, force it back to student.
            if ($currentRole === 'pilote' && $requestedRole !== 'student') {
                $requestedRole = 'student';
            }

            // Validation...
            if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                $error = "Format d'email invalide.";
            } elseif (empty($firstName) || empty($lastName)) {
                $error = "Le nom et le prénom sont requis.";
            } elseif (empty($promoId)) {
                $error = "La promotion est obligatoire.";
            } elseif ($this->userRepository->findByEmail($emailRaw)) {
                $error = "Cet email est déjà utilisé.";
            } else {
                try {
                    $user = new UserModel();
                    $user->email = new Email($emailRaw);
                    $user->first_name = $firstName;
                    $user->last_name = $lastName;
                    // Use the null coalescing operator to provide a default (true/1)
                    $user->is_active_user = $row['is_active'] ?? true;

                    // Map string role to Enum safely
                    $user->role = RoleEnum::tryFrom($requestedRole) ?? RoleEnum::Student;
                    $user->is_active = true;
                    $user->created_at = date('Y-m-d H:i:s');

                    // Generate temp password
                    $tempPassword = bin2hex(random_bytes(4));
                    $user->password = password_hash($tempPassword, PASSWORD_ARGON2ID);

                    $newUserId = $this->userRepository->push($user);

                    // 3. Link Student to Promotion
                    if ($user->role === RoleEnum::Student) {

                        $this->userRepository->makeStudent($newUserId, $promoId, 'searching');
                    }
                    $success = "Compte créé avec succès ! Mot de passe provisoire : <strong>$tempPassword</strong>";
                    $old = [];

                } catch (\Throwable $e) {
                    error_log("Registration Error: " . $e->getMessage());
                    $error = "Erreur technique lors de la création.";
                }
            }
        }

        echo $this->twig->render('auth/register_student.html.twig', [
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'csrf_token' => Util::getCSRFToken(),
            'user_role' => $currentRole // Pass this to show/hide UI elements if needed
        ]);
    }


    public function registerStudent(): void
    {
        $currentRole = Util::getRole()?->value; // 'admin' | 'pilote' | 'student' | null
        $currentUserId = Util::getUserId();

        // 1. Authorization: Only Admin and Pilote can access this page
        if ($currentRole !== 'admin' && $currentRole !== 'pilote') {
            $this->abort(403, "Accès refusé.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $currentUserId = Util::getUserId();
            $currentPromo = $this->userRepository->getPromoByPilote($currentUserId);

            echo $this->twig->render('auth/register_student.html.twig', [
                'current_promo' => $currentPromo,
                'csrf_token' => Util::getCSRFToken(),
                'user_role' => Util::getRole()?->value, // Ensure this matches 'admin' or 'pilote'
                'error' => null,
                'success' => null,
                'old' => [] // <--- CRITICAL: Must be an empty array
            ]);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "CSRF token invalid.");
            }

            // 2. Data Extraction
            $email = trim($_POST['email'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $promoId = $_POST['promotion_id'] ?? null;

            // 3. Role Logic
            // Admin picks from POST, Pilote is forced to 'student'
            $requestedRoleStr = $_POST['role'] ?? 'student';
            if ($currentRole === 'pilote') {
                $requestedRoleStr = 'student';
            }

            // Map string to Enum (assuming RoleEnum has 'pilote' and 'student' cases)
            $roleEnum = RoleEnum::tryFrom($requestedRoleStr) ?? RoleEnum::Student;

            // 4. Validation
            if (empty($email) || empty($firstName) || empty($lastName)) {
                $this->renderWithError("Informations d'identité manquantes.");
                return;
            }

            // Students MUST have a promotion selected
            if ($roleEnum === RoleEnum::Student && empty($promoId)) {
                $this->renderWithError("Une promotion est requise pour inscrire un étudiant.");
                return;
            }

            try {
                // 5. User Creation
                $tempPassword = bin2hex(random_bytes(4));

                $user = new UserModel();
                $user->email = new Email($email);
                $user->password = password_hash($tempPassword, PASSWORD_ARGON2ID);
                $user->first_name = $firstName;
                $user->last_name = $lastName;
                $user->role = $roleEnum;
                $user->is_active = true;

                $newUserId = $this->userRepository->push($user);

                if ($newUserId) {
                    // 6. Role-Specific Logic
                    if ($roleEnum === RoleEnum::Student) {
                        // Link user to student table and promo
                        $this->userRepository->makeStudent($newUserId, (int) $promoId, 'searching');
                    }
                    // Note: If RoleEnum::Pilote, no extra 'make' method was provided, 
                    // but you can add $this->userRepository->makePilote(...) here if needed.

                    $_SESSION['temp_password_display'] = $tempPassword;
                    $_SESSION['flash_success'] = "Le compte <strong>$requestedRoleStr</strong> pour $firstName $lastName a été créé.";

                    // 7. Contextual Redirect
                    $redirectPath = ($roleEnum === RoleEnum::Student) ? "/dashboard/etudiants" : "/dashboard/equipe";
                    header("Location: $redirectPath");
                    exit;
                }
            } catch (\Exception $e) {
                error_log("Registration Error: " . $e->getMessage());
                $this->renderWithError("Erreur : cet email est peut-être déjà utilisé.");
                return;
            }
        }
    }

    /**
     * Helper to re-render the form with an error message without crashing Twig
     */
    private function renderWithError(string $message): void
    {
        echo $this->twig->render('auth/register_student.html.twig', [
            'error' => $message,
            'success' => null,
            'user_role' => Util::getRole()?->value,
            'csrf_token' => Util::getCSRFToken(),
            'current_promo' => $this->userRepository->getPromoByPilote(Util::getUserId()),
            'old' => []
        ]);
    }

    public function login(): void
    {
        $error = null;
        $lastEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lastEmail = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = $this->userRepository->findByEmail($lastEmail);

            if ($user && password_verify($password, $user->password)) {
                session_regenerate_id(true);

                $role = $user->role instanceof RoleEnum ? $user->role : RoleEnum::tryFrom((string) $user->role);

                Util::setCSRFToken(bin2hex(random_bytes(32)));
                Util::setUserId((string) $user->id);
                Util::setRole($role);
                Util::setUserData([
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email->asString(),
                    'role' => $role->value,
                ]);

                $this->handleRoleRedirection($role);
                return;
            } else {
                $error = "Identifiants invalides.";
            }
        }

        echo $this->twig->render('auth/login.html.twig', [
            'error' => $error,
            'last_email' => $lastEmail,
            'csrf_token' => Util::getCSRFToken(),
        ]);
    }

    public function profile(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $user = $this->userRepository->findById(Util::getUserId());
        if (!$user) {
            $this->logout();
            return;
        }

        echo $this->twig->render('auth/profile.html.twig', [
            'user' => $user,
            'csrf_token' => Util::getCSRFToken(),
        ]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        session_start();
        Util::setCSRFToken(bin2hex(random_bytes(32)));

        header('Location: /login');
        exit;
    }

    private function handleRoleRedirection(RoleEnum $role): void
    {
        $path = match ($role) {
            RoleEnum::Admin => '/dashboard/offers',
            RoleEnum::Pilote => '/dashboard/offers',
            RoleEnum::Student => '/dashboard/offers',
            default => '/',
        };

        header("Location: $path");
        exit;
    }
}
