<?php 


final class DomainError {
    public const BAD_EMAIL = 1001;
    public const BAD_PASSWORD = 1002;
    public const EMAIL_TAKEN = 1003;
    public const INVALID_LOGIN = 1004;
    public const ROLE_NOT_FOUND = 1005;
    public const PASSWORD_MISMATCH = 1006;
    public const BOOK_NOT_FOUND = 1007;
    public const LOAN_NOT_FOUND = 1008;
    public const LOAN_RENEW_NOT_ALLOWED = 1009;
    public const RESERVATION_NOT_FOUND = 1010;
    public const RESERVATION_CANCEL_NOT_ALLOWED = 1011;

    private function __construct() {}
}