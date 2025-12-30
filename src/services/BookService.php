<?php

require_once __DIR__ . '/../repositories/UserRepository.php';


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
}