<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use Exception;
use App\Models\CompanyModel;
use App\Models\CompanySiteModel;

class CompanyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAllSectors(): array
    {
        $sql = "SELECT
                    id_business_sector,
                    id_business_sector AS id,
                    name_business_sector,
                    name_business_sector AS name,
                    description_business_sector
                FROM business_sector
                ORDER BY name_business_sector ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBaseSelect(): string
    {
        return "SELECT
                    c.id_company,
                    c.id_company AS id,
                    c.name_company,
                    c.name_company AS name,
                    c.description_company,
                    c.description_company AS description,
                    c.tax_id_company,
                    c.tax_id_company AS siren,
                    c.email_company,
                    c.email_company AS email,
                    c.phone_company,
                    c.phone_company AS phone,
                    c.is_active_company,
                    c.is_active_company AS is_active,
                    c.sector_id_company,
                    c.sector_id_company AS sector_id,
                    c.created_at_company,
                    s.name_business_sector,
                    s.name_business_sector AS sector_name
                FROM company c
                LEFT JOIN business_sector s
                    ON c.sector_id_company = s.id_business_sector";
    }

    private function isValidId(int|string|null $id): bool
    {
        if ($id === null || $id === '') {
            return false;
        }

        return is_numeric((string) $id) && (int) $id > 0;
    }

    public function findAllActive(): array
    {
        $sql = $this->getBaseSelect() . " WHERE c.is_active_company = 1 ORDER BY c.name_company ASC";
        $stmt = $this->pdo->query($sql);

        return array_map(
            fn(array $row) => CompanyModel::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getCompaniesWithActiveOffers(): array
    {
        $sql = "SELECT DISTINCT
                    c.id_company,
                    c.id_company AS id,
                    c.name_company,
                    c.name_company AS name
                FROM company c
                INNER JOIN company_site cs
                    ON cs.company_id_company_site = c.id_company
                INNER JOIN internship_offer io
                    ON io.company_site_id_internship_offer = cs.id_company_site
                WHERE c.is_active_company = 1
                  AND io.is_active_internship_offer = 1
                ORDER BY c.name_company ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int|string $id): ?CompanyModel
    {
        if (!$this->isValidId($id)) {
            return null;
        }

        $sql = $this->getBaseSelect() . " WHERE c.id_company = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $company = CompanyModel::fromArray($row);
        $company->sites = $this->findSitesByCompany((int) $id);

        return $company;
    }

    public function getBySiren(string $siren): ?CompanyModel
    {
        $sql = $this->getBaseSelect() . " WHERE c.tax_id_company = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$siren]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $company = CompanyModel::fromArray($row);
        if ($company->id_company !== null) {
            $company->sites = $this->findSitesByCompany($company->id_company);
        }

        return $company;
    }

    public function getByName(string $name): array
    {
        $sql = $this->getBaseSelect() . " WHERE c.name_company LIKE ? ORDER BY c.name_company ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['%' . $name . '%']);

        return array_map(
            fn(array $row) => CompanyModel::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function push(CompanyModel $company): bool
    {
        try {
            $this->pdo->beginTransaction();

            if (empty($company->id_company)) {
                $sql = "INSERT INTO company (
                            name_company,
                            description_company,
                            email_company,
                            phone_company,
                            tax_id_company,
                            is_active_company,
                            sector_id_company
                        ) VALUES (
                            :name_company,
                            :description_company,
                            :email_company,
                            :phone_company,
                            :tax_id_company,
                            :is_active_company,
                            :sector_id_company
                        )";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'name_company'        => $company->name_company,
                    'description_company' => $company->description_company,
                    'email_company'       => $company->email_company?->asString(),
                    'phone_company'       => $company->phone_company,
                    'tax_id_company'      => $company->tax_id_company,
                    'is_active_company'   => (int) $company->is_active_company,
                    'sector_id_company'   => $company->sector_id_company,
                ]);

                $company->id_company = (int) $this->pdo->lastInsertId();
            } else {
                $sql = "UPDATE company
                        SET
                            name_company = :name_company,
                            description_company = :description_company,
                            email_company = :email_company,
                            phone_company = :phone_company,
                            tax_id_company = :tax_id_company,
                            is_active_company = :is_active_company,
                            sector_id_company = :sector_id_company
                        WHERE id_company = :id_company";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'id_company'          => $company->id_company,
                    'name_company'        => $company->name_company,
                    'description_company' => $company->description_company,
                    'email_company'       => $company->email_company?->asString(),
                    'phone_company'       => $company->phone_company,
                    'tax_id_company'      => $company->tax_id_company,
                    'is_active_company'   => (int) $company->is_active_company,
                    'sector_id_company'   => $company->sector_id_company,
                ]);
            }

            if (!empty($company->sites) && $company->id_company !== null) {
                foreach ($company->sites as $site) {
                    $site->company_id_company_site = $company->id_company;
                    $this->pushSite($site);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function search(array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 10);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT
                    c.id_company,
                    c.id_company AS id,
                    c.name_company,
                    c.name_company AS name,
                    c.description_company,
                    c.description_company AS description,
                    c.tax_id_company,
                    c.tax_id_company AS siren,
                    c.email_company,
                    c.email_company AS email,
                    c.phone_company,
                    c.phone_company AS phone,
                    c.is_active_company,
                    c.is_active_company AS is_active,
                    c.sector_id_company,
                    c.sector_id_company AS sector_id,
                    s.name_business_sector,
                    s.name_business_sector AS sector_name,
                    (SELECT GROUP_CONCAT(DISTINCT cs.city_company_site ORDER BY cs.city_company_site SEPARATOR ', ')
                        FROM company_site cs
                        WHERE cs.company_id_company_site = c.id_company
                          AND cs.city_company_site IS NOT NULL
                          AND cs.city_company_site <> '') AS location
                FROM company c
                LEFT JOIN business_sector s
                    ON c.sector_id_company = s.id_business_sector
                WHERE 1=1";

        $params = [];

        $nameFilter = $filters['name_company'] ?? ($filters['name'] ?? null);
        if (!empty($nameFilter)) {
            $sql .= " AND (c.name_company LIKE :name_search OR c.tax_id_company LIKE :tax_search)";
            $params['name_search'] = '%' . $nameFilter . '%';
            $params['tax_search'] = '%' . $nameFilter . '%';
        }

        $sectorFilter = $filters['sector_id_company'] ?? ($filters['sector_id'] ?? null);
        if (!empty($sectorFilter)) {
            $sql .= " AND c.sector_id_company = :sector_id";
            $params['sector_id'] = (int) $sectorFilter;
        }

        $statusFilter = $filters['is_active_company'] ?? ($filters['status'] ?? null);
        if ($statusFilter !== null && $statusFilter !== '') {
            if ($statusFilter === 'active') {
                $params['is_active'] = 1;
            } elseif ($statusFilter === 'inactive') {
                $params['is_active'] = 0;
            } else {
                $params['is_active'] = (int) $statusFilter;
            }

            $sql .= " AND c.is_active_company = :is_active";
        }

        $sql .= " ORDER BY c.name_company ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $val, $type);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findSitesByCompany(int|string $companyId): array
    {
        if (!$this->isValidId($companyId)) {
            return [];
        }

        $sql = "SELECT
                    id_company_site,
                    id_company_site AS id,
                    address_company_site,
                    address_company_site AS address,
                    city_company_site,
                    city_company_site AS city,
                    company_id_company_site,
                    company_id_company_site AS company_id
                FROM company_site
                WHERE company_id_company_site = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $companyId]);

        return array_map(
            fn(array $row) => CompanySiteModel::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function pushSite(CompanySiteModel $site): bool
    {
        if (empty($site->id_company_site)) {
            $sql = "INSERT INTO company_site (
                        address_company_site,
                        city_company_site,
                        company_id_company_site
                    ) VALUES (
                        :address_company_site,
                        :city_company_site,
                        :company_id_company_site
                    )";

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                'address_company_site'    => $site->address_company_site,
                'city_company_site'       => $site->city_company_site,
                'company_id_company_site' => $site->company_id_company_site,
            ]);

            if ($success) {
                $site->id_company_site = (int) $this->pdo->lastInsertId();
            }

            return $success;
        }

        $sql = "UPDATE company_site
                SET
                    address_company_site = :address_company_site,
                    city_company_site = :city_company_site,
                    company_id_company_site = :company_id_company_site
                WHERE id_company_site = :id_company_site";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id_company_site'         => $site->id_company_site,
            'address_company_site'    => $site->address_company_site,
            'city_company_site'       => $site->city_company_site,
            'company_id_company_site' => $site->company_id_company_site,
        ]);
    }

    public function deleteById(int|string $id): bool
    {
        if (!$this->isValidId($id)) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $this->deleteSitesByCompany((int) $id);

            $stmt = $this->pdo->prepare("DELETE FROM company WHERE id_company = ?");
            $res = $stmt->execute([(int) $id]);

            $this->pdo->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function deleteSitesByCompany(int|string $companyId): bool
    {
        if (!$this->isValidId($companyId)) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM company_site WHERE company_id_company_site = ?");
        return $stmt->execute([(int) $companyId]);
    }


    
}
