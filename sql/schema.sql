-- =====================================================================
-- Skema database Dashboard Kehutanan (MariaDB / XAMPP)
--
-- Tiga database, mengikuti alur Data-level EAI:
--   2. kehutanan_master    : hasil TRANSFORM + LOAD, data bersih & seragam (sumber kebenaran)
--   3. kehutanan_dashboard : REPLIKA dari master, satu-satunya DB yang dibaca website
--
-- Jalankan ulang file ini = reset total (semua tabel dibuat ulang).
-- =====================================================================

DROP DATABASE IF EXISTS kehutanan_staging;
DROP DATABASE IF EXISTS kehutanan_master;
CREATE DATABASE kehutanan_staging   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE kehutanan_master    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS kehutanan_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 1. STAGING: semua kolom nilai bertipe teks, persis seperti di sumber
-- ---------------------------------------------------------------------
USE kehutanan_staging;

CREATE TABLE stg_d1_kawasan_hutan (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    sumber_file       VARCHAR(120) NOT NULL,
    tahun_data        SMALLINT     NOT NULL,
    baris_ke          SMALLINT     NOT NULL,
    provinsi_raw      VARCHAR(120),
    tahun_sk_raw      VARCHAR(20),
    hutan_lindung_raw VARCHAR(40),
    konservasi_raw    VARCHAR(40),
    hpt_raw           VARCHAR(40),
    hp_raw            VARCHAR(40),
    hpk_raw           VARCHAR(40),
    jumlah_raw        VARCHAR(40),
    extracted_at      DATETIME     NOT NULL
);

CREATE TABLE stg_d2_tutupan_lahan (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    sumber_file       VARCHAR(120) NOT NULL,
    blok              VARCHAR(20)  NOT NULL,
    baris_ke          SMALLINT     NOT NULL,
    provinsi_raw      VARCHAR(120),
    tahun_data        SMALLINT     NOT NULL,
    hutan_raw         VARCHAR(40),
    hutan_pct_raw     VARCHAR(20),
    non_hutan_raw     VARCHAR(40),
    non_hutan_pct_raw VARCHAR(20),
    jumlah_raw        VARCHAR(40),
    extracted_at      DATETIME     NOT NULL
);

CREATE TABLE stg_d3_karhutla (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    sumber_file      VARCHAR(120) NOT NULL,
    baris_ke         SMALLINT     NOT NULL,
    kode_wilayah_raw VARCHAR(10),
    provinsi_raw     VARCHAR(120),
    tahun_data       SMALLINT     NOT NULL,
    luas_raw         VARCHAR(40),
    extracted_at     DATETIME     NOT NULL
);

CREATE TABLE stg_d4_produksi_kayu (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    sumber_file     VARCHAR(120) NOT NULL,
    datacontent_key VARCHAR(40)  NOT NULL,
    vervar_val      VARCHAR(10),
    provinsi_raw    VARCHAR(120),
    tahun_data      SMALLINT     NOT NULL,
    nilai_raw       VARCHAR(40),
    extracted_at    DATETIME     NOT NULL
);

CREATE TABLE stg_d5_deforestasi (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    sumber_file       VARCHAR(120) NOT NULL,
    baris_ke          SMALLINT     NOT NULL,
    provinsi_raw      VARCHAR(120),
    periode_raw       VARCHAR(20),
    dalam_kawasan_raw VARCHAR(40),
    luar_kawasan_raw  VARCHAR(40),
    total_raw         VARCHAR(40),
    extracted_at      DATETIME     NOT NULL
);

-- ---------------------------------------------------------------------
-- 2. MASTER: data bersih, satuan seragam, kunci = kode provinsi BPS
-- ---------------------------------------------------------------------
USE kehutanan_master;

CREATE TABLE ref_provinsi (
    kode_bps   CHAR(2)     PRIMARY KEY,
    nama       VARCHAR(60) NOT NULL,
    wilayah    VARCHAR(30) NOT NULL,
    updated_at DATETIME    NOT NULL
);

CREATE TABLE fakta_kawasan_hutan (
    kode_bps             CHAR(2)  NOT NULL,
    tahun                SMALLINT NOT NULL,
    hutan_lindung_ha     DECIMAL(14,2),
    konservasi_ha        DECIMAL(14,2),
    produksi_terbatas_ha DECIMAL(14,2),
    produksi_tetap_ha    DECIMAL(14,2),
    produksi_konversi_ha DECIMAL(14,2),
    total_ha             DECIMAL(14,2),
    tahun_sk             SMALLINT,
    catatan              VARCHAR(200),
    updated_at           DATETIME NOT NULL,
    PRIMARY KEY (kode_bps, tahun)
);

CREATE TABLE fakta_tutupan_lahan (
    kode_bps     CHAR(2)  NOT NULL,
    tahun        SMALLINT NOT NULL,
    hutan_ha     DECIMAL(14,2),
    non_hutan_ha DECIMAL(14,2),
    total_ha     DECIMAL(14,2),
    persen_hutan DECIMAL(5,2),
    catatan      VARCHAR(200),
    updated_at   DATETIME NOT NULL,
    PRIMARY KEY (kode_bps, tahun)
);

CREATE TABLE fakta_karhutla (
    kode_bps   CHAR(2)  NOT NULL,
    tahun      SMALLINT NOT NULL,
    luas_ha    DECIMAL(14,2),
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (kode_bps, tahun)
);

CREATE TABLE fakta_produksi_kayu (
    kode_bps    CHAR(2)  NOT NULL,
    tahun       SMALLINT NOT NULL,
    produksi_m3 DECIMAL(16,2),
    updated_at  DATETIME NOT NULL,
    PRIMARY KEY (kode_bps, tahun)
);

CREATE TABLE fakta_deforestasi (
    kode_bps         CHAR(2)    NOT NULL,
    periode          VARCHAR(9) NOT NULL,
    tahun_awal       SMALLINT   NOT NULL,
    tahun_akhir      SMALLINT   NOT NULL,
    dalam_kawasan_ha DECIMAL(14,2),
    luar_kawasan_ha  DECIMAL(14,2),
    total_ha         DECIMAL(14,2),
    updated_at       DATETIME   NOT NULL,
    PRIMARY KEY (kode_bps, periode)
);

-- Catatan setiap tahap ETL & hasil cek kualitas
CREATE TABLE etl_log (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    run_id        VARCHAR(20) NOT NULL,
    tahap         VARCHAR(20) NOT NULL,
    dataset       VARCHAR(10) NOT NULL,
    baris_masuk   INT,
    baris_dimuat  INT,
    baris_dibuang INT,
    status        VARCHAR(10) NOT NULL,
    pesan         TEXT,
    waktu         DATETIME    NOT NULL
);

CREATE TABLE sync_log (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    dataset       VARCHAR(10) NOT NULL,
    baris_dicek   INT NOT NULL,
    baris_baru    INT NOT NULL,
    baris_berubah INT NOT NULL,
    baris_sama    INT NOT NULL,
    detail        TEXT,
    waktu         DATETIME NOT NULL
);

-- ---------------------------------------------------------------------
-- 3. DASHBOARD (replika): struktur disalin persis dari master
-- ---------------------------------------------------------------------
USE kehutanan_dashboard;

DROP TABLE IF EXISTS ref_provinsi, fakta_kawasan_hutan, fakta_tutupan_lahan, fakta_karhutla,
                     fakta_produksi_kayu, fakta_deforestasi, etl_log, sync_log, replication_log;

CREATE TABLE ref_provinsi        LIKE kehutanan_master.ref_provinsi;
CREATE TABLE fakta_kawasan_hutan LIKE kehutanan_master.fakta_kawasan_hutan;
CREATE TABLE fakta_tutupan_lahan LIKE kehutanan_master.fakta_tutupan_lahan;
CREATE TABLE fakta_karhutla      LIKE kehutanan_master.fakta_karhutla;
CREATE TABLE fakta_produksi_kayu LIKE kehutanan_master.fakta_produksi_kayu;
CREATE TABLE fakta_deforestasi   LIKE kehutanan_master.fakta_deforestasi;
CREATE TABLE etl_log             LIKE kehutanan_master.etl_log;
CREATE TABLE sync_log            LIKE kehutanan_master.sync_log;

-- Bukti replikasi: dicatat di sisi replika setiap kali data disalin
CREATE TABLE replication_log (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    tabel            VARCHAR(40) NOT NULL,
    baris_master     INT NOT NULL,
    baris_disisipkan INT NOT NULL,
    baris_diperbarui INT NOT NULL,
    baris_dihapus    INT NOT NULL,
    checksum_master  BIGINT,
    checksum_replika BIGINT,
    status           VARCHAR(10) NOT NULL,
    waktu            DATETIME NOT NULL
);
