<?php
// .back/repository/ApplicationRepository.php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\ApplicationModel;

class ApplicationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Centralisation de la sauvegarde (Create ou Update)
     */
    public function push(ApplicationModel $app): bool
    {
        if (empty($app->id_application)) {
            $sql = "INSERT INTO application (
                        student_id_application,
                        offer_id_application,
                        cv_path_application,
                        cover_letter_path_application,
                        status_application,
                        applied_at_application
                    ) VALUES (
                        :student_id_application,
                        :offer_id_application,
                        :cv_path_application,
                        :cover_letter_path_application,
                        :status_application,
                        NOW()
                    )";
        } else {
            $sql = "UPDATE application
                    SET
                        student_id_application = :student_id_application,
                        offer_id_application = :offer_id_application,
                        cv_path_application = :cv_path_application,
                        cover_letter_path_application = :cover_letter_path_application,
                        status_application = :status_application
                    WHERE id_application = :id_application";
        }

        $stmt = $this->pdo->prepare($sql);

        $params = [
            ':student_id_application' => $app->student_id_application,
            ':offer_id_application' => $app->offer_id_application,
            ':cv_path_application' => $app->cv_path_application,
            ':cover_letter_path_application' => $app->cover_letter_path_application,
            ':status_application' => $app->status_application,
        ];

        if (!empty($app->id_application)) {
            $params[':id_application'] = $app->id_application;
        }

        $success = $stmt->execute($params);

        if ($success && empty($app->id_application)) {
            $app->id_application = (int) $this->pdo->lastInsertId();
        }

        return $success;
    }

    public function isOwner(int|string $appId, int|string $userId): bool
    {
        $app = $this->findById((int) $appId);
        return $app !== null && $app->student_id_application === (int) $userId;
    }

    public function findById(int|string $id): ?ApplicationModel
    {
        $sql = "SELECT
                    id_application,
                    student_id_application,
                    offer_id_application,
                    cv_path_application,
                    cover_letter_path_application,
                    status_application,
                    applied_at_application
                FROM application
                WHERE id_application = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ApplicationModel::fromArray($row) : null;
    }

    public function findByStudent(int|string $studentId): array
    {
        $sql = "SELECT
                    a.id_application,
                    a.id_application              AS id,
                    a.student_id_application,
                    a.offer_id_application,
                    a.cv_path_application,
                    a.cover_letter_path_application,
                    a.status_application,
                    a.applied_at_application,
                    o.id_internship_offer,
                    o.title_internship_offer,
                    c.name_company
                FROM application a
                INNER JOIN internship_offer o
                    ON a.offer_id_application = o.id_internship_offer
                INNER JOIN company_site cs
                    ON o.company_site_id_internship_offer = cs.id_company_site
                INNER JOIN company c
                    ON cs.company_id_company_site = c.id_company
                WHERE a.student_id_application = :student_id
                ORDER BY a.applied_at_application DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentProgress(int $studentId): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN status_application = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status_application = 'accepted' THEN 1 ELSE 0 END) AS accepted_count,
                    SUM(CASE WHEN status_application = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM application
                WHERE student_id_application = :student_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pending' => (int) ($row['pending_count'] ?? 0),
            'accepted' => (int) ($row['accepted_count'] ?? 0),
            'rejected' => (int) ($row['rejected_count'] ?? 0),
        ];
    }

    public function findStudentApplicationsOverview(int $studentId, int $limit = 5): array
    {
        $sql = "SELECT
                    a.id_application,
                    a.status_application,
                    a.applied_at_application,
                    o.id_internship_offer,
                    o.title_internship_offer,
                    c.name_company
                FROM application a
                INNER JOIN internship_offer o
                    ON a.offer_id_application = o.id_internship_offer
                INNER JOIN company_site s
                    ON o.company_site_id_internship_offer = s.id_company_site
                INNER JOIN company c
                    ON s.company_id_company_site = c.id_company
                WHERE a.student_id_application = :student_id
                ORDER BY a.applied_at_application DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste détaillée des candidatures d'un étudiant (offre + entreprise jointes).
     * Utilisée par la vue Admin/Pilote des candidatures d'un étudiant (SFx21 staff).
     */
    public function findByStudentDetailed(int|string $studentId): array
    {
        $sql = "SELECT
                    a.id_application,
                    a.id_application              AS id,
                    a.student_id_application,
                    a.offer_id_application,
                    a.status_application,
                    a.applied_at_application,
                    o.id_internship_offer,
                    o.title_internship_offer,
                    c.name_company
                FROM application a
                INNER JOIN internship_offer o
                    ON a.offer_id_application = o.id_internship_offer
                INNER JOIN company_site cs
                    ON o.company_site_id_internship_offer = cs.id_company_site
                INNER JOIN company c
                    ON cs.company_id_company_site = c.id_company
                WHERE a.student_id_application = :student_id
                ORDER BY a.applied_at_application DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':student_id', (int) $studentId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * SFx22 — Toutes les candidatures des étudiants rattachés à la dernière
     * promotion assignée au pilote, avec l'identité de l'étudiant.
     */
    public function findByPilot(int|string $pilotId): array
    {
        $sql = "SELECT
                    a.id_application,
                    a.id_application              AS id,
                    a.status_application,
                    a.applied_at_application,
                    o.id_internship_offer,
                    o.title_internship_offer,
                    c.name_company,
                    u.id_user                     AS student_id,
                    u.first_name_user             AS student_first_name,
                    u.last_name_user              AS student_last_name,
                    u.email_user                  AS student_email
                FROM application a
                INNER JOIN internship_offer o
                    ON a.offer_id_application = o.id_internship_offer
                INNER JOIN company_site cs
                    ON o.company_site_id_internship_offer = cs.id_company_site
                INNER JOIN company c
                    ON cs.company_id_company_site = c.id_company
                INNER JOIN user u
                    ON a.student_id_application = u.id_user
                INNER JOIN student_enrollment se
                    ON u.id_user = se.student_id_student_enrollment
                INNER JOIN promotion_assignment pa
                    ON se.promotion_id_student_enrollment = pa.promotion_assignment_id
                WHERE pa.pilot_assignment_id = :pilot_id
                  AND pa.assigned_at = (
                      SELECT MAX(pa2.assigned_at)
                      FROM promotion_assignment pa2
                      WHERE pa2.pilot_assignment_id = :pilot_id_sub
                  )
                ORDER BY a.applied_at_application DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pilot_id', (int) $pilotId, PDO::PARAM_INT);
        $stmt->bindValue(':pilot_id_sub', (int) $pilotId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int|string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM application WHERE id_application = ?");
        return $stmt->execute([(int) $id]);


    }

    /**
     * Return how many applications have been submitted for a given offer.
     */
    public function countByOffer(int $offerId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
         FROM application
         WHERE offer_id_application = :offer_id'
        );
        $stmt->execute(['offer_id' => $offerId]);

        // Store the result in a variable so it isn't lost
        $count = (int) $stmt->fetchColumn();

        error_log("countByOffer executed for offer_id: " . $offerId);
        error_log("countByOffer returned: " . $count);

        return $count;
    }
}
