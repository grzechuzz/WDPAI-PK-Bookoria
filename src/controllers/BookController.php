<?php 

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../services/BookService.php';


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


        $this->render('repository', $data);
    }
}