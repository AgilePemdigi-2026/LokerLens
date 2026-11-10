# Sumber: SRS LokerLens, Appendix B, Table B.3 (US-02).
Feature: Meninjau lowongan kerja dengan prioritas tinggi

  Background:
    Given I am logged in as "Analis"

  Scenario: Menampilkan daftar lowongan prioritas tinggi
    When I follow "Pemantauan"
    Then I should see "Daftar Lowongan Prioritas Tinggi"

  Scenario: Memfilter daftar berdasarkan skor
    Given I am on the "/tinjau-lowongan/" page
    When I select "Skor Tinggi" from "filter_skor"
    And I select "Skor Tertinggi" from "urutan"
    And I press "Cari"
    Then I should see "Daftar Sesuai Kriteria Filter"

  Scenario: Mencari lowongan berdasarkan nomor tiket dan data ditemukan
    Given I am on the "/tinjau-lowongan/" page
    When I fill in "nomor_tiket" with "#LKR-8819"
    And I press "Cari"
    Then I should see "Hasil Pencarian"

  Scenario: Data lowongan tidak ditemukan
    Given I am on the "/tinjau-lowongan/" page
    When I fill in "nomor_tiket" with "#LKR-0000"
    And I press "Cari"
    Then I should see "Data Lowongan Tidak Ditemukan"

  Scenario: Meninjau detail lowongan
    Given I am on the "/tinjau-lowongan/" page
    When I follow "Lihat Detail"
    Then I should see "Detail Lowongan Terpilih"
    And I should see "Confidence Score"
    And I should see "Indikator Mencurigakan"

  Scenario: Mengisi catatan tinjauan dan melanjutkan
    Given I am on the review detail page for ticket "#LKR-8819"
    When I fill in "catatan_tinjauan" with "catatan tinjauan"
    And I select "Terkonfirmasi Mencurigakan" from "hasil_tinjauan"
    And I press "Lanjutkan"
    Then I should see "Hasil Tinjauan Berhasil Disimpan"
    And I should see "Daftar Lowongan Prioritas Tinggi"
