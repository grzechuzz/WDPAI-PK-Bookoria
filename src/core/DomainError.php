<?php 


final class DomainError 
{
    public const BAD_EMAIL = 1001;
    public const BAD_PASSWORD = 1002;
    public const EMAIL_TAKEN = 1003;
    public const INVALID_LOGIN = 1004;
    public const ROLE_NOT_FOUND = 1005;
    public const PASSWORD_MISMATCH = 1006;

    public const BOOK_NOT_FOUND = 1007;
    public const ISBN_EXISTS = 1008;
    public const INVALID_ISBN = 1009;
    public const INVALID_PUBLICATION_YEAR = 1010;

    public const LOAN_NOT_FOUND = 1021;
    public const LOAN_RENEW_NOT_ALLOWED = 1022;
    public const LOAN_LIMIT_REACHED = 1023;
    public const LOAN_ALREADY_RETURNED = 1024;
    public const LOAN_CREATE_FAILED = 1025;

    public const RESERVATION_NOT_FOUND = 1031;
    public const RESERVATION_CANCEL_NOT_ALLOWED = 1032;
    public const RESERVATION_CREATE_NOT_ALLOWED = 1033;
    public const RESERVATION_NOT_READY = 1034;
    public const RESERVATION_ALREADY_FULFILLED = 1035;

    public const COPY_NOT_FOUND = 1041;
    public const COPY_NOT_AVAILABLE = 1042;
    public const INVENTORY_CODE_EXISTS = 1043;
    public const COPY_NOT_LOANED = 1044;

    public const BRANCH_NOT_FOUND = 1051;
    public const BRANCH_ACCESS_DENIED = 1052;

    public const ACCESS_DENIED = 1061;
    public const INVALID_REQUEST = 1062;

    public const FILE_UPLOAD_FAILED = 1071;
    public const FILE_TOO_LARGE = 1072;
    public const FILE_INVALID_TYPE = 1073;

    private function __construct() {}
}