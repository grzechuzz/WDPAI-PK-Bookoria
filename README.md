# Bookoria - system do zarządzania biblioteką
Bookoria to system do zarządzania zasobami bibliotek. Umożliwia przeglądanie katalogu książek, sprawdzanie ich dostępności w różnych lokalizacjach oraz składanie rezerwacji i śledzenie wypożyczeń. Administratorzy oraz bibliotekarze mogą dodawać nowe pozycje, zarządzać egzemplarzami i rejestrować zwroty. 

System został stworzony w ramach projektu z przedmiotu WDPAI.

## Uruchomienie

1. Sklonuj repozytorium:
```bash
https://github.com/grzechuzz/WDPAI-PK-Bookoria.git
```

2. Skopiuj plik konfiguracyjny i dostosuj hasła:
```bash
cp .env.example .env
```

3. Uruchom kontenery:
```bash
docker-compose up -d --build
```
4. Otwórz aplikację:
```bash
http://localhost:8080
```
## Domyślne dane logowania

| Rola | Email | Hasło |
|------|-------|-------|
| Administrator | uzytkownik3@gmail.com | superhaslo |
| Bibliotekarz | uzytkownik4@gmail.com | superhaslo |
| Czytelnik | uzytkownik2@gmail.com | superhaslo |

## Diagram ERD
<img width="1848" height="3120" alt="erd" src="https://github.com/user-attachments/assets/b09b7ba2-c258-4c06-8fb5-b46fe92ea92d" />

## Screeny aplikacji
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/5a46aa27-b9d5-4440-ae86-908171882344" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/41454eca-35d9-440a-b1ad-4b28318aff88" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/c3f759ab-c530-43da-abdd-0d46ad43bb8d" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/6c856d19-8f33-48eb-a68a-8cf6c70dded3" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/8aff2de4-84ba-4902-a000-8e4ca8092791" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/9a5c782e-b825-4f38-8f34-bbf09dca657f" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/c22ac102-8612-4a81-9eb4-aa850ea441ec" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/f267bb55-07f3-4e59-86e6-40eca89a95b1" />
<img width="1912" height="947" alt="image" src="https://github.com/user-attachments/assets/d8afe4e3-db66-4560-8b21-7dd42776266c" />







