# AssetVault

**Autor:** Jakub Lenart  
**Rok akademicki:** 2024/2025

---

## Opis projektu

**AssetVault** to aplikacja webowa służąca do zarządzania cyfrowymi assetami (np. plikami graficznymi, dźwiękowymi, 3D itp.) przez wielu użytkowników, z podziałem na role (user/admin), funkcją przesyłania plików, ich pobierania, edycji, filtrowania oraz zarządzania własnym kontem. System zapewnia podstawowe bezpieczeństwo, czytelną szatę graficzną oraz jest przystosowany do uruchamiania w środowisku Docker.

---

## Spis treści

1. [Technologie](#technologie)
2. [Wymagania systemowe](#wymagania-systemowe)
3. [Struktura katalogów](#struktura-katalogów)
4. [Sposób uruchomienia w Dockerze](#sposób-uruchomienia-w-dockerze)
5. [Funkcjonalności](#funkcjonalności)
6. [Role i uprawnienia użytkowników](#role-i-uprawnienia-użytkowników)
7. [Opis widoków (stron)](#opis-widoków-stron)
8. [Struktura bazy danych (opis)](#struktura-bazy-danych-opis)
9. [Bezpieczeństwo](#bezpieczeństwo)
10. [Możliwości rozbudowy](#możliwości-rozbudowy)
11. [Informacje dodatkowe](#informacje-dodatkowe)

---

## Technologie

- **PHP** (logika serwera)
- **MySQL** (baza danych)
- **HTML5**, **CSS3** (szata graficzna, responsywność)
- **JavaScript** (obsługa interfejsu użytkownika)
- **Docker** (uruchamianie aplikacji w kontenerach)
- **PDO** (komunikacja PHP z bazą)
- Brak frameworków PHP i JS

---

## Wymagania systemowe

- Docker + Docker Compose (najwygodniejsza, rekomendowana instalacja)
- Alternatywnie: Apache/Nginx, PHP 8+, MySQL 8+

---

## Struktura katalogów
public/
    auth.php
    config.php
    db.php
    index.php
    images/
        ...
    scripts/
        main.js
    styles/
        *.css
    uploads/
        ... (przesłane pliki użytkowników)
    views/
        asset.php
        assets.php
        dashboard.php
        delete_asset.php
        edit_asset.php
        login.php
        logout.php
        register.php
        upload.php
    partials/
        asset_list.php


---

## Sposób uruchomienia w Dockerze

1. **Klonuj repozytorium lub rozpakuj paczkę projektu:**
    ```bash
    git clone <adres_repozytorium> lub rozpakuj archiwum .zip/.7z
    ```
2. **Upewnij się, że masz zainstalowanego Dockera i Docker Compose.**

3. **W głównym katalogu projektu uruchom:**
    ```bash
    docker compose up --build
    ```
    lub (w zależności od konfiguracji)
    ```bash
    docker-compose up --build
    ```
4. **Aplikacja będzie dostępna lokalnie np. pod adresem:**  
   [http://localhost:8080](http://localhost:8080)  
   (Port może być inny, zależnie od pliku `docker-compose.yml`)

5. **Baza danych oraz wszystkie zależności uruchamiane są automatycznie w osobnych kontenerach.**

---

## Funkcjonalności

- **Rejestracja, logowanie, wylogowanie**
- Zarządzanie kontem użytkownika (zmiana danych, hasła)
- Wysyłanie assetów (plików) przez użytkowników
- Pobieranie i przeglądanie assetów
- Filtrowanie assetów po typie (grafika, audio, 3D, itp.)
- Przeglądanie własnych plików
- Edycja i usuwanie własnych assetów (admin: wszystkich)
- Rola **admin** – dostęp do pełnej administracji plikami i użytkownikami
- Wyświetlanie miniatur plików graficznych
- Panel użytkownika z podstawowymi statystykami
- Obsługa sesji, ról, prostych uprawnień

---

## Role i uprawnienia użytkowników

- **user** – zwykły użytkownik (może zarządzać tylko własnymi plikami)
- **admin** – może edytować i usuwać wszystkie assety (nie tylko własne), dodatkowe uprawnienia do zarządzania użytkownikami
- Przypisanie roli na podstawie adresu e-mail z końcówką `.admin` (np. `jan.kowalski@wp.pl.admin` podczas rejestracji tworzy konto admina)

---

## Opis widoków (stron)

- **Strona główna** (`index.php`) — przekierowanie na odpowiedni widok w zależności od sesji użytkownika
- **Rejestracja** (`views/register.php`) — formularz zakładania konta, walidacja danych, obsługa ról
- **Logowanie** (`views/login.php`) — formularz logowania, obsługa sesji
- **Wylogowanie** (`views/logout.php`) — zakończenie sesji
- **Dashboard** (`views/dashboard.php`) — panel użytkownika, zarządzanie kontem i swoimi assetami
- **Lista assetów** (`views/assets.php`) — przeglądanie i filtrowanie wszystkich plików
- **Widok pojedynczego assetu** (`views/asset.php`) — szczegóły pliku, możliwość pobrania, edycji/usunięcia (jeśli użytkownik ma uprawnienia)
- **Edycja assetu** (`views/edit_asset.php`) — formularz edycji pliku
- **Upload assetu** (`views/upload.php`) — przesyłanie nowych plików
- **Usuwanie assetu** (`views/delete_asset.php`) — potwierdzenie i usuwanie pliku

---

## Struktura bazy danych (opis)

Projekt zakłada co najmniej dwie tabele:

- **users**
    - id (int, PK)
    - username (varchar, unikalny)
    - email (varchar, unikalny)
    - password (varchar, hash)
    - role (enum: user, admin)

- **assets**
    - id (int, PK)
    - filename (varchar)
    - type (varchar, np. png, jpg, mp3, fbx, blend)
    - uploaded_by (FK -> users.id)
    - description (text)
    - created_at (timestamp)
    - inne kolumny wg potrzeb (np. ścieżka, miniaturka, rozmiar)

**Komunikacja z bazą przez PDO, bez wyzwalaczy, widoków czy transakcji.**

---

## Bezpieczeństwo

- Hasła przechowywane jako hashe (password_hash)
- Użycie prepared statements (PDO) do zapytań SQL — ochrona przed SQL Injection
- Walidacja pól formularzy po stronie serwera
- Podstawowa ochrona przed XSS (filtrowanie i esc. danych przy wyświetlaniu)
- Brak zaawansowanej ochrony CSRF (do wdrożenia)
- Pliki uploadowane są sprawdzane pod kątem typu

---

## Możliwości rozbudowy

- Wprowadzenie OOP w backendzie (PHP)
- Zmiana bazy na PostgreSQL
- Implementacja Fetch API (AJAX) — asynchroniczna obsługa przesyłania/pobierania danych
- Dodanie widoków, funkcji i wyzwalaczy w bazie danych
- Lepsze zarządzanie uprawnieniami (np. osobny panel admina)
- Dodanie testów jednostkowych
- Pełna internacjonalizacja (wielojęzyczność)
- Refaktoryzacja do czystszej architektury (MVC, DRY)
- Zautomatyzowany eksport/import bazy

---

## Kontakt

**Autor:** Jakub Lenart  
e-mail: jlenart1326@gmail.com 


