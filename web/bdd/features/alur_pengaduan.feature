# Bug alur antar aktor: pengaduan baru dengan skor di bawah 70% tidak sampai ke Analis.
# Aturan yang benar: SEMUA pengaduan baru masuk antrean Analis. Skor >= 70% hanya ditandai
# prioritas dan tampil paling atas.
# Skor diatur langsung di database test, supaya skenario tidak bergantung pada model ML.
Feature: Alur pengaduan dari Masyarakat sampai Kepala Bidang
  As a Masyarakat, I want pengaduan saya ditinjau petugas berapa pun skornya,
  so that saya bisa melihat perkembangan penanganannya.

  Scenario Outline: Pengaduan baru ditinjau Analis, diteruskan ke Kepala Bidang, dan statusnya terlihat oleh Masyarakat
    Given I am logged in as "Masyarakat"
    And I am on the "/periksa-lowongan/" page
    When I fill in "nama_perusahaan" with "PT Alur Pengaduan"
    And I fill in "posisi_lowongan" with "Staff Gudang"
    And I fill in "deskripsi_lowongan" with "Dibutuhkan staff gudang, kirim CV lewat WhatsApp dan siapkan biaya pelatihan sebelum mulai bekerja."
    And I press "Periksa Sekarang"
    And the newest complaint is classified with a score of "<skor>"

    Given I am logged in as "Analis"
    When I am on the "/tinjau-lowongan" page
    Then I should see the newest complaint ticket
    When I follow "Lihat Detail" in the row for the newest complaint
    Then I should see "Detail Lowongan Terpilih"
    And I should see "<tanda>"
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

    Examples:
      | skor | tanda            |
      | 55   | Bukan prioritas  |
      | 85   | Prioritas tinggi |
