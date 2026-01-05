<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../repositories/BookRepository.php';


class BookService
{
    private BookRepository $bookRepository;

    public function __construct(BookRepository $bookRepository)
    {
        $this->bookRepository = $bookRepository;
    }

    public function getBooksForPage(int $pageNumber, ?string $search = null)
    {
        $perPage = Config::BOOKS_PER_PAGE;

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
            'search' => $search,
        ];
    }

    public function getDetails(int $id)
    {
        $rows = $this->bookRepository->findById($id);

        if (!$rows) {
            throw new RuntimeException('Książka nie została znaleziona.', DomainError::BOOK_NOT_FOUND);
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
            'branches' => $branches,
        ];
    }

    public function createBookWithAuthorsAndCover(int $roleId, string $title, string $isbn13, ?string $publicationYear, string $authorsRaw, ?string $description, ?array $coverFile)
    {
        if (!Config::isAdmin($roleId)) {
            throw new RuntimeException('Brak uprawnień do dodawania książek.', DomainError::ACCESS_DENIED);
        }

        $title = trim($title);
        if (mb_strlen($title) < 2) {
            throw new RuntimeException('Tytuł jest wymagany (min. 2 znaki).');
        }

        $isbnDigits = preg_replace('/\D+/', '', $isbn13 ?? '');
        if ($isbnDigits === null) {
            $isbnDigits = '';
        }
        if (mb_strlen($isbnDigits) !== Config::ISBN_LENGTH) {
            throw new RuntimeException('ISBN musi mieć ' . Config::ISBN_LENGTH . ' cyfr.', DomainError::INVALID_ISBN);
        }

        $year = null;
        $publicationYear = trim((string)$publicationYear);
        if ($publicationYear !== '') {
            if (!preg_match('/^\d{4}$/', $publicationYear)) {
                throw new RuntimeException('Rok wydania musi być liczbą (YYYY) albo pusty.', DomainError::INVALID_PUBLICATION_YEAR);
            }
            $y = (int)$publicationYear;
            $maxYear = Config::maxPublicationYear();
            if ($y < Config::MIN_PUBLICATION_YEAR || $y > $maxYear) {
                throw new RuntimeException('Rok wydania musi być między ' . Config::MIN_PUBLICATION_YEAR . ' a ' . $maxYear . '.', DomainError::INVALID_PUBLICATION_YEAR);
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

        $description = trim((string)$description);
        if ($description === '') {
            $description = null;
        } elseif (mb_strlen($description) > Config::MAX_DESCRIPTION_LENGTH) {
            throw new RuntimeException('Opis jest za długi (max ' . Config::MAX_DESCRIPTION_LENGTH . ' znaków).');
        }

        if ($this->bookRepository->existsByIsbn13($isbnDigits)) {
            throw new RuntimeException('Książka o tym numerze ISBN już istnieje w katalogu.', DomainError::ISBN_EXISTS);
        }

        $coverUrl = null;
        if ($coverFile && isset($coverFile['tmp_name']) && $coverFile['tmp_name'] !== '') {
            $coverUrl = $this->saveCoverToPublicImages($coverFile);
        }

        $db = $this->bookRepository->getDb();
        $db->beginTransaction();

        try {
            $bookId = $this->bookRepository->insertBook($title, $isbnDigits, $year, $coverUrl, $description);

            foreach ($authors as $name) {
                $authorId = $this->bookRepository->getOrCreateAuthorId($name);
                $this->bookRepository->linkBookAuthor($bookId, $authorId);
            }

            $db->commit();
            return $bookId;

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function saveCoverToPublicImages(array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nie udało się wgrać okładki.', DomainError::FILE_UPLOAD_FAILED);
        }

        if (!isset($file['size']) || (int)$file['size'] > Config::MAX_COVER_SIZE) {
            throw new RuntimeException('Okładka jest za duża (max ' . (Config::MAX_COVER_SIZE / 1_000_000) . 'MB).', DomainError::FILE_TOO_LARGE);
        }

        $tmp = (string)$file['tmp_name'];
        $mime = @mime_content_type($tmp);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!$mime || !isset($allowed[$mime])) {
            throw new RuntimeException('Okładka musi być w formacie JPG, PNG lub WEBP.', DomainError::FILE_INVALID_TYPE);
        }

        $ext = $allowed[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = __DIR__ . '/../../public/images/covers';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Nie można utworzyć katalogu na okładki.', DomainError::FILE_UPLOAD_FAILED
                );
            }
        }

        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Nie udało się zapisać okładki.', DomainError::FILE_UPLOAD_FAILED);
        }

        return '/images/covers/' . $name;
    }
}