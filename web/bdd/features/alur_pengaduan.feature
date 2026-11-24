# Alur pengaduan antar aktor sesuai SRS BR-05: hanya skor >= 70% (prioritas) yang masuk ke Analis.
# Skor di bawah 70% otomatis "Selesai - Risiko Rendah" dan tidak diteruskan ke petugas.
# Skor diatur langsung di database test, supaya skenario tidak bergantung pada model ML.
Feature: Alur pengaduan dari Masyarakat sampai Kepala Bidang
  As a Masyarakat, I want pengaduan berisiko tinggi saya ditinjau petugas,
  so that saya bisa melihat perkembangan penanganannya.

  Background:
    Given I am logged in as "Masyarakat"
    And I am on the "/periksa-lowongan/" page
    When I fill in "nama_perusahaan" with "PT Alur Pengaduan"
    And I fill in "posisi_lowongan" with "Staff Gudang"
    And I fill in "deskripsi_lowongan" with "Dibutuhkan staff gudang, kirim CV lewat WhatsApp dan siapkan biaya pelatihan sebelum mulai bekerja."
    And I press "Periksa Sekarang"

  Scenario: Pengaduan skor 85% ditinjau Analis, diteruskan ke Kepala Bidang, dan statusnya terlihat oleh Masyarakat
    Given the newest complaint is classified with a score of "85"

    Given I am logged in as "Analis"
    When I am on the "/tinjau-lowongan" page
    Then I should see the newest complaint ticket
    When I follow "Lihat Detail" in the row for the newest complaint
    Then I should see "Detail Lowongan Terpilih"
    And I should see "Prioritas tinggi"
    When I fill in "catatan_tinjauan" with "Ada permintaan biaya pelatihan."
    And I select "Terkonfirmasi Mencurigakan" from "hasil_tinjauan"
    And I press "Lanjutkan"
    Then I should see "Hasil Tinjauan Berhasil Disimpan"

    Given I am logged in as "Kepala Bidang"
    When I am on the "/tindak-lanjut" page
    Then I should see the newest complaint ticket

    Given I am logged in as "Masyarakat"
    And I am on the "/status-pengaduan" page
    When I follow the newest complaint ticket
    Then I should see "Menunggu Keputusan"

  Scenario: Pengaduan skor 55% tidak masuk ke Analis dan selesai sebagai risiko rendah
    Given the newest complaint is classified with a score of "55"

    Given I am logged in as "Analis"
    When I am on the "/tinjau-lowongan" page
    Then I should not see the newest complaint ticket

    Given I am logged in as "Masyarakat"
    When I am on the "/riwayat-pengaduan" page
    Then I should see the newest complaint ticket
    When I am on the status detail page for the newest complaint
    Then I should see "Selesai - Risiko Rendah"
    And I should see "tidak diteruskan ke petugas"
