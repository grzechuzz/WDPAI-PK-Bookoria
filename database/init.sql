--
-- PostgreSQL database dump
--

\restrict Up38niR9ONfT7O4fpx9e9PvSDKncfhzpbWoUNELlvZ2wNd3IDlvg058lJD73p6e

-- Dumped from database version 17.7 (Debian 17.7-3.pgdg13+1)
-- Dumped by pg_dump version 17.7 (Debian 17.7-3.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: citext; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public;


--
-- Name: EXTENSION citext; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION citext IS 'data type for case-insensitive character strings';


--
-- Name: fn_loan_fine_amount(bigint, numeric); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_loan_fine_amount(p_loan_id bigint, p_daily_rate numeric) RETURNS numeric
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
	v_due_at TIMESTAMPTZ;
	v_end_at TIMESTAMPTZ;
	v_days_overdue INT;
BEGIN
	SELECT l.due_at, COALESCE(l.returned_at, now()) INTO v_due_at, v_end_at FROM loans l
	WHERE l.id = p_loan_id;
		
	IF NOT FOUND THEN
		RAISE EXCEPTION 'Loan % not found', p_loan_id;
	END IF;
		
	IF v_end_at <= v_due_at THEN
		RETURN 0;
	END IF;
		  
	v_days_overdue := (v_end_at::date - v_due_at::date);
		
	IF v_days_overdue < 0 THEN
		v_days_overdue := 0;
	END IF;
		
	RETURN ROUND(v_days_overdue * p_daily_rate, 2);
END;
$$;


--
-- Name: trg_log_copy_status_change(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_log_copy_status_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF NEW.status IS DISTINCT FROM OLD.status THEN
    INSERT INTO copy_events (copy_id, event_type, details)
    VALUES (NEW.id, NEW.status, 'Status changed from ' || COALESCE(OLD.status, '<NULL>') || ' to ' || COALESCE(NEW.status, '<NULL>'));
  END IF;

  RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: authors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.authors (
    id bigint NOT NULL,
    name public.citext NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT authors_name_check CHECK ((btrim((name)::text) <> ''::text))
);


--
-- Name: authors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.authors ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.authors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: book_authors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.book_authors (
    book_id bigint NOT NULL,
    author_id bigint NOT NULL
);


--
-- Name: books; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.books (
    id bigint NOT NULL,
    title text NOT NULL,
    description text,
    isbn13 text NOT NULL,
    publication_year smallint,
    cover_url text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT books_isbn13_check CHECK ((isbn13 ~ '^[0-9]{13}$'::text)),
    CONSTRAINT books_publication_year_check CHECK (((publication_year >= 1400) AND (publication_year <= 2100))),
    CONSTRAINT books_title_check CHECK ((btrim(title) <> ''::text))
);


--
-- Name: books_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.books ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.books_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: branch_staff; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.branch_staff (
    user_id bigint NOT NULL,
    branch_id bigint NOT NULL
);


--
-- Name: branches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.branches (
    id bigint NOT NULL,
    name text NOT NULL,
    country_code character(2) NOT NULL,
    city text NOT NULL,
    address_line1 text NOT NULL,
    address_line2 text,
    region text,
    postal_code text,
    timezone text DEFAULT 'UTC'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    currency_code character(3) DEFAULT 'USD'::bpchar NOT NULL,
    CONSTRAINT branches_country_code_check CHECK ((country_code ~ '^[A-Z]{2}$'::text)),
    CONSTRAINT branches_name_check CHECK ((TRIM(BOTH FROM name) <> ''::text)),
    CONSTRAINT chk_branches_currency_code CHECK ((currency_code ~ '^[A-Z]{3}$'::text))
);


--
-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.branches ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: copies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.copies (
    id bigint NOT NULL,
    book_id bigint NOT NULL,
    branch_id bigint NOT NULL,
    inventory_code text NOT NULL,
    status text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT copies_inventory_code_check CHECK ((btrim(inventory_code) <> ''::text)),
    CONSTRAINT copies_status_check CHECK ((status = ANY (ARRAY['AVAILABLE'::text, 'LOANED'::text, 'HELD'::text, 'LOST'::text, 'MAINTENANCE'::text])))
);


--
-- Name: copies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.copies ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.copies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: copy_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.copy_events (
    id bigint NOT NULL,
    copy_id bigint NOT NULL,
    event_type text NOT NULL,
    details text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT copy_events_event_type_check CHECK ((btrim(event_type) <> ''::text))
);


--
-- Name: copy_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.copy_events ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.copy_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: loans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loans (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    copy_id bigint NOT NULL,
    loaned_at timestamp with time zone DEFAULT now() NOT NULL,
    due_at timestamp with time zone NOT NULL,
    returned_at timestamp with time zone,
    renewals_count smallint DEFAULT 0 NOT NULL,
    CONSTRAINT chk_due_after_loaned CHECK ((due_at > loaned_at)),
    CONSTRAINT chk_renewals_count_0_1 CHECK (((renewals_count >= 0) AND (renewals_count <= 1)))
);


--
-- Name: loans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.loans ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.loans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reservations (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    book_id bigint NOT NULL,
    branch_id bigint NOT NULL,
    status text DEFAULT 'QUEUED'::text NOT NULL,
    ready_until timestamp with time zone,
    assigned_copy_id bigint,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_ready_requires_copy_and_deadline CHECK ((((status = 'READY_FOR_PICKUP'::text) AND (assigned_copy_id IS NOT NULL) AND (ready_until IS NOT NULL)) OR (status <> 'READY_FOR_PICKUP'::text))),
    CONSTRAINT reservations_status_check CHECK ((status = ANY (ARRAY['QUEUED'::text, 'READY_FOR_PICKUP'::text, 'CANCELLED'::text, 'EXPIRED'::text, 'FULFILLED'::text])))
);


--
-- Name: reservations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.reservations ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.reservations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    code text NOT NULL,
    description text
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.roles ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    email text NOT NULL,
    password_hash text NOT NULL,
    role_id bigint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.users ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: v_book_availability_by_branch; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_book_availability_by_branch AS
 SELECT b.id AS book_id,
    b.title,
    br.id AS branch_id,
    ((br.city || ', '::text) || br.name) AS branch_label,
    count(*) FILTER (WHERE (c.status = 'AVAILABLE'::text)) AS available_count,
    count(*) FILTER (WHERE (c.status = 'HELD'::text)) AS held_count,
    count(*) FILTER (WHERE (c.status = 'LOANED'::text)) AS loaned_count,
    count(*) AS total_copies
   FROM ((public.books b
     JOIN public.copies c ON ((c.book_id = b.id)))
     JOIN public.branches br ON ((br.id = c.branch_id)))
  GROUP BY b.id, b.title, br.id, br.city, br.name;


--
-- Name: v_user_loans; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_user_loans AS
 SELECT l.id AS loan_id,
    l.user_id,
    l.copy_id,
    c.inventory_code AS copy_code,
    c.book_id,
    b.title,
    c.branch_id,
    ((br.city || ', '::text) || br.name) AS branch_label,
    l.loaned_at,
    l.due_at,
    l.returned_at,
    l.renewals_count,
    (l.returned_at IS NULL) AS is_active,
    ((l.returned_at IS NULL) AND (now() > l.due_at)) AS is_overdue,
        CASE
            WHEN ((l.returned_at IS NULL) AND (now() > l.due_at)) THEN GREATEST(0, ((now())::date - (l.due_at)::date))
            ELSE 0
        END AS days_overdue
   FROM (((public.loans l
     JOIN public.copies c ON ((c.id = l.copy_id)))
     JOIN public.books b ON ((b.id = c.book_id)))
     JOIN public.branches br ON ((br.id = c.branch_id)));


--
-- Data for Name: authors; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.authors (id, name, created_at) FROM stdin;
1	Marc Peter Deisenroth	2025-12-30 00:23:17.302943+00
2	A. Aldo Faisal	2025-12-30 00:23:17.302943+00
3	Cheng Soon Org	2025-12-30 00:23:17.302943+00
4	Adam Mickiewicz	2025-12-30 00:29:28.700683+00
5	Andrzej Sapkowski	2025-12-30 00:29:28.700683+00
6	Robert C. Martin	2025-12-30 00:29:28.700683+00
7	Michael Sipser	2025-12-30 13:28:41.333196+00
8	Adam Bielecki	2025-12-30 14:20:01.537254+00
9	Dominik Szczepański	2025-12-30 14:20:01.537254+00
10	Eric Evans	2025-12-30 14:30:08.387541+00
11	Juliusz Słowacki	2025-12-30 14:31:34.239563+00
12	Daniel Kahneman	2025-12-30 14:42:53.164785+00
13	Cay S. Horstmann	2025-12-30 14:43:21.970876+00
14	George Orwell	2026-01-04 16:53:33.354685+00
16	Marcin Karbowski	2026-01-05 13:52:50.341785+00
\.


--
-- Data for Name: book_authors; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.book_authors (book_id, author_id) FROM stdin;
1	1
1	2
1	3
2	4
3	5
4	6
5	7
6	8
6	9
7	10
8	11
9	12
10	13
12	14
13	16
\.


--
-- Data for Name: books; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.books (id, title, description, isbn13, publication_year, cover_url, created_at) FROM stdin;
2	Pan Tadeusz	Epos narodowy.	9788307033419	2010	/images/covers/b3.png	2025-12-30 00:33:10.713355+00
4	Czysty Kod	Programowanie obiektowe. Zasady SOLID, GRASP i wiele, wiele więcej	9788328302341	2014	/images/covers/b2.png	2025-12-30 00:38:49.786546+00
7	Domain-Driven Design	Tworzenie skomplikowanych systemów informatycznych wymaga nowego podejścia. Dotychczas stosowane metody przestają się sprawdzać i generują mnóstwo problemów. Odpowiedzią na nie jest Domain-Driven Design, w skrócie DDD. W tym podejściu szczególny nacisk kładzie się na tworzenie obiektów dokładnie odzwierciedlających zachowanie ich odpowiedników istniejących w rzeczywistości. Dzięki temu projektowanie systemu można powierzyć ekspertom z danej branży, którzy niekoniecznie muszą być specjalistami w dziedzinie projektowania architektury systemów informatycznych.	9788328391840	2015	/images/covers/b7.png	2025-12-30 14:29:42.793449+00
3	Wiedźmin: Ostatnie Życzenie	Zbiór opowiadań.	9788375780635	1993	/images/covers/b4.jpg	2025-12-30 00:37:29.863979+00
8	Kordian	Wydanie Kordiana kompletne bez skrótów i cięć w treści. W tym wydaniu znajdziesz odpowiedzi na pytania z podręcznika - pewniak na teście , czyli wskazanie zagadnień, które zwykle pojawiają się w pytaniach z danej lektury we wszelkich testach sprawdzających wiedzę, a także w podręcznikach i na klasówkach. Książka zawiera pełen tekst lektury.	9788373271678	2000	/images/covers/b8.png	2025-12-30 14:33:45.433693+00
5	Wprowadzenie do teorii obliczeń	Podręcznik do teorii obliczeń skierowany do studentów informatyki na wszystkich wyższych uczelniach. Dotyczy podstaw informatyki, a w szczególności możliwości obliczeniowych współczesnych komputerów. Składa się z trzech części. Pierwsza poświęcona automatom i językom formalnym. Omówiono w niej niedeterminizm, równoważność automatów deterministycznych i niedeterministycznych, wyrażenia regularne, kryteria nieregularności języków, a także języki bezkontekstowe. Druga część dotyczy teorii obliczalności . Opisano w niej ograniczenia współczesnych komputerów, wyjaśniono pojęcia rozstrzygalności i nierozstrzygalności. Trzecia część jest poświęcona teorii złożoności. Przedstawiono w niej podstawowe klasy złożoności obliczeniowej, klasę problemów NP-zupełnych, a także klasyfikację problemów ze względu na możliwość automatycznego ich rozwiązywania przy ograniczonych zasobach, a także deterministycznym językom bezkontekstowym.	9788301209261	2020	/images/covers/b5.jpg	2025-12-30 13:27:57.902919+00
9	Pułapki myślenia	Mechanizmy zachodzące w naszym umyśle nie zostały jeszcze zgłębione przez naukowców. Ludzki mózg pracuje na pełnych obrotach przez cały czas. Jak myślimy? Wolno czy szybko? Jak działa nasz umysł? Na te i inne pytania odpowiedzi poszukuje psycholog Daniel Kahneman, laureat Nagrody Nobla w dziedzinie ekonomii. Przedstawia on w swojej książce mechanizmy ludzkiego rozumowania.	9788382651966	2022	/images/covers/b9.png	2025-12-30 14:38:36.659179+00
1	Matematyka w uczeniu maszynowym	Ten podręcznik jest przeznaczony dla osób, które chcą dobrze zrozumieć matematyczne podstawy uczenia maszynowego i nabrać praktycznego doświadczenia w używaniu pojęć matematycznych. Wyjaśniono tutaj stosowanie szeregu technik matematycznych, takich jak algebra liniowa, geometria analityczna, rozkłady macierzy, rachunek wektorowy, optymalizacja, probabilistyka i statystyka. Następnie zaprezentowano matematyczne aspekty czterech podstawowych metod uczenia maszynowego: regresji liniowej, analizy głównych składowych, modeli mieszanin rozkładów Gaussa i maszyn wektorów nośnych. W każdym rozdziale znalazły się przykłady i ćwiczenia ułatwiające przyswojenie materiału.	9788328384590	2022	/images/covers/b1.png	2025-12-30 00:27:31.99969+00
6	Spod zamarzniętych powiek	Ta książka to pierwsza pełna opowieść Adama Bieleckiego o jego drodze z górniczych Tychów na szczyty najwyższych gór świata. To także hołd dla bohaterów i twórców polskiego himalaizmu zimowego, w których szeregu Bielecki ustawił się, zdobywając jako pierwszy człowiek zimą Gaszerbrum I i Broad Peak. W dramatycznych historiach tych wejść Bielecki przede wszystkim opowiada o nieludzkich warunkach zimowej wspinaczki w Himalajach i Karakorum, sztuki uprawianej przez nielicznych na świecie, która stała się naszą narodową specjalnością.	9788326844751	2017	/images/covers/b6.png	2025-12-30 14:22:39.417748+00
10	JAVA: Techniki zaawansowane	Książka zawiera szczegółowe omówienie Javy 21, programowania korporacyjnego, sieciowego i bazodanowego, a także zagadnień związanych z internacjonalizacją i metodami natywnymi. Dużo miejsca poświęcono obsłudze strumieni, pracy z językiem XML, API dat i czasu, API skryptowemu czy kompilacji.	9788328930049	2025	/images/covers/b10.jpg	2025-12-30 14:45:49.65407+00
12	Rok 1984	Powieść Rok 1984 jest jednym z tych dzieł literackich, które odbiły się szerokim echem i na stałe wkradły się do ludzkiej świadomości.\r\n\r\nPrzedstawiamy nowe wydanie Roku 1984 w serii Kolorowa Klasyka. Najnowsze tłumaczenie znanej powieści przygotował Krzysztof Mazurek. Tekst uzupełniają ilustracje autorstwa Kamila Rekosza - klimatyczne i przejmujące, świetnie oddają atmosferę orwellowskiej rzeczywistości. Rok 1984 to lektura absolutnie obowiązkowa.	9788375179767	2021	/images/covers/596d6f3560c339fda1482695d3911544.png	2026-01-04 17:16:44.000347+00
13	Podstawy Kryptografii	Kryptografia to dziedzina nauki, której sedno stanowią sposoby bezpiecznego przekazywania informacji. Jest ona niemal tak stara, jak nasza cywilizacja, a dziś rozwija się w sposób niezwykle dynamiczny. Gdy tylko narodziły się pierwsze metody zapisu i komunikowania się, pojawiła się też konieczność zabezpieczenia informacji przed tymi, którzy mogliby wykorzystać je na niekorzyść osób dysponujących tymi informacjami. Od bezpieczeństwa ważnych informacji zależały często losy całych państw i narodów. O rozstrzygnięciach wielkich bitew nierzadko decydowały inteligencja i determinacja pojedynczych osób, które potrafiły odpowiednio skutecznie szyfrować (bądź też deszyfrować) nadawane (lub przechwytywane) komunikaty.	9788328385696	2021	/images/covers/70eb530f3d18b1e5c32425377e65b02e.png	2026-01-05 13:52:50.341785+00
\.


--
-- Data for Name: branch_staff; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.branch_staff (user_id, branch_id) FROM stdin;
3	3
3	2
3	1
\.


--
-- Data for Name: branches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.branches (id, name, country_code, city, address_line1, address_line2, region, postal_code, timezone, created_at, currency_code) FROM stdin;
2	Filia Centrum	PL	Kraków	Grodzka 8	\N	Małopolska	31-325	Europe/Warsaw	2025-12-30 00:17:12.60202+00	PLN
1	Filia Ruczaj	PL	Kraków	Zachodnia 52	\N	Małopolska	30-385	Europe/Warsaw	2025-12-30 00:08:26.950754+00	PLN
3	Filia NH	PL	Kraków	Aleja Róż 33	\N	Małopolska	31-949	Europe/Warsaw	2025-12-30 00:19:45.681481+00	PLN
\.


--
-- Data for Name: copies; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.copies (id, book_id, branch_id, inventory_code, status, created_at) FROM stdin;
3	1	1	4572832312	AVAILABLE	2025-12-30 14:51:44.896687+00
4	1	1	4572832313	AVAILABLE	2025-12-30 14:51:48.057939+00
5	1	1	4572832314	AVAILABLE	2025-12-30 14:51:50.893997+00
6	2	1	4572832315	AVAILABLE	2025-12-30 14:52:00.321796+00
7	2	1	4572832316	AVAILABLE	2025-12-30 14:52:03.635987+00
8	3	1	4572832317	AVAILABLE	2025-12-30 14:52:08.692361+00
9	3	1	4572832318	AVAILABLE	2025-12-30 14:52:11.738598+00
10	3	1	4572832319	AVAILABLE	2025-12-30 14:52:14.73641+00
13	4	1	4572832322	AVAILABLE	2025-12-30 15:01:09.845211+00
14	4	1	4572832323	AVAILABLE	2025-12-30 15:01:12.369456+00
15	4	1	4572832324	AVAILABLE	2025-12-30 15:01:20.828232+00
16	4	1	4572832325	AVAILABLE	2025-12-30 15:01:23.118863+00
17	5	1	4572832326	AVAILABLE	2025-12-30 15:01:28.16946+00
19	6	1	4572832327	AVAILABLE	2025-12-30 15:01:35.850684+00
20	6	1	4572832328	AVAILABLE	2025-12-30 15:01:40.516331+00
21	6	1	4572832329	AVAILABLE	2025-12-30 15:01:43.406348+00
22	6	1	4572832330	AVAILABLE	2025-12-30 15:01:46.203652+00
23	6	1	4572832331	AVAILABLE	2025-12-30 15:01:48.485111+00
25	9	1	4572832333	AVAILABLE	2025-12-30 15:02:01.163134+00
26	9	1	4572832334	AVAILABLE	2025-12-30 15:02:03.984283+00
27	9	1	4572832335	AVAILABLE	2025-12-30 15:02:06.634139+00
28	1	2	4572832336	AVAILABLE	2025-12-30 15:02:35.174705+00
29	1	2	4572832337	AVAILABLE	2025-12-30 15:02:39.812408+00
31	4	2	4572832339	AVAILABLE	2025-12-30 15:02:50.860629+00
32	4	2	4572832340	AVAILABLE	2025-12-30 15:02:55.178989+00
33	4	2	4572832341	AVAILABLE	2025-12-30 15:02:57.939726+00
34	4	2	4572832342	AVAILABLE	2025-12-30 15:03:00.461372+00
35	4	2	4572832343	AVAILABLE	2025-12-30 15:03:02.748388+00
36	4	2	4572832344	AVAILABLE	2025-12-30 15:03:30.795619+00
37	4	2	4572832345	AVAILABLE	2025-12-30 15:03:30.821939+00
41	4	2	4572832346	AVAILABLE	2025-12-30 15:03:41.214723+00
42	4	2	4572832347	AVAILABLE	2025-12-30 15:03:44.000908+00
43	5	2	4572832348	AVAILABLE	2025-12-30 15:03:49.951954+00
44	5	2	4572832349	AVAILABLE	2025-12-30 15:03:52.776718+00
46	8	2	4572832351	AVAILABLE	2025-12-30 15:04:02.718728+00
47	8	2	4572832352	AVAILABLE	2025-12-30 15:04:04.715238+00
48	8	2	4572832353	AVAILABLE	2025-12-30 15:04:06.979972+00
49	8	2	4572832354	AVAILABLE	2025-12-30 15:04:09.266814+00
51	9	2	4572832356	AVAILABLE	2025-12-30 15:04:17.086866+00
52	9	2	4572832357	AVAILABLE	2025-12-30 15:04:19.478066+00
54	10	2	4572832359	AVAILABLE	2025-12-30 15:04:29.456483+00
55	10	2	4572832360	AVAILABLE	2025-12-30 15:04:32.247699+00
56	10	2	4572832361	AVAILABLE	2025-12-30 15:04:34.486974+00
57	10	2	4572832362	AVAILABLE	2025-12-30 15:04:36.907174+00
58	10	2	4572832363	AVAILABLE	2025-12-30 15:04:39.337702+00
59	1	3	4572832364	AVAILABLE	2025-12-30 15:05:16.004201+00
60	1	3	4572832365	AVAILABLE	2025-12-30 15:05:18.254403+00
62	1	3	4572832366	AVAILABLE	2025-12-30 15:05:22.653272+00
63	1	3	4572832367	AVAILABLE	2025-12-30 15:05:25.335276+00
65	2	3	4572832369	AVAILABLE	2025-12-30 15:05:34.446386+00
66	2	3	4572832370	AVAILABLE	2025-12-30 15:05:39.500451+00
67	2	3	4572832371	AVAILABLE	2025-12-30 15:05:48.618647+00
68	2	3	4572832372	AVAILABLE	2025-12-30 15:05:50.712208+00
69	2	3	4572832373	AVAILABLE	2025-12-30 15:05:53.087126+00
70	3	3	4572832374	AVAILABLE	2025-12-30 15:06:00.036443+00
71	3	3	4572832375	AVAILABLE	2025-12-30 15:06:02.451945+00
72	3	3	4572832376	AVAILABLE	2025-12-30 15:06:04.941431+00
73	3	3	4572832377	AVAILABLE	2025-12-30 15:06:07.829151+00
74	3	3	4572832378	AVAILABLE	2025-12-30 15:06:10.2231+00
75	3	3	4572832379	AVAILABLE	2025-12-30 15:06:13.266938+00
76	4	3	4572832380	AVAILABLE	2025-12-30 15:06:18.892891+00
77	4	3	4572832381	AVAILABLE	2025-12-30 15:06:21.036539+00
78	4	3	4572832382	AVAILABLE	2025-12-30 15:06:23.320839+00
79	5	3	4572832383	AVAILABLE	2025-12-30 15:06:28.316579+00
80	5	3	4572832384	AVAILABLE	2025-12-30 15:06:30.465093+00
81	5	3	4572832385	AVAILABLE	2025-12-30 15:06:32.671338+00
82	5	3	4572832386	AVAILABLE	2025-12-30 15:06:35.951566+00
83	5	3	4572832387	AVAILABLE	2025-12-30 15:06:38.282134+00
84	5	3	4572832388	AVAILABLE	2025-12-30 15:06:40.912051+00
85	5	3	4572832389	AVAILABLE	2025-12-30 15:06:43.287307+00
86	5	3	4572832390	AVAILABLE	2025-12-30 15:06:46.805752+00
87	6	3	4572832391	AVAILABLE	2025-12-30 15:06:52.54187+00
88	6	3	4572832392	AVAILABLE	2025-12-30 15:06:55.713489+00
89	6	3	4572832393	AVAILABLE	2025-12-30 15:06:57.740225+00
90	6	3	4572832394	AVAILABLE	2025-12-30 15:06:59.781833+00
93	7	3	4572832396	AVAILABLE	2025-12-30 15:07:09.325603+00
94	7	3	4572832397	AVAILABLE	2025-12-30 15:07:11.868324+00
95	7	3	4572832398	AVAILABLE	2025-12-30 15:07:14.14836+00
96	7	3	4572832399	AVAILABLE	2025-12-30 15:07:17.137002+00
98	8	3	4572832101	AVAILABLE	2025-12-30 15:07:30.55176+00
45	8	2	4572832350	AVAILABLE	2025-12-30 15:04:00.257612+00
24	7	1	4572832332	AVAILABLE	2025-12-30 15:01:54.288495+00
97	8	3	4572832100	AVAILABLE	2025-12-30 15:07:27.002004+00
92	7	3	4572832395	LOANED	2025-12-30 15:07:07.144605+00
2	1	1	4572832311	AVAILABLE	2025-12-30 14:51:36.01435+00
30	4	2	4572832338	LOANED	2025-12-30 15:02:47.356423+00
12	4	1	4572832321	AVAILABLE	2025-12-30 15:01:06.5986+00
50	9	2	4572832355	LOANED	2025-12-30 15:04:14.481332+00
53	10	2	4572832358	HELD	2025-12-30 15:04:26.464976+00
64	2	3	4572832368	AVAILABLE	2025-12-30 15:05:30.959331+00
99	9	3	4572832102	AVAILABLE	2025-12-30 15:07:36.233956+00
101	9	3	4572832103	AVAILABLE	2025-12-30 15:07:39.999219+00
102	9	3	4572832104	AVAILABLE	2025-12-30 15:07:42.125356+00
103	9	3	4572832105	AVAILABLE	2025-12-30 15:07:44.326607+00
105	10	3	4572832107	AVAILABLE	2025-12-30 15:07:52.529662+00
106	10	3	4572832108	AVAILABLE	2025-12-30 15:07:54.944344+00
107	10	3	4572832109	AVAILABLE	2025-12-30 15:07:57.526088+00
11	4	1	4572832320	LOANED	2025-12-30 15:01:03.99795+00
108	4	2	4920346788	AVAILABLE	2026-01-04 18:23:58.347378+00
104	10	3	4572832106	AVAILABLE	2025-12-30 15:07:49.420346+00
110	13	3	4928388910	AVAILABLE	2026-01-05 14:17:59.52212+00
111	13	3	4920346792	AVAILABLE	2026-01-05 14:19:06.704034+00
112	8	1	45843859921	AVAILABLE	2026-01-05 16:02:32.447678+00
109	12	2	4920346791	LOANED	2026-01-04 20:13:48.458592+00
\.


--
-- Data for Name: copy_events; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.copy_events (id, copy_id, event_type, details, created_at) FROM stdin;
1	11	LOANED	Status changed from AVAILABLE to LOANED	2026-01-04 13:42:04.179136+00
2	97	HELD	Status changed from AVAILABLE to HELD	2026-01-04 15:23:44.421882+00
3	45	HELD	Status changed from AVAILABLE to HELD	2026-01-04 15:24:48.429149+00
4	97	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-04 15:25:30.759728+00
5	104	HELD	Status changed from AVAILABLE to HELD	2026-01-04 19:58:25.701808+00
6	30	HELD	Status changed from AVAILABLE to HELD	2026-01-05 02:54:05.057782+00
7	30	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 02:54:16.213567+00
8	104	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 02:54:17.331638+00
9	45	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 02:54:18.390276+00
10	24	HELD	Status changed from AVAILABLE to HELD	2026-01-05 02:54:31.976048+00
11	24	LOANED	Status changed from HELD to LOANED	2026-01-05 03:00:20.545953+00
12	24	AVAILABLE	Status changed from LOANED to AVAILABLE	2026-01-05 13:44:24.731908+00
13	30	HELD	Status changed from AVAILABLE to HELD	2026-01-05 13:45:49.638751+00
14	30	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 13:46:31.076942+00
15	109	HELD	Status changed from AVAILABLE to HELD	2026-01-05 13:46:44.3705+00
16	12	HELD	Status changed from AVAILABLE to HELD	2026-01-05 14:54:16.079072+00
17	92	HELD	Status changed from AVAILABLE to HELD	2026-01-05 14:54:25.904613+00
18	2	HELD	Status changed from AVAILABLE to HELD	2026-01-05 14:54:41.704231+00
19	12	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 14:54:45.016665+00
20	30	HELD	Status changed from AVAILABLE to HELD	2026-01-05 16:00:50.497135+00
21	64	HELD	Status changed from AVAILABLE to HELD	2026-01-05 16:01:53.515299+00
22	64	LOANED	Status changed from HELD to LOANED	2026-01-05 16:03:02.383989+00
23	109	LOANED	Status changed from HELD to LOANED	2026-01-05 16:03:04.996829+00
24	2	LOANED	Status changed from HELD to LOANED	2026-01-05 16:03:08.486117+00
25	30	LOANED	Status changed from HELD to LOANED	2026-01-05 16:03:09.235757+00
26	92	LOANED	Status changed from HELD to LOANED	2026-01-05 16:03:09.813837+00
27	2	AVAILABLE	Status changed from LOANED to AVAILABLE	2026-01-05 16:03:16.237173+00
28	50	HELD	Status changed from AVAILABLE to HELD	2026-01-05 16:10:47.79144+00
29	53	HELD	Status changed from AVAILABLE to HELD	2026-01-05 16:10:55.087694+00
30	24	HELD	Status changed from AVAILABLE to HELD	2026-01-05 16:11:02.359771+00
31	24	AVAILABLE	Status changed from HELD to AVAILABLE	2026-01-05 16:11:04.069766+00
32	64	AVAILABLE	Status changed from LOANED to AVAILABLE	2026-01-05 16:12:00.927377+00
33	50	LOANED	Status changed from HELD to LOANED	2026-01-05 16:12:03.084638+00
\.


--
-- Data for Name: loans; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.loans (id, user_id, copy_id, loaned_at, due_at, returned_at, renewals_count) FROM stdin;
1	1	11	2025-12-31 17:17:00+00	2026-02-13 23:59:00+00	\N	1
2	1	24	2026-01-05 03:00:20.545953+00	2026-02-04 03:00:20.545953+00	2026-01-05 13:44:24.731908+00	0
4	1	109	2026-01-05 16:03:04.996829+00	2026-02-04 16:03:04.996829+00	\N	0
6	5	30	2026-01-05 16:03:09.235757+00	2026-02-04 16:03:09.235757+00	\N	0
7	5	92	2026-01-05 16:03:09.813837+00	2026-02-04 16:03:09.813837+00	\N	0
5	5	2	2026-01-05 16:03:08.486117+00	2026-02-04 16:03:08.486117+00	2026-01-05 16:03:16.237173+00	0
3	1	64	2026-01-05 16:03:02.383989+00	2026-02-04 16:03:02.383989+00	2026-01-05 16:12:00.927377+00	0
8	6	50	2026-01-05 16:12:03.084638+00	2026-02-04 16:12:03.084638+00	\N	0
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reservations (id, user_id, book_id, branch_id, status, ready_until, assigned_copy_id, created_at) FROM stdin;
1	1	4	1	FULFILLED	2026-01-01 19:00:00+00	11	2025-12-31 16:41:42.375223+00
7	1	8	3	CANCELLED	\N	\N	2026-01-04 15:23:44.421882+00
10	1	4	2	CANCELLED	\N	\N	2026-01-05 02:54:05.057782+00
9	1	10	3	CANCELLED	\N	\N	2026-01-04 19:58:25.701808+00
8	1	8	2	CANCELLED	\N	\N	2026-01-04 15:24:48.429149+00
11	1	7	1	FULFILLED	2026-01-07 02:54:31.976048+00	24	2026-01-05 02:54:31.976048+00
12	1	4	2	CANCELLED	\N	\N	2026-01-05 13:45:49.638751+00
14	5	4	1	CANCELLED	\N	\N	2026-01-05 14:54:16.079072+00
18	1	2	3	FULFILLED	2026-01-07 16:01:53.515299+00	64	2026-01-05 16:01:53.515299+00
13	1	12	2	FULFILLED	2026-01-07 13:46:44.3705+00	109	2026-01-05 13:46:44.3705+00
16	5	1	1	FULFILLED	2026-01-07 14:54:41.704231+00	2	2026-01-05 14:54:41.704231+00
17	5	4	2	FULFILLED	2026-01-07 16:00:50.497135+00	30	2026-01-05 16:00:50.497135+00
15	5	7	3	FULFILLED	2026-01-07 14:54:25.904613+00	92	2026-01-05 14:54:25.904613+00
20	6	10	2	READY_FOR_PICKUP	2026-01-07 16:10:55.087694+00	53	2026-01-05 16:10:55.087694+00
21	6	7	1	CANCELLED	\N	\N	2026-01-05 16:11:02.359771+00
19	6	9	2	FULFILLED	2026-01-07 16:10:47.79144+00	50	2026-01-05 16:10:47.79144+00
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.roles (id, code, description) FROM stdin;
1	ADMIN	\N
2	LIBRARIAN	\N
3	READER	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, email, password_hash, role_id, is_active, created_at) FROM stdin;
3	adamklimczyk@gmail.com	$2y$10$POn63eLj4MWMGIOuyJ1IV.8VyU9xaiezGWwqLRLXWU3O5wGyLLlQK	2	t	2026-01-04 15:34:49.042644+00
4	karolinamarzec@gmail.com	$2y$10$KerescXyMyUYb5cp2ddGR.nIjt4mEESC4Wt2RLRInGVxHoY1upFSK	1	t	2026-01-04 16:18:27.853269+00
5	uzytkownik1@gmail.com	$2y$10$bAmfktll0VxkTxIQVZq68.r10dwDaG8K28WX5Bh1xM0rjO7O7L25i	3	t	2026-01-05 14:53:46.90448+00
1	karolkubica12@gmail.com	$2y$10$x8X/mESz7qwTLt6Mw.FtReTdflY9JW1AdSOxQ1YGkQrwlEF.IBpkq	3	t	2025-12-29 22:09:44.825102+00
6	uzytkownik2@gmail.com	$2y$10$RLBb01ikzU/qbzWztU8P7epSUnHZtJ4rr3catYepM/suY2EMyMlki	3	t	2026-01-05 16:10:02.222216+00
\.


--
-- Name: authors_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.authors_id_seq', 16, true);


--
-- Name: books_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.books_id_seq', 13, true);


--
-- Name: branches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.branches_id_seq', 3, true);


--
-- Name: copies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.copies_id_seq', 112, true);


--
-- Name: copy_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.copy_events_id_seq', 33, true);


--
-- Name: loans_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.loans_id_seq', 8, true);


--
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reservations_id_seq', 21, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 3, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- Name: authors authors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.authors
    ADD CONSTRAINT authors_pkey PRIMARY KEY (id);


--
-- Name: book_authors book_authors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.book_authors
    ADD CONSTRAINT book_authors_pkey PRIMARY KEY (book_id, author_id);


--
-- Name: books books_isbn13_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_isbn13_key UNIQUE (isbn13);


--
-- Name: books books_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.books
    ADD CONSTRAINT books_pkey PRIMARY KEY (id);


--
-- Name: branch_staff branch_staff_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branch_staff
    ADD CONSTRAINT branch_staff_pkey PRIMARY KEY (user_id, branch_id);


--
-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);


--
-- Name: copies copies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copies
    ADD CONSTRAINT copies_pkey PRIMARY KEY (id);


--
-- Name: copy_events copy_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copy_events
    ADD CONSTRAINT copy_events_pkey PRIMARY KEY (id);


--
-- Name: loans loans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_pkey PRIMARY KEY (id);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id);


--
-- Name: roles roles_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_code_key UNIQUE (code);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: copies uniq_branch_id_inventory_code; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copies
    ADD CONSTRAINT uniq_branch_id_inventory_code UNIQUE (branch_id, inventory_code);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: idx_loans_user_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_loans_user_active ON public.loans USING btree (user_id, due_at) WHERE (returned_at IS NULL);


--
-- Name: uniq_email_lower; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_email_lower ON public.users USING btree (lower(email));


--
-- Name: uniq_loans_active_copy; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_loans_active_copy ON public.loans USING btree (copy_id) WHERE (returned_at IS NULL);


--
-- Name: uniq_res_active_one_per_user_book_branch; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_res_active_one_per_user_book_branch ON public.reservations USING btree (user_id, book_id, branch_id) WHERE (status = ANY (ARRAY['QUEUED'::text, 'READY_FOR_PICKUP'::text]));


--
-- Name: uq_authors_name_ci; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_authors_name_ci ON public.authors USING btree (name);


--
-- Name: copies tr_copies_log_status_change; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER tr_copies_log_status_change AFTER UPDATE OF status ON public.copies FOR EACH ROW EXECUTE FUNCTION public.trg_log_copy_status_change();


--
-- Name: book_authors book_authors_author_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.book_authors
    ADD CONSTRAINT book_authors_author_id_fkey FOREIGN KEY (author_id) REFERENCES public.authors(id) ON DELETE RESTRICT;


--
-- Name: book_authors book_authors_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.book_authors
    ADD CONSTRAINT book_authors_book_id_fkey FOREIGN KEY (book_id) REFERENCES public.books(id) ON DELETE CASCADE;


--
-- Name: branch_staff branch_staff_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branch_staff
    ADD CONSTRAINT branch_staff_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branches(id);


--
-- Name: branch_staff branch_staff_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branch_staff
    ADD CONSTRAINT branch_staff_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: copies copies_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copies
    ADD CONSTRAINT copies_book_id_fkey FOREIGN KEY (book_id) REFERENCES public.books(id) ON DELETE RESTRICT;


--
-- Name: copies copies_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copies
    ADD CONSTRAINT copies_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE RESTRICT;


--
-- Name: copy_events copy_events_copy_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.copy_events
    ADD CONSTRAINT copy_events_copy_id_fkey FOREIGN KEY (copy_id) REFERENCES public.copies(id) ON DELETE CASCADE;


--
-- Name: loans fk_loans_copy; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT fk_loans_copy FOREIGN KEY (copy_id) REFERENCES public.copies(id) ON DELETE RESTRICT;


--
-- Name: loans fk_loans_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_assigned_copy_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_assigned_copy_id_fkey FOREIGN KEY (assigned_copy_id) REFERENCES public.copies(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_book_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_book_id_fkey FOREIGN KEY (book_id) REFERENCES public.books(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: users users_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict Up38niR9ONfT7O4fpx9e9PvSDKncfhzpbWoUNELlvZ2wNd3IDlvg058lJD73p6e

