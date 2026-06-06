<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\BuisnessReviewModel;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new business review.
     * Composite primary key:
     * (pilot_id_business_review, company_id_business_review)
     */
    public function push(BuisnessReviewModel $review): bool
    {
        $sql = "INSERT INTO business_review (
                    pilot_id_business_review,
                    company_id_business_review,
                    rating_business_review,
                    comment_business_review,
                    reviewed_at_business_review
                ) VALUES (
                    :pilot_id,
                    :company_id,
                    :rating,
                    :comment,
                    :reviewed_at
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':pilot_id'    => $review->pilot_id_business_review,
            ':company_id'  => $review->company_id_business_review,
            ':rating'      => $review->rating_business_review,
            ':comment'     => $review->comment_business_review,
            ':reviewed_at' => $review->reviewed_at_business_review->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Finds all reviews for a specific company.
     */
    public function findByBusinessId(int|string $companyId): array
    {
        $sql = "SELECT
                    pilot_id_business_review,
                    pilot_id_business_review AS reviewer_id,
                    company_id_business_review,
                    company_id_business_review AS business_id,
                    rating_business_review,
                    rating_business_review AS rating,
                    comment_business_review,
                    comment_business_review AS comment,
                    reviewed_at_business_review,
                    reviewed_at_business_review AS review_date
                FROM business_review
                WHERE company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $companyId]);

        $reviews = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reviews[] = BuisnessReviewModel::fromArray($row);
        }

        return $reviews;
    }

    /**
     * Deletes a specific review based on the composite primary key.
     */
    public function delete(int|string $pilotId, int|string $companyId): bool
    {
        $sql = "DELETE FROM business_review
                WHERE pilot_id_business_review = ?
                  AND company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int) $pilotId, (int) $companyId]);
    }

    /**
     * Finds all reviews for a company, enriched with the reviewer's name and
     * role (admin / pilote). Returns plain associative rows ready for Twig.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCompanyDetailed(int|string $companyId): array
    {
        $sql = "SELECT
                    br.pilot_id_business_review        AS reviewer_id,
                    br.company_id_business_review      AS company_id,
                    br.rating_business_review          AS rating,
                    br.comment_business_review         AS comment,
                    br.reviewed_at_business_review     AS review_date,
                    u.first_name_user                  AS first_name,
                    u.last_name_user                   AS last_name,
                    CASE
                        WHEN a.id_administrator IS NOT NULL THEN 'admin'
                        WHEN p.id_pilot         IS NOT NULL THEN 'pilote'
                        ELSE 'user'
                    END                                AS reviewer_role
                FROM business_review br
                JOIN user u
                    ON u.id_user = br.pilot_id_business_review
                LEFT JOIN administrator a
                    ON a.id_administrator = u.id_user
                LEFT JOIN pilot p
                    ON p.id_pilot = u.id_user
                WHERE br.company_id_business_review = ?
                ORDER BY br.reviewed_at_business_review DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $companyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns aggregate stats for a company: number of reviews and average.
     *
     * @return array{count: int, average: float}
     */
    public function getStatsForCompany(int|string $companyId): array
    {
        $sql = "SELECT
                    COUNT(*)                    AS cnt,
                    AVG(rating_business_review) AS avg_rating
                FROM business_review
                WHERE company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'count'   => (int) ($row['cnt'] ?? 0),
            'average' => $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 1) : 0.0,
        ];
    }

    /**
     * Finds a single review by its composite key, or null if absent.
     * Used to know whether the current user has already rated a company.
     */
    public function findOne(int|string $reviewerId, int|string $companyId): ?BuisnessReviewModel
    {
        $sql = "SELECT
                    pilot_id_business_review,
                    company_id_business_review,
                    rating_business_review,
                    comment_business_review,
                    reviewed_at_business_review
                FROM business_review
                WHERE pilot_id_business_review = ?
                  AND company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $reviewerId, (int) $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? BuisnessReviewModel::fromArray($row) : null;
    }

    /**
     * Convenience helper: creates the review if it does not exist for this
     * (reviewer, company) pair, otherwise updates the existing one.
     */
    public function save(BuisnessReviewModel $review): bool
    {
        $existing = $this->findOne(
            $review->pilot_id_business_review,
            $review->company_id_business_review
        );

        return $existing === null ? $this->push($review) : $this->update($review);
    }

    /**
     * Updates an existing review's rating and comment.
     */
    public function update(BuisnessReviewModel $review): bool
    {
        $sql = "UPDATE business_review
                SET
                    rating_business_review = :rating,
                    comment_business_review = :comment,
                    reviewed_at_business_review = :reviewed_at
                WHERE pilot_id_business_review = :pilot_id
                  AND company_id_business_review = :company_id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':rating'      => $review->rating_business_review,
            ':comment'     => $review->comment_business_review,
            ':reviewed_at' => $review->reviewed_at_business_review->format('Y-m-d H:i:s'),
            ':pilot_id'    => $review->pilot_id_business_review,
            ':company_id'  => $review->company_id_business_review
        ]);
    }
}
