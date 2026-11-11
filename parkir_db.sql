-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Nov 2026 pada 21.01
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkir_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `level` enum('admin','petugas') NOT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `email`, `username`, `password`, `gambar`, `level`, `login_attempts`, `last_login_at`, `otp`, `otp_expires_at`, `otp_attempts`, `created_at`, `updated_at`) VALUES
(1, 'akaridmonkey@gmail.com', 'rayaao', '$2y$12$Nuuoa.07Hdq1zhBmgw4mJuohh4D52O8NLKL3UywWBB2wGgZftgHau', '', 'admin', 0, '2026-11-11 19:42:59', NULL, NULL, 0, '2026-11-04 12:59:46', '2026-11-11 19:42:59'),
(3, 'kozumechibi@gmail.com', 'ayamm', '$2y$12$igLpMLcTrOrOR5m6dGQMl.1ZbqHOr20NEMQyqQNOTookK67qwePQy', 'profile/1794382030_WhatsApp Image 2026-08-06 at 09.55.34.jpeg', 'petugas', 0, '2026-11-11 19:52:10', NULL, NULL, 0, '2026-11-04 19:16:06', '2026-11-11 19:52:10'),
(4, 'ghaidatsuraya@gmail.com', 'uni', '$2y$12$0XSVVflppHSwNJI82LZmY.BabPT9azFl1l7bWRfmBbhMCZTSLfVzG', NULL, 'petugas', 0, NULL, NULL, NULL, 0, '2026-11-11 19:43:42', '2026-11-11 19:43:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `members`
--

CREATE TABLE `members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `no_member` varchar(255) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `instansi` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telepon` varchar(255) DEFAULT NULL,
  `motor_1` varchar(20) DEFAULT NULL,
  `motor_2` varchar(20) DEFAULT NULL,
  `motor_3` varchar(20) DEFAULT NULL,
  `mobil_1` varchar(20) DEFAULT NULL,
  `mobil_2` varchar(20) DEFAULT NULL,
  `mobil_3` varchar(20) DEFAULT NULL,
  `plat_1` varchar(20) DEFAULT NULL,
  `plat_2` varchar(20) DEFAULT NULL,
  `plat_3` varchar(20) DEFAULT NULL,
  `tanggal_daftar` date NOT NULL,
  `masa_aktif` date NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `members`
--

INSERT INTO `members` (`id`, `no_member`, `nama`, `instansi`, `email`, `telepon`, `motor_1`, `motor_2`, `motor_3`, `mobil_1`, `mobil_2`, `mobil_3`, `plat_1`, `plat_2`, `plat_3`, `tanggal_daftar`, `masa_aktif`, `status`, `created_at`, `updated_at`) VALUES
(3, 'MBR-20261105-002', 'Fitri', 'google', 'akaridmonkey@gmail.com', '081513442182', 'BS 7878 VI', 'JI 0987 KI', 'UI 8768 JI', 'VG 8789 BI', 'BH 7687 HJ', 'HH 8768 JH', 'BS 7878 VI', 'JI 0987 KI', 'UI 8768 JI', '2026-11-05', '2026-12-05', 'aktif', '2026-11-05 12:10:18', '2026-11-11 19:38:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_11_04_185138_create_admins_table', 1),
(2, '2026_11_04_185200_create_password_otps_table', 1),
(3, '2026_11_04_195744_create_admin_table', 2),
(4, '2026_11_04_202206_create_personal_access_tokens_table', 3),
(5, '2026_11_05_000001_create_members_table', 4),
(6, '2026_11_05_000002_create_tiket_parkir_table', 4),
(7, '2026_11_05_000003_create_transaksi_table', 4),
(8, '2026_11_05_100000_add_plat_to_members', 5),
(9, '2026_11_12_000001_add_plat_motor_mobil_to_members', 6);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_otps`
--

CREATE TABLE `password_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(47, 'App\\Models\\Admin', 1, 'auth_token', 'dc043dbee9570ede21b945da57fe8cb79667a96a37cea7a978f9dfb3134d37b7', '[\"*\"]', NULL, NULL, '2026-11-11 19:42:59', '2026-11-11 19:42:59'),
(48, 'App\\Models\\Admin', 3, 'auth_token', '5d738451063ebbcea475c3ae459959eef8d9bd201bc5a33573b1afdf08f05bdd', '[\"*\"]', NULL, NULL, '2026-11-11 19:52:10', '2026-11-11 19:52:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tiket_parkir`
--

CREATE TABLE `tiket_parkir` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `no_invoice` varchar(255) NOT NULL,
  `jenis` enum('non_member','member') NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `jenis_kendaraan` enum('motor','mobil') NOT NULL,
  `plat_nomor` varchar(255) DEFAULT NULL,
  `foto_plat` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `total_menit` int(11) DEFAULT NULL,
  `tarif_per_jam` int(11) NOT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `status` enum('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
  `petugas_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `tiket_parkir`
--

INSERT INTO `tiket_parkir` (`id`, `no_invoice`, `jenis`, `member_id`, `jenis_kendaraan`, `plat_nomor`, `foto_plat`, `barcode`, `waktu_masuk`, `waktu_keluar`, `total_menit`, `tarif_per_jam`, `total_harga`, `status`, `petugas_id`, `created_at`, `updated_at`) VALUES
(1, 'INV-20261105-0001', 'non_member', NULL, 'motor', 'BA BH JH', 'plat/1793840848_ba-bh-jh.jpg', 'ba6101e3-3183-4061-8bc3-403cf7873358', '2026-11-05 01:04:59', '2026-11-05 02:18:37', 74, 3000, 6000, 'selesai', 3, '2026-11-04 18:04:59', '2026-11-04 19:18:37'),
(2, 'INV-20261105-0002', 'member', NULL, 'motor', NULL, NULL, 'defd7fb9-a44c-40d9-b565-a410816cf3e1', '2026-11-05 04:26:18', '2026-11-05 04:27:21', 1, 3000, 0, 'selesai', 3, '2026-11-04 21:26:18', '2026-11-04 21:27:21'),
(3, 'INV-20261105-0003', 'member', NULL, 'motor', 'FI 0989 TR', NULL, 'c5452856-5ce5-4872-9c1f-1243068a5800', '2026-11-05 05:49:08', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-04 22:49:08', '2026-11-04 22:49:08'),
(4, 'INV-20261105-0004', 'member', NULL, 'motor', 'GI 9890 TR', NULL, 'a70cca3d-8245-4d8b-a340-ee3905e415b4', '2026-11-05 05:56:18', '2026-11-05 05:57:27', 1, 3000, 0, 'selesai', 3, '2026-11-04 22:56:18', '2026-11-04 22:57:27'),
(5, 'INV-20261105-0005', 'non_member', NULL, 'mobil', NULL, NULL, '6765eeff-1ad5-4df4-8927-288c484df355', '2026-11-05 06:03:32', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-04 23:03:32', '2026-11-04 23:03:32'),
(6, 'INV-20261105-0006', 'non_member', NULL, 'mobil', NULL, NULL, 'be94f9c8-6730-4d60-9c1b-0eabc2d03fdf', '2026-11-05 07:07:07', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-05 00:07:07', '2026-11-05 00:07:07'),
(7, 'INV-20261105-0007', 'member', NULL, 'mobil', 'HN 8567 TR', NULL, 'ddd7d926-ce09-4af1-a696-5fd34a75a2bc', '2026-11-05 07:08:08', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-05 00:08:08', '2026-11-05 00:08:08'),
(8, 'INV-20261105-0008', 'non_member', NULL, 'mobil', 'NM 7978 GH', NULL, '74842424-aaa5-4934-90f5-2f25dfd3b1b1', '2026-11-05 18:47:14', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-05 11:47:14', '2026-11-05 11:48:11'),
(9, 'INV-20261108-0001', 'non_member', NULL, 'motor', NULL, NULL, '97c69aeb-056b-4766-a7cc-9250b9aca81d', '2026-11-08 03:18:24', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-07 20:18:24', '2026-11-07 20:18:24'),
(10, 'INV-20261108-0002', 'non_member', NULL, 'motor', 'JN', NULL, 'd1ada56e-ef3f-4356-9a77-c0fcfcaad8d6', '2026-11-08 03:55:55', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-07 20:55:55', '2026-11-07 20:56:33'),
(11, 'INV-20261108-0003', 'non_member', NULL, 'motor', NULL, NULL, 'eee74b83-1836-4b66-ae04-bb8afdea2967', '2026-11-08 04:41:31', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-07 21:41:31', '2026-11-07 21:41:31'),
(12, 'INV-20261108-0004', 'non_member', NULL, 'motor', NULL, NULL, '3d2d85bf-03c1-4612-a576-53863877d67d', '2026-11-08 08:18:42', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-08 01:18:42', '2026-11-08 01:18:42'),
(13, 'INV-20261108-0005', 'non_member', NULL, 'motor', NULL, NULL, 'a8ae1af3-2b3f-47c3-a643-4e6fe637b0c2', '2026-11-08 09:08:03', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-08 02:08:03', '2026-11-08 02:08:03'),
(14, 'INV-20261108-0006', 'non_member', NULL, 'motor', 'HGH', NULL, '386a9b19-94bb-4ac4-9af0-914cf3aebf23', '2026-11-08 17:31:53', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-08 10:31:53', '2026-11-08 10:32:05'),
(15, 'INV-20261108-0007', 'non_member', NULL, 'mobil', 'GFG', NULL, '4a7b05c5-557a-4ae1-a520-0f0c64132c87', '2026-11-08 17:34:58', '2026-11-08 17:57:38', 22, 5000, 5000, 'selesai', 1, '2026-11-08 10:34:58', '2026-11-08 10:57:38'),
(16, 'INV-20261109-0001', 'non_member', NULL, 'motor', NULL, NULL, 'bc5c8d69-0099-464c-8e5a-a38bde3667cf', '2026-11-09 13:12:51', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-09 06:12:51', '2026-11-09 06:12:51'),
(17, 'INV-20261109-0002', 'non_member', NULL, 'motor', 'BA 7678 HU', 'plat/1794204828_ba-7678-hu.png', 'ed10878a-651f-4825-a597-6f729fe1bf4b', '2026-11-09 13:13:09', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-09 06:13:09', '2026-11-09 06:13:49'),
(18, 'INV-20261109-0003', 'non_member', NULL, 'motor', 'BA 6757 BH', NULL, '6cb75fde-7b03-4ebf-b895-41436408df68', '2026-11-09 13:17:10', '2026-11-09 13:17:55', 0, 3000, 3000, 'selesai', 3, '2026-11-09 06:17:10', '2026-11-09 06:17:55'),
(19, 'INV-20261109-0004', 'non_member', NULL, 'motor', NULL, NULL, '750d459f-b672-4d6f-b0b1-75e29d79b212', '2026-11-09 14:13:37', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-09 07:13:37', '2026-11-09 07:13:37'),
(20, 'INV-20261109-0005', 'non_member', NULL, 'mobil', NULL, NULL, 'fe3bd548-0eb7-45bd-8681-92e335aeb139', '2026-11-09 14:14:06', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-09 07:14:06', '2026-11-09 07:14:06'),
(21, 'INV-20261109-0006', 'non_member', NULL, 'mobil', NULL, NULL, '8d76b408-5b3d-4e2d-9a54-3e3e483f8d78', '2026-11-09 14:48:28', NULL, NULL, 5000, NULL, 'aktif', NULL, '2026-11-09 07:48:28', '2026-11-09 07:48:28'),
(22, 'INV-20261110-0001', 'non_member', NULL, 'motor', NULL, NULL, '15a8c0aa-b50e-435a-9e0d-2be603b9d13c', '2026-11-10 13:28:30', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-10 06:28:30', '2026-11-10 06:28:30'),
(23, 'INV-20261110-0002', 'non_member', NULL, 'motor', NULL, NULL, 'bfe782d4-fb3e-4529-9b64-e116e0c40d67', '2026-11-10 13:28:49', '2026-11-10 13:40:26', 11, 3000, 3000, 'selesai', 1, '2026-11-10 06:28:49', '2026-11-10 06:40:26'),
(24, 'INV-20261110-0003', 'non_member', NULL, 'mobil', 'B 1234 ACB', NULL, '27bbaf57-2e16-4165-ac09-eebdaad259b0', '2026-11-10 13:51:48', '2026-11-10 13:52:36', 0, 5000, 5000, 'selesai', 1, '2026-11-10 06:51:48', '2026-11-10 06:52:36'),
(25, 'INV-20261110-0004', 'non_member', NULL, 'motor', NULL, NULL, '693e587a-8688-4dbb-8ab9-6da862e0421e', '2026-11-10 13:59:14', '2026-11-10 13:59:29', 0, 3000, 3000, 'selesai', 1, '2026-11-10 06:59:14', '2026-11-10 06:59:29'),
(26, 'INV-20261110-0005', 'non_member', NULL, 'mobil', NULL, NULL, '70bb41ca-be3c-478a-ae5f-2dddba87378b', '2026-11-10 14:10:26', '2026-11-10 14:10:44', 0, 5000, 5000, 'selesai', 1, '2026-11-10 07:10:26', '2026-11-10 07:10:44'),
(27, 'INV-20261110-0006', 'non_member', NULL, 'mobil', NULL, NULL, '3fa99d7c-7840-48d0-9bc9-5479aae9bcbb', '2026-11-10 14:16:53', '2026-11-10 14:17:18', 0, 5000, 5000, 'selesai', 1, '2026-11-10 07:16:53', '2026-11-10 07:17:18'),
(28, 'INV-20261110-0007', 'non_member', NULL, 'motor', 'D 1234 YT', NULL, '5efeff8a-53cb-419b-a767-8050f63ba86e', '2026-11-10 14:23:26', '2026-11-10 14:24:03', 0, 3000, 3000, 'selesai', 1, '2026-11-10 07:23:26', '2026-11-10 07:24:03'),
(29, 'INV-20261110-0008', 'non_member', NULL, 'motor', NULL, NULL, '29371b57-b4aa-4202-a7b7-22778de93f40', '2026-11-10 14:30:58', '2026-11-10 14:31:22', 0, 3000, 3000, 'selesai', 1, '2026-11-10 07:30:58', '2026-11-10 07:31:22'),
(30, 'INV-20261110-0009', 'non_member', NULL, 'mobil', NULL, NULL, '8a522a95-d87f-4dbb-a993-cad1300e84eb', '2026-11-10 14:39:00', '2026-11-10 14:39:29', 0, 5000, 5000, 'selesai', 1, '2026-11-10 07:39:00', '2026-11-10 07:39:29'),
(31, 'INV-20261110-0010', 'non_member', NULL, 'motor', NULL, NULL, 'ea8133d3-cbb0-4629-9a62-00a890ba4c6e', '2026-11-10 14:47:18', '2026-11-10 14:47:51', 0, 3000, 3000, 'selesai', 1, '2026-11-10 07:47:18', '2026-11-10 07:47:51'),
(32, 'INV-20261110-0011', 'non_member', NULL, 'mobil', NULL, NULL, '79265ecc-7ea7-4af9-836d-8849027cd9d4', '2026-11-10 14:52:22', '2026-11-10 14:52:40', 0, 5000, 5000, 'selesai', 1, '2026-11-10 07:52:22', '2026-11-10 07:52:40'),
(33, 'INV-20261110-0012', 'non_member', NULL, 'motor', NULL, NULL, 'c26c4a2d-7b42-4fc5-99b1-80b5dc96bef9', '2026-11-10 14:57:57', '2026-11-10 14:58:10', 0, 3000, 3000, 'selesai', 1, '2026-11-10 07:57:57', '2026-11-10 07:58:10'),
(34, 'INV-20261110-0013', 'non_member', NULL, 'motor', NULL, NULL, '7f4a9b67-18fa-4edd-9e07-0c10d5f5fd5d', '2026-11-10 14:59:33', '2026-11-10 14:59:49', 0, 3000, 3000, 'selesai', 1, '2026-11-10 07:59:33', '2026-11-10 07:59:49'),
(35, 'INV-20261110-0014', 'non_member', NULL, 'motor', 'BG 8799 HI', NULL, '76d6cdd2-bf12-479e-a5df-ed94094cbda8', '2026-11-10 15:09:46', '2026-11-10 15:10:18', 0, 3000, 3000, 'selesai', 1, '2026-11-10 08:09:46', '2026-11-10 08:10:18'),
(36, 'INV-20261110-0015', 'non_member', NULL, 'motor', 'VH 8799 BH', NULL, '7f24c77c-274e-4e1c-9685-c8cd4f3079fe', '2026-11-10 15:11:54', '2026-11-10 15:12:23', 0, 3000, 3000, 'selesai', 1, '2026-11-10 08:11:54', '2026-11-10 08:12:23'),
(37, 'INV-20261110-0016', 'non_member', NULL, 'mobil', 'BH 9098 FD', NULL, 'a1cf5dcb-1227-4ab1-a344-2e13eea56ce7', '2026-11-10 15:20:53', '2026-11-10 15:21:30', 0, 5000, 5000, 'selesai', 1, '2026-11-10 08:20:53', '2026-11-10 08:21:30'),
(38, 'INV-20261110-0017', 'non_member', NULL, 'motor', 'VA 87987 HB', NULL, '997381b4-dbf9-4efa-85b3-13adfa8f533a', '2026-11-10 15:21:55', '2026-11-10 15:22:19', 0, 3000, 3000, 'selesai', 1, '2026-11-10 08:21:55', '2026-11-10 08:22:19'),
(39, 'INV-20261110-0018', 'non_member', NULL, 'motor', NULL, NULL, '6539c3f3-a7e3-4c58-ba0d-de5b80b7b30a', '2026-11-10 15:23:36', '2026-11-10 15:23:50', 0, 3000, 3000, 'selesai', 1, '2026-11-10 08:23:36', '2026-11-10 08:23:50'),
(40, 'INV-20261111-0001', 'non_member', NULL, 'motor', 'BA 1234 Y', NULL, '9ab43692-0070-4e11-a246-82c65ade9532', '2026-11-11 09:36:20', '2026-11-11 09:37:15', 0, 3000, 3000, 'selesai', 3, '2026-11-11 02:36:20', '2026-11-11 02:37:15'),
(41, 'INV-20261111-0002', 'non_member', NULL, 'mobil', NULL, NULL, '58095a28-22b2-4e0f-a57c-5a11047e6907', '2026-11-11 09:47:33', '2026-11-11 09:47:53', 0, 5000, 5000, 'selesai', 1, '2026-11-11 02:47:33', '2026-11-11 02:47:53'),
(42, 'INV-20261111-0003', 'non_member', NULL, 'motor', 'BA 1234 YY', NULL, '3605cd29-1db8-41db-a304-f784c4154679', '2026-11-11 09:49:15', '2026-11-11 12:50:16', 181, 3000, 12000, 'selesai', 1, '2026-11-11 02:49:15', '2026-11-11 05:50:16'),
(43, 'INV-20261111-0004', 'non_member', NULL, 'motor', 'BA 8978 VF', 'plat/1794379811_ba-8978-vf.jpg', '6ab3a3de-ed98-45d8-ac32-a6961ccb330f', '2026-11-11 13:49:49', NULL, NULL, 3000, NULL, 'aktif', NULL, '2026-11-11 06:49:49', '2026-11-11 06:50:11'),
(44, 'INV-20261112-0001', 'member', 3, 'motor', 'BS 7878 VI', NULL, '01a6ec42-a43b-4093-87b8-196a1f54cd71', '2026-11-12 02:40:17', '2026-11-12 02:40:46', 0, 3000, 0, 'selesai', 3, '2026-11-11 19:40:17', '2026-11-11 19:40:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tiket_id` bigint(20) UNSIGNED NOT NULL,
  `total_harga` int(11) NOT NULL,
  `nominal_bayar` int(11) NOT NULL,
  `kembalian` int(11) NOT NULL,
  `metode` enum('cash','member') NOT NULL,
  `petugas_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `tiket_id`, `total_harga`, `nominal_bayar`, `kembalian`, `metode`, `petugas_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6000, 7000, 1000, 'cash', 3, '2026-11-04 19:18:37', '2026-11-04 19:18:37'),
(2, 2, 0, 0, 0, 'member', 3, '2026-11-04 21:27:21', '2026-11-04 21:27:21'),
(3, 4, 0, 0, 0, 'member', 3, '2026-11-04 22:57:27', '2026-11-04 22:57:27'),
(4, 15, 5000, 5000, 0, 'cash', 1, '2026-11-08 10:57:38', '2026-11-08 10:57:38'),
(5, 18, 3000, 9000, 6000, 'cash', 3, '2026-11-09 06:17:55', '2026-11-09 06:17:55'),
(6, 23, 3000, 5000, 2000, 'cash', 1, '2026-11-10 06:40:26', '2026-11-10 06:40:26'),
(7, 24, 5000, 5000, 0, 'cash', 1, '2026-11-10 06:52:36', '2026-11-10 06:52:36'),
(8, 25, 3000, 3000, 0, 'cash', 1, '2026-11-10 06:59:29', '2026-11-10 06:59:29'),
(9, 26, 5000, 5000, 0, 'cash', 1, '2026-11-10 07:10:44', '2026-11-10 07:10:44'),
(10, 27, 5000, 5000, 0, 'cash', 1, '2026-11-10 07:17:18', '2026-11-10 07:17:18'),
(11, 28, 3000, 5000, 2000, 'cash', 1, '2026-11-10 07:24:03', '2026-11-10 07:24:03'),
(12, 29, 3000, 10000, 7000, 'cash', 1, '2026-11-10 07:31:22', '2026-11-10 07:31:22'),
(13, 30, 5000, 50000, 45000, 'cash', 1, '2026-11-10 07:39:29', '2026-11-10 07:39:29'),
(14, 31, 3000, 5000, 2000, 'cash', 1, '2026-11-10 07:47:51', '2026-11-10 07:47:51'),
(15, 32, 5000, 5000, 0, 'cash', 1, '2026-11-10 07:52:40', '2026-11-10 07:52:40'),
(16, 33, 3000, 5000, 2000, 'cash', 1, '2026-11-10 07:58:10', '2026-11-10 07:58:10'),
(17, 34, 3000, 3000, 0, 'cash', 1, '2026-11-10 07:59:49', '2026-11-10 07:59:49'),
(18, 35, 3000, 5000, 2000, 'cash', 1, '2026-11-10 08:10:18', '2026-11-10 08:10:18'),
(19, 36, 3000, 5000, 2000, 'cash', 1, '2026-11-10 08:12:23', '2026-11-10 08:12:23'),
(20, 37, 5000, 10000, 5000, 'cash', 1, '2026-11-10 08:21:30', '2026-11-10 08:21:30'),
(21, 38, 3000, 5000, 2000, 'cash', 1, '2026-11-10 08:22:19', '2026-11-10 08:22:19'),
(22, 39, 3000, 5000, 2000, 'cash', 1, '2026-11-10 08:23:50', '2026-11-10 08:23:50'),
(23, 40, 3000, 3000, 0, 'cash', 3, '2026-11-11 02:37:15', '2026-11-11 02:37:15'),
(24, 41, 5000, 5000, 0, 'cash', 1, '2026-11-11 02:47:53', '2026-11-11 02:47:53'),
(25, 42, 12000, 100000, 88000, 'cash', 1, '2026-11-11 05:50:16', '2026-11-11 05:50:16'),
(26, 44, 0, 0, 0, 'member', 3, '2026-11-11 19:40:46', '2026-11-11 19:40:46');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_email_unique` (`email`),
  ADD UNIQUE KEY `admin_username_unique` (`username`);

--
-- Indeks untuk tabel `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `members_no_member_unique` (`no_member`),
  ADD UNIQUE KEY `members_email_unique` (`email`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_otps`
--
ALTER TABLE `password_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `password_otps_email_index` (`email`);

--
-- Indeks untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indeks untuk tabel `tiket_parkir`
--
ALTER TABLE `tiket_parkir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tiket_parkir_no_invoice_unique` (`no_invoice`),
  ADD UNIQUE KEY `tiket_parkir_barcode_unique` (`barcode`),
  ADD KEY `tiket_parkir_member_id_foreign` (`member_id`),
  ADD KEY `tiket_parkir_petugas_id_foreign` (`petugas_id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_tiket_id_foreign` (`tiket_id`),
  ADD KEY `transaksi_petugas_id_foreign` (`petugas_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `members`
--
ALTER TABLE `members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `password_otps`
--
ALTER TABLE `password_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `tiket_parkir`
--
ALTER TABLE `tiket_parkir`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tiket_parkir`
--
ALTER TABLE `tiket_parkir`
  ADD CONSTRAINT `tiket_parkir_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tiket_parkir_petugas_id_foreign` FOREIGN KEY (`petugas_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_petugas_id_foreign` FOREIGN KEY (`petugas_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksi_tiket_id_foreign` FOREIGN KEY (`tiket_id`) REFERENCES `tiket_parkir` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
