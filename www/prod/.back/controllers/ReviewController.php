<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BuisnessReviewModel;
use App\Models\RoleEnum;
use App\Repository\ReviewRepository;
use App\Util;
use DateTime;
use Twig\Environment;

/**
 * Handles company reviews (notes/avis) posted by staff members.
 *
 * Both administrators and pilots are allowed to rate a company. Each staff
 * member can leave at most one review per company (composite primary key on
 * the business_review table); submitting again updates the existing review.
 */
class ReviewController extends BaseController
{
    public function __construct(
        private ReviewRepository $repo,
        Environment $twig
    ) {
        parent::__construct($twig);
    }

    /**
     * POST /dashboard/companies/{id}/reviews
     * Creates or updates the current user's review for a company.
     */
    public function store(int|string $companyId): void
    {
        $companyId  = (int) $companyId;
        $reviewerId = Util::getUserId();

        if ($reviewerId === null) {
            $this->redirect('/login');
        }

        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        // Validation: rating must be between 1 and 5 (DB CHECK constraint).
        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = "La note doit être comprise entre 1 et 5 étoiles.";
            $this->redirect("/dashboard/companies/{$companyId}");
        }

        if (mb_strlen($comment) > 2000) {
            $comment = mb_substr($comment, 0, 2000);
        }

        $review = new BuisnessReviewModel();
        $review->pilot_id_business_review     = (int) $reviewerId; // = reviewer id
        $review->company_id_business_review   = $companyId;
        $review->rating_business_review       = $rating;
        $review->comment_business_review      = $comment;
        $review->reviewed_at_business_review  = new DateTime();

        if ($this->repo->save($review)) {
            $_SESSION['flash_message'] = "Votre évaluation a bien été enregistrée.";
            $_SESSION['flash_type']    = 'success';
        } else {
            $_SESSION['flash_error'] = "Impossible d'enregistrer l'évaluation. Réessayez.";
        }

        $this->redirect("/dashboard/companies/{$companyId}");
    }

    /**
     * POST /dashboard/companies/{id}/reviews/delete
     * Deletes a review. A user can always delete their own review; an
     * administrator can additionally delete any reviewer's review by passing
     * a `reviewer_id` field.
     */
    public function delete(int|string $companyId): void
    {
        $companyId      = (int) $companyId;
        $currentUserId  = Util::getUserId();

        if ($currentUserId === null) {
            $this->redirect('/login');
        }

        $targetReviewerId = (int) $currentUserId;

        // Admins may delete someone else's review.
        if (!empty($_POST['reviewer_id'])) {
            $requested = (int) $_POST['reviewer_id'];
            if ($requested !== (int) $currentUserId) {
                if (Util::getRole() !== RoleEnum::Admin) {
                    $_SESSION['flash_error'] = "Seul un administrateur peut supprimer l'avis d'un autre membre.";
                    $this->redirect("/dashboard/companies/{$companyId}");
                }
                $targetReviewerId = $requested;
            }
        }

        if ($this->repo->delete($targetReviewerId, $companyId)) {
            $_SESSION['flash_message'] = "L'évaluation a été supprimée.";
            $_SESSION['flash_type']    = 'success';
        } else {
            $_SESSION['flash_error'] = "Impossible de supprimer cette évaluation.";
        }

        $this->redirect("/dashboard/companies/{$companyId}");
    }
}
