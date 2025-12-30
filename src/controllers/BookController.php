<?php 

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../services/BookService.php';
require_once __DIR__ . '/../core/DomainError.php';


class BookController extends AppController {

    private BookService $bookService;

    public function __construct() {
        $db = Database::connect();
        $repo = new BookRepository($db);
        $this->bookService = new BookService($repo);
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;


        $data = $this->bookService->getBooksForPage($page, $search);


        $this->render('books/repository', $data);
    }

    public function show() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            http_response_code(400);
            $this->redirect('/books/repository');
            return;
        }

        try {
            $data = $this->bookService->getDetails($id);

            return $this->render('books/book', [
                'book' => $data['details'],
                'branches' => $data['branches'],
            ]);

        } catch (Exception $e) {
            if ($e->getCode() === DomainError::BOOK_NOT_FOUND) {
                http_response_code(404);
                $this->redirect('/books/repository');
                return;
            }

            http_response_code(500);
            throw $e;
        }
    }

}