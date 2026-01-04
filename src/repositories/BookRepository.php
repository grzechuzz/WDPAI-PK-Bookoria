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
            SELECT b.id, b.title, b.isbn13, b.cover_url, STRING_AGG(DISTINCT a.name, ', ') as author, COALESCE(SUM(v.available_count), 0) as total_available FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN v_book_availability_by_branch v ON b.id = v.book_id
            WHERE 1=1 {$searchSql}
            GROUP BY b.id, b.title, b.isbn13, b.cover_url
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

    public function findById(int $id) {
        $sql = "
            SELECT b.*, STRING_AGG(DISTINCT a.name, ', ') as author, v.branch_id, v.branch_label, v.available_count
            FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN v_book_availability_by_branch v ON b.id = v.book_id
            WHERE b.id = :id
            GROUP BY b.id, v.branch_id, v.branch_label, v.available_count
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existsByIsbn13(string $isbn13): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM books WHERE isbn13 = :isbn LIMIT 1");
        $stmt->execute(['isbn' => $isbn13]);
        return $stmt->fetchColumn() !== false;
    }

    public function insertBook(string $title, string $isbn13, ?int $year, ?string $coverUrl,?string $description) 
    {
        $stmt = $this->db->prepare("
            INSERT INTO books (title, isbn13, publication_year, cover_url, description)
            VALUES (:title, :isbn13, :year, :cover_url, :description)
            RETURNING id
        ");

        $stmt->execute([
            'title' => $title,
            'isbn13' => $isbn13,
            'year' => $year,
            'cover_url' => $coverUrl,
            'description' => $description,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function getOrCreateAuthorId(string $name): int
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        $stmt = $this->db->prepare("
            INSERT INTO authors (name)
            VALUES (:name)
            ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
            RETURNING id
        ");
        $stmt->execute(['name' => $name]);

        return (int)$stmt->fetchColumn();
    }


    public function linkBookAuthor(int $bookId, int $authorId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO book_authors (book_id, author_id)
            VALUES (:b, :a)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute(['b' => $bookId, 'a' => $authorId]);
    }
}