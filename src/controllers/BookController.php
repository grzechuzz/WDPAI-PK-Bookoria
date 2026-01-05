<?php 

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../services/BookService.php';


class BookController extends AppController
{
    private BookService $bookService;

    public function __construct()
    {
        $db = Database::connect();
        $repo = new BookRepository($db);
        $this->bookService = new BookService($repo);
    }

 
    public function index()
    {
        Auth::requireLogin();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;

        $data = $this->bookService->getBooksForPage($page, $search);
        $this->render('books/repository', $data);
    }

    public function show()
    {
        Auth::requireLogin();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowy identyfikator książki.';
            $this->redirect('/repository');
            return;
        }

        try {
            $data = $this->bookService->getDetails($id);

            $this->render('books/book', [
                'book' => $data['details'],
                'branches' => $data['branches'],
            ]);

        } catch (RuntimeException $e) {
            if ($e->getCode() === DomainError::BOOK_NOT_FOUND) {
                $_SESSION['flash_error'] = 'Książka nie została znaleziona.';
                $this->redirect('/repository');
                return;
            }
            throw $e;
        }
    }
    
    public function add()
    {
        Auth::requireAdmin();

        if ($this->isGet()) {
            $this->render('books/add-book', [
                'form' => [
                    'title' => '',
                    'authors' => '',
                    'isbn13' => '',
                    'publication_year' => '',
                    'description' => '',
                ],
                'error' => null,
            ]);
            return;
        }

        $title = $_POST['title'] ?? '';
        $authors = $_POST['authors'] ?? '';
        $isbn13 = $_POST['isbn13'] ?? '';
        $publicationYear = $_POST['publication_year'] ?? '';
        $cover = $_FILES['cover'] ?? null;
        $description = $_POST['description'] ?? null;

        try {
            $roleId = Auth::roleId();

            $bookId = $this->bookService->createBookWithAuthorsAndCover(
                $roleId,
                (string)$title,
                (string)$isbn13,
                $publicationYear !== '' ? (string)$publicationYear : null,
                (string)$authors,
                $description,
                is_array($cover) ? $cover : null
            );

            $_SESSION['flash_success'] = 'Książka została dodana do katalogu.';
            unset($_SESSION['flash_error']);
            $this->redirect('/book?id=' . $bookId);

        } catch (RuntimeException $e) {
            $this->render('books/add-book', [
                'error' => $e->getMessage(),
                'form' => [
                    'title' => (string)$title,
                    'authors' => (string)$authors,
                    'isbn13' => (string)$isbn13,
                    'publication_year' => (string)$publicationYear,
                    'description' => (string)($description ?? ''),
                ],
            ]);
        }
    }
}