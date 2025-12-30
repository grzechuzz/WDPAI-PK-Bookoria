<?php 

require_once __DIR__ . '/Repository.php';


class BookRepository extends Repository {
    
    public function findPaginated(int $limit, int $offset, ?string $searchPhrase) {
        $searchSql = "";
        $params = [
            'limit' => $limit, 
            'offset' => $offset
        ];

        if ($searchPhrase) {
            $searchSql = "AND (
                LOWER(b.title) LIKE :search OR LOWER(a.name) LIKE :search OR b.isbn13 LIKE :search
            )";

            $params['search'] = '%' . mb_strtolower($searchPhrase) . '%';
        }

        $sql = "
            SELECT b.id, b.title, b.isbn13, b.cover_url, STRING_AGG(a.name, ', ') as author, COALESCE(SUM(v.available_count), 0) as total_available FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN v_book_availability_by_branch v ON b.id = v.book_id
            WHERE 1=1 {$searchSql}  
            GROUP BY b.id, b.title
            ORDER BY b.title ASC, b.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function countBySearch(?string $searchPhrase = null) {
        $searchSql = "";
        $params = [];

        if ($searchPhrase) {
            $searchSql = "AND (
                LOWER(b.title) LIKE :search OR LOWER(a.name) LIKE :search OR b.isbn13 LIKE :search
            )";
            $params[':search'] = '%' . mb_strtolower($searchPhrase) . '%';
        }

        $sql = "
            SELECT COUNT(DISTINCT b.id) FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            WHERE 1=1 {$searchSql}
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}