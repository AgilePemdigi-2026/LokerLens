# Sumber: user story BARU US-10, Table B.11 (fitur tambahan issue #25, tidak ada di Sprint Planning). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Riwayat tinjauan Analis
  As a Analis, I want to melihat daftar lowongan yang sudah ditinjau beserta hasil akhir kasusnya,
  so that saya dapat menelusuri kembali hasil tinjauan dan keputusan lanjutannya.

  Scenario: Menampilkan lowongan yang sudah ditinjau
    Given I am logged in as "Analis"
    When I follow "Riwayat"
    Then I should see "Riwayat Tinjauan Lowongan"
    And I should see "#LKR-8798"
    And I should not see "#LKR-8821"

  Scenario: Memfilter riwayat berdasarkan hasil akhir
    Given I am logged in as "Analis"
    And I am on the "/riwayat-tinjauan" page
    When I select "Tidak Ditindaklanjuti" from "filter_hasil_akhir"
    And I press "Cari"
    Then I should see "#LKR-8781"
    And I should not see "#LKR-8798"

  Scenario: Melihat detail tinjauan dalam mode hanya-baca
    Given I am logged in as "Analis"
    And I am on the "/riwayat-tinjauan" page
    When I follow "Lihat Detail" in the row for ticket "#LKR-2025-0842"
    Then I should see "Hasil Akhir Kasus"
    And I should see "Menunggu keputusan Kepala Bidang"
    And I should not see a "#catatan_tinjauan" element

  Scenario: Peran lain tidak bisa membuka Riwayat Tinjauan
    Given I am logged in as "Kepala Bidang"
    When I am on the "/riwayat-tinjauan" page
    Then the response status code should be 403
