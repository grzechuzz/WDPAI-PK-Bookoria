<?php

require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../core/DomainError.php';


class BookService {
    private BookRepository $bookRepository;

    public function __construct(BookRepository $bookRepository) {
        $this->bookRepository = $bookRepository;
    }

    public function getBooksForPage(int $pageNumber, ?string $search = null) {
        $perPage = 3;

        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        $offset = ($pageNumber - 1) * $perPage;
        
        $books = $this->bookRepository->findPaginated($perPage, $offset, $search);
        $totalBooks = $this->bookRepository->countBySearch($search);

        $totalPages = (int)ceil($totalBooks / $perPage);

        return [
            'books' => $books,
            'currentPage' => $pageNumber,
            'totalPages' => $totalPages,
            'search' => $search 
        ];
    }

    public function getDetails(int $id) {
        $rows = $this->bookRepository->findById($id);

        if (!$rows) {
            throw new RuntimeException('Book not found', DomainError::BOOK_NOT_FOUND);
        }

        $book = $rows[0];
        $branches = [];

        foreach ($rows as $row) {
            $label = $row['branch_label'] ?? null;
            if (!$label) {
                continue;
            }

            $branchId = (int)($row['branch_id'] ?? 0);
            $branches[] = [
                'branch_id' => $branchId,
                'label' => $label,
                'count' => (int)($row['available_count'] ?? 0),
            ];
        }

        return [
            'details' => $book,
            'branches' => $branches
        ];
    }

    public function createBookWithAuthorsAndCover(
        int $roleId,
        string $title,
        string $isbn13,
        ?string $publicationYear,
        string $authorsRaw,
        ?array $coverFile
    ) {
        if ($roleId !== 2) {
            throw new RuntimeException('Brak uprawnień.');
        }

        $title = trim($title);
        if (mb_strlen($title) < 2) {
            throw new RuntimeException('Tytuł jest wymagany.');
        }

        $isbnDigits = preg_replace('/\D+/', '', $isbn13 ?? '');
        if ($isbnDigits === null) $isbnDigits = '';
        if (mb_strlen($isbnDigits) !== 13) {
            throw new RuntimeException('ISBN musi mieć 13 cyfr.');
        }

        $year = null;
        $publicationYear = trim((string)$publicationYear);
        if ($publicationYear !== '') {
            if (!preg_match('/^\d{4}$/', $publicationYear)) {
                throw new RuntimeException('Rok wydania musi być liczbą (YYYY) albo pusty.');
            }
            $y = (int)$publicationYear;
            $currentYear = (int)date('Y') + 1;
            if ($y < 1400 || $y > $currentYear) {
                throw new RuntimeException('Nieprawidłowy rok wydania.');
            }
            $year = $y;
        }

        $authorsRaw = trim($authorsRaw);
        if ($authorsRaw === '') {
            throw new RuntimeException('Podaj przynajmniej jednego autora.');
        }

        $authors = array_map('trim', preg_split('/,/', $authorsRaw) ?: []);
        $authors = array_values(array_filter($authors, fn($a) => $a !== ''));
        $seen = [];
        $uniq = [];
        foreach ($authors as $a) {
            $k = mb_strtolower($a);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $uniq[] = $a;
        }
        $authors = $uniq;

        if (count($authors) < 1) {
            throw new RuntimeException('Podaj przynajmniej jednego autora.');
        }

        if ($this->bookRepository->existsByIsbn13($isbnDigits)) {
            throw new RuntimeException('Znaleziono istniejącą książkę o tym numerze ISBN.');
        }

        $coverUrl = null;
        if ($coverFile && isset($coverFile['tmp_name']) && $coverFile['tmp_name'] !== '') {
            $coverUrl = $this->saveCoverToPublicImages($coverFile); 
        }

        $db = Database::connect();
        $db->beginTransaction();

        try {
            $bookId = $this->bookRepository->insertBook($title, $isbnDigits, $year, $coverUrl);

            foreach ($authors as $name) {
                $authorId = $this->bookRepository->getOrCreateAuthorId($name);
                $this->bookRepository->linkBookAuthor($bookId, $authorId);
            }

            $db->commit();
            return $bookId;

        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }


    private function saveCoverToPublicImages(array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nie udało się wgrać okładki.');
        }

        if (!isset($file['size']) || (int)$file['size'] > 5_000_000) {
            throw new RuntimeException('Okładka jest za duża (max 5MB).');
        }

        $tmp = (string)$file['tmp_name'];
        $mime = @mime_content_type($tmp);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!$mime || !isset($allowed[$mime])) {
            throw new RuntimeException('Okładka musi być JPG/PNG/WEBP.');
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = __DIR__ . '/../../public/images/covers';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Nie można utworzyć katalogu na okładki.');
            }
        }

        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Nie udało się zapisać okładki.');
        }

        return '/images/covers/' . $name;
    }
}