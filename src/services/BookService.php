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
}